<?php

namespace App\Http\Controllers;

use App\AI\Ochat;
use App\Models\AiProjectChat;
use App\Models\Proyecto;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Services\AiIntentService;
use App\Services\AiPromptBuilderService;
use App\Services\AiResponseParserService;
use App\Services\AiSimilarProjectService;
use App\Services\OllamaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function __construct(
        private readonly OllamaService           $ollama,
        private readonly AiIntentService         $intentService,
        private readonly AiSimilarProjectService $similarService,
        private readonly AiPromptBuilderService  $promptBuilder,
        private readonly AiResponseParserService $parser,
    ) {}

    // ── View ──────────────────────────────────────────────────────────────────

    public function index()
    {
        $proyectos = Proyecto::where('user_id', auth()->id())
            ->orderByDesc('updated_at')
            ->get(['id', 'nombre', 'estado', 'descripcion']);

        return view('proyectos.chat', compact('proyectos'));
    }

    // ── Main message handler ──────────────────────────────────────────────────

    public function sendMessage(Request $request): JsonResponse
    {
        set_time_limit(0);
        $userId = auth()->id() ?? 1;

        // ── Confirm / apply actions ───────────────────────────────────────
        if ($request->filled('action')) {
            return $this->handleConfirmAction($request, $userId);
        }

        $request->validate([
            'message'     => 'required|string|max:3000',
            'proyecto_id' => 'nullable|integer',
        ]);

        $message    = trim($request->message);
        $proyectoId = $request->input('proyecto_id') ?: null;

        // Verify ownership of the selected project
        $proyectoActivo = null;
        if ($proyectoId) {
            $proyectoActivo = Proyecto::where('id', $proyectoId)
                ->where('user_id', $userId)
                ->first(['id', 'nombre', 'descripcion', 'estado']);

            if (!$proyectoActivo) {
                $proyectoId = null;
            }
        }

        // Intent (context-aware when project is selected)
        $intent = $proyectoActivo
            ? $this->intentService->detectWithContext($message, hasProject: true)
            : $this->intentService->detect($message);

        // Save prompt to history
        $record = AiProjectChat::create([
            'user_id'        => $userId,
            'proyecto_id'    => $proyectoId,
            'prompt_usuario' => $message,
            'tipo_accion'    => $intent,
            'estado'         => 'borrador',
        ]);

        Log::info('[ChatController] sendMessage', [
            'chat_id'  => $record->id,
            'intent'   => $intent,
            'proyecto' => $proyectoActivo?->nombre,
        ]);

        // ── With active project → route by intent ────────────────────────
        if ($proyectoActivo) {
            return match ($intent) {
                'actualizar_tareas'  => $this->callOllamaForTaskUpdates($message, $proyectoActivo, $record),
                'eliminar_tareas'    => $this->callOllamaForTaskDeletes($message, $proyectoActivo, $record),
                'mover_tareas_estado' => $this->callOllamaForTaskStatusChange($message, $proyectoActivo, $record),
                default              => $this->callOllamaForProjectProposal($message, $proyectoActivo, $record),
            };
        }

        // ── Task-specific intents without a project → helpful message ─────
        if ($this->intentService->requiresActiveProject($intent)) {
            $record->update(['estado' => 'descartado']);
            $label = $this->intentService->label($intent);
            return response()->json([
                'response' => "⚠ Para <strong>{$label}</strong>, primero selecciona un proyecto activo usando el selector de arriba.",
            ]);
        }

        // ── Without project → duplicate check + standard creation ─────────
        if ($this->intentService->isProjectRelated($intent)) {
            $similar = $this->similarService->findSimilar($message, $userId);

            if ($similar->isNotEmpty()) {
                $record->update([
                    'datos_detectados' => ['similar_project_ids' => $similar->pluck('id')->toArray()],
                ]);

                return response()->json([
                    'type'     => 'similar_found',
                    'chat_id'  => $record->id,
                    'intent'   => $intent,
                    'projects' => $similar->map(fn(Proyecto $p) => [
                        'id'          => $p->id,
                        'nombre'      => $p->nombre,
                        'descripcion' => Str::limit($p->descripcion ?? '', 100),
                        'estado'      => $p->estado,
                        'url_show'    => route('proyectos.show', $p),
                        'url_edit'    => route('proyectos.edit', $p),
                    ]),
                ]);
            }
        }

        return $this->callOllamaAndCreate($message, $record, $userId);
    }

    // ── Confirm / action handler ──────────────────────────────────────────────

    private function handleConfirmAction(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'action'     => 'required|string|in:apply_proposal,discard_proposal,apply_task_updates,apply_task_deletes,apply_task_status_changes,confirm_create,use_existing,discard',
            'chat_id'    => 'required|integer',
            'project_id' => 'nullable|integer',
        ]);

        $record = AiProjectChat::where('id', $request->chat_id)
            ->where('user_id', $userId)
            ->first();

        if (!$record) {
            return response()->json(['response' => 'Registro de historial no encontrado.']);
        }

        return match ($request->action) {
            'apply_proposal'           => $this->applyProjectProposal($record, $userId),
            'discard_proposal'         => $this->discardProposal($record),
            'apply_task_updates'       => $this->applyTaskUpdates($record, $userId),
            'apply_task_deletes'       => $this->applyTaskDeletes($record, $userId),
            'apply_task_status_changes' => $this->applyTaskStatusChanges($record, $userId),
            'confirm_create'           => $this->callOllamaAndCreate($record->prompt_usuario, $record, $userId),
            'use_existing'             => $this->redirectToExistingProject($request, $record, $userId),
            'discard'                  => $this->discardProposal($record),
            default                    => response()->json(['response' => 'Acción no reconocida.']),
        };
    }

    // ── Apply proposal to real DB ─────────────────────────────────────────────

    private function applyProjectProposal(AiProjectChat $record, int $userId): JsonResponse
    {
        if (!$record->proyecto_id || empty($record->datos_detectados)) {
            return response()->json(['response' => 'No hay propuesta guardada para aplicar.']);
        }

        $proyecto = Proyecto::where('id', $record->proyecto_id)
            ->where('user_id', $userId)
            ->first();

        if (!$proyecto) {
            return response()->json(['response' => 'Proyecto no encontrado o sin permiso.']);
        }

        $proposal = $record->datos_detectados;

        try {
            DB::beginTransaction();

            $counts = ['requerimientos' => 0, 'tareas' => 0, 'sprints' => 0, 'insumos' => 0];

            // ── Requirements ────────────────────────────────────────────
            foreach ($proposal['requerimientos'] ?? [] as $i => $req) {
                if (empty($req['titulo'])) continue;

                $tipo    = in_array($req['tipo'] ?? '', ['funcional', 'no_funcional']) ? $req['tipo'] : 'funcional';
                $prio    = in_array($req['prioridad'] ?? '', ['alta', 'media', 'baja']) ? $req['prioridad'] : 'media';
                $prefix  = $tipo === 'no_funcional' ? 'RNF-' : 'RF-';
                $codigo  = $prefix . str_pad($i + 1, 3, '0', STR_PAD_LEFT);

                $proyecto->requirements()->create([
                    'codigo'      => $codigo,
                    'titulo'      => $req['titulo'],
                    'descripcion' => $req['descripcion'] ?? '',
                    'tipo'        => $tipo,
                    'prioridad'   => $prio,
                ]);
                $counts['requerimientos']++;
            }

            // ── Tasks ────────────────────────────────────────────────────
            $status = TaskStatus::first()
                ?? TaskStatus::create(['nombre' => 'To Do', 'color' => '#808080', 'orden' => 1]);

            foreach ($proposal['tareas'] ?? [] as $task) {
                if (empty($task['titulo'])) continue;

                $proyecto->tasks()->create([
                    'titulo'         => $task['titulo'],
                    'descripcion'    => $task['descripcion'] ?? '',
                    'task_status_id' => $status->id,
                ]);
                $counts['tareas']++;
            }

            // ── Sprints ──────────────────────────────────────────────────
            foreach ($proposal['sprints'] ?? [] as $sprint) {
                if (empty($sprint['nombre'])) continue;

                $semanas    = max(1, (int) ($sprint['semanas'] ?? $sprint['duracion_semanas'] ?? 2));
                $fechaInicio = now()->toDateString();
                $fechaFin    = now()->addWeeks($semanas)->toDateString();

                $proyecto->sprints()->create([
                    'nombre'       => $sprint['nombre'],
                    'objetivo'     => $sprint['objetivo'] ?? '',
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin'    => $fechaFin,
                    'estado'       => 'planificado',
                ]);
                $counts['sprints']++;
            }

            // ── Insumos (project_inputs: tipo, titulo, contenido) ────────
            foreach ($proposal['insumos'] ?? [] as $insumo) {
                $titulo = $insumo['titulo'] ?? $insumo['nombre'] ?? '';
                if (empty($titulo)) continue;

                $validTipos = ['software', 'hardware', 'servicio', 'recurso_humano', 'otro'];
                $tipo       = in_array($insumo['tipo'] ?? '', $validTipos) ? $insumo['tipo'] : 'otro';

                $proyecto->inputs()->create([
                    'tipo'     => $tipo,
                    'titulo'   => $titulo,
                    'contenido' => $insumo['contenido'] ?? $insumo['descripcion'] ?? '',
                ]);
                $counts['insumos']++;
            }

            DB::commit();
            $record->update(['estado' => 'aplicado']);

            Log::info('[ChatController] Proposal applied', [
                'proyecto_id' => $proyecto->id,
                'counts'      => $counts,
            ]);

            $urlShow  = route('proyectos.show', $proyecto);
            $urlBoard = route('scrum-board.show', $proyecto);

            $msg = "<strong>✅ Cambios aplicados al proyecto «{$proyecto->nombre}»</strong><br><br>";
            if ($counts['requerimientos']) $msg .= "📋 <strong>{$counts['requerimientos']}</strong> requerimiento(s) creados.<br>";
            if ($counts['tareas'])         $msg .= "✅ <strong>{$counts['tareas']}</strong> tarea(s) creadas — visibles en Scrum Board.<br>";
            if ($counts['sprints'])        $msg .= "🏃 <strong>{$counts['sprints']}</strong> sprint(s) creados.<br>";
            if ($counts['insumos'])        $msg .= "📦 <strong>{$counts['insumos']}</strong> insumo(s) registrados.<br>";

            $total = array_sum($counts);
            if ($total === 0) {
                $msg = 'La propuesta no tenía cambios concretos que guardar.';
            } else {
                $msg .= "<br><div style='display:flex;gap:8px;flex-wrap:wrap;margin-top:8px'>"
                    . "<a href='{$urlShow}' style='display:inline-block;background:rgba(99,102,241,0.85);color:#fff;font-size:12px;font-weight:700;padding:6px 14px;border-radius:8px;text-decoration:none'>Ver proyecto →</a>";
                if ($counts['tareas'] > 0) {
                    $msg .= "<a href='{$urlBoard}' style='display:inline-block;border:1px solid rgba(255,255,255,0.15);color:#f8fafc;font-size:12px;font-weight:700;padding:6px 12px;border-radius:8px;text-decoration:none'>Scrum Board</a>";
                }
                $msg .= "</div>";
            }

            return response()->json(['response' => $msg]);

        } catch (\Exception $e) {
            DB::rollBack();
            $record->update(['estado' => 'error']);
            Log::error('[ChatController] Apply proposal error', ['message' => $e->getMessage()]);
            return response()->json(['response' => 'Error al guardar cambios: ' . $e->getMessage()]);
        }
    }

    // ── Shared: load project tasks with status name ───────────────────────────

    private function loadTasksForAi(Proyecto $proyecto): \Illuminate\Database\Eloquent\Collection
    {
        return $proyecto->tasks()
            ->with('status:id,nombre')
            ->get(['id', 'titulo', 'descripcion', 'task_status_id']);
    }

    /**
     * Match a task by titulo (case-insensitive exact match) when the AI omits/wrongs the ID.
     * Returns the corrected task ID, or 0 if no match.
     */
    private function resolveTaskId(
        int $aiId,
        string $aiTitle,
        array $validIds,
        \Illuminate\Database\Eloquent\Collection $tasks
    ): int {
        if (in_array($aiId, $validIds, true)) {
            return $aiId;
        }

        // Title-based fallback (exact, case-insensitive)
        $needle  = mb_strtolower(trim($aiTitle));
        $matched = $tasks->first(fn(Task $t) => mb_strtolower(trim($t->titulo)) === $needle);

        return $matched ? $matched->id : 0;
    }

    // ── Ollama: propose deletion of tasks ─────────────────────────────────────

    private function callOllamaForTaskDeletes(
        string        $message,
        Proyecto      $proyecto,
        AiProjectChat $record,
    ): JsonResponse {
        $tasks = $this->loadTasksForAi($proyecto);

        if ($tasks->isEmpty()) {
            $record->update(['estado' => 'error']);
            return response()->json(['response' => 'El proyecto no tiene tareas para eliminar.']);
        }

        $tasksForPrompt = $tasks->map(fn(Task $t) => [
            'id'          => $t->id,
            'titulo'      => $t->titulo,
            'descripcion' => $t->descripcion,
        ])->toArray();

        $result = $this->ollama->generate(
            $this->promptBuilder->deleteTasksPrompt($message, $proyecto->nombre, $tasksForPrompt)
        );

        if (!$result['ok']) {
            $record->update(['estado' => 'error']);
            return response()->json(['response' => 'Error al obtener propuesta de la IA: ' . ($result['error'] ?? '')]);
        }

        $proposal  = $result['data'];
        $toDelete  = $proposal['tareas_a_eliminar'] ?? $proposal['tareas'] ?? $proposal['tasks'] ?? [];

        if (empty($toDelete)) {
            $record->update(['estado' => 'error']);
            return response()->json(['response' => 'La IA no identificó tareas para eliminar. Sé más específico.']);
        }

        $validIds     = $tasks->pluck('id')->map(fn($id) => (int) $id)->toArray();
        $tasksById    = $tasks->keyBy('id');
        $validDeletes = [];
        $invalidCount = 0;

        foreach ($toDelete as $d) {
            $resolvedId = $this->resolveTaskId(
                (int) ($d['id'] ?? 0),
                (string) ($d['titulo'] ?? ''),
                $validIds, $tasks
            );

            if (!$resolvedId) { $invalidCount++; continue; }

            $d['id']     = $resolvedId;
            $d['titulo'] = $d['titulo'] ?? ($tasksById[$resolvedId]?->titulo ?? '—');
            $validDeletes[] = $d;
        }

        $stored = [
            'tipo'              => 'eliminar_tareas',
            'resumen'           => $proposal['resumen'] ?? 'Eliminar tareas seleccionadas',
            'tareas_a_eliminar' => $validDeletes,
            'invalid_count'     => $invalidCount,
        ];

        $record->update([
            'respuesta_ia'     => json_encode($proposal, JSON_UNESCAPED_UNICODE),
            'datos_detectados' => $stored,
            'estado'           => 'borrador',
        ]);

        return response()->json([
            'type'            => 'task_delete_proposal',
            'chat_id'         => $record->id,
            'proyecto_id'     => $proyecto->id,
            'proyecto_nombre' => $proyecto->nombre,
            'data'            => $stored,
        ]);
    }

    // ── Ollama: propose task status changes ───────────────────────────────────

    private function callOllamaForTaskStatusChange(
        string        $message,
        Proyecto      $proyecto,
        AiProjectChat $record,
    ): JsonResponse {
        $tasks    = $this->loadTasksForAi($proyecto);
        $statuses = TaskStatus::orderBy('orden')->get(['id', 'nombre']);

        if ($tasks->isEmpty()) {
            $record->update(['estado' => 'error']);
            return response()->json(['response' => 'El proyecto no tiene tareas para mover.']);
        }

        $tasksForPrompt = $tasks->map(fn(Task $t) => [
            'id'     => $t->id,
            'titulo' => $t->titulo,
            'estado' => $t->status?->nombre ?? 'pendiente',
        ])->toArray();

        $statusNames = $statuses->pluck('nombre')->toArray();

        $result = $this->ollama->generate(
            $this->promptBuilder->moveTaskStatusPrompt($message, $proyecto->nombre, $tasksForPrompt, $statusNames)
        );

        if (!$result['ok']) {
            $record->update(['estado' => 'error']);
            return response()->json(['response' => 'Error al obtener propuesta de la IA.']);
        }

        $proposal = $result['data'];
        $changes  = $proposal['cambios_estado'] ?? $proposal['changes'] ?? $proposal['status_changes'] ?? [];

        if (empty($changes)) {
            $record->update(['estado' => 'error']);
            return response()->json(['response' => 'La IA no identificó cambios de estado. Sé más específico.']);
        }

        $validIds  = $tasks->pluck('id')->map(fn($id) => (int) $id)->toArray();
        $tasksById = $tasks->keyBy('id');
        // Map status name (lowercase) → id for quick lookup
        $statusMap = $statuses->mapWithKeys(fn($s) => [mb_strtolower($s->nombre) => $s->id]);

        $validChanges = [];
        $invalidCount = 0;

        foreach ($changes as $c) {
            $resolvedId = $this->resolveTaskId(
                (int) ($c['id'] ?? 0),
                (string) ($c['titulo_actual'] ?? ''),
                $validIds, $tasks
            );

            if (!$resolvedId) { $invalidCount++; continue; }

            // Resolve target status by name
            $statusName = mb_strtolower(trim($c['estado_nuevo'] ?? ''));
            $statusId   = $statusMap[$statusName] ?? null;

            if (!$statusId) {
                // Fuzzy: find first status containing or contained in the requested name
                $statusId = $statuses
                    ->first(fn($s) =>
                        str_contains(mb_strtolower($s->nombre), $statusName) ||
                        str_contains($statusName, mb_strtolower($s->nombre))
                    )?->id;
            }

            if (!$statusId) { $invalidCount++; continue; }

            $c['id']                  = $resolvedId;
            $c['titulo_actual']       = $c['titulo_actual'] ?? ($tasksById[$resolvedId]?->titulo ?? '—');
            $c['estado_actual']       = $tasksById[$resolvedId]?->status?->nombre ?? '—';
            $c['status_id']           = $statusId;
            $c['estado_nuevo_nombre'] = $statuses->firstWhere('id', $statusId)?->nombre ?? $c['estado_nuevo'] ?? '—';
            $validChanges[]           = $c;
        }

        $stored = [
            'tipo'           => 'mover_tareas_estado',
            'resumen'        => $proposal['resumen'] ?? 'Cambios de estado propuestos',
            'cambios_estado' => $validChanges,
            'invalid_count'  => $invalidCount,
        ];

        $record->update([
            'respuesta_ia'     => json_encode($proposal, JSON_UNESCAPED_UNICODE),
            'datos_detectados' => $stored,
            'estado'           => 'borrador',
        ]);

        return response()->json([
            'type'            => 'task_status_proposal',
            'chat_id'         => $record->id,
            'proyecto_id'     => $proyecto->id,
            'proyecto_nombre' => $proyecto->nombre,
            'data'            => $stored,
        ]);
    }

    // ── Ollama: request task updates for existing tasks ───────────────────────

    private function callOllamaForTaskUpdates(
        string        $message,
        Proyecto      $proyecto,
        AiProjectChat $record,
    ): JsonResponse {
        // Load current tasks with their status names
        $tasks = $proyecto->tasks()
            ->with('status:id,nombre')
            ->get(['id', 'titulo', 'descripcion', 'task_status_id']);

        if ($tasks->isEmpty()) {
            $record->update(['estado' => 'error']);
            return response()->json([
                'response' => 'El proyecto <strong>' . e($proyecto->nombre) . '</strong> no tiene tareas todavía. '
                    . 'Primero genera tareas desde el asistente.',
            ]);
        }

        $tasksForPrompt = $tasks->map(fn(Task $t) => [
            'id'          => $t->id,
            'titulo'      => $t->titulo,
            'descripcion' => $t->descripcion,
            'estado'      => $t->status?->nombre ?? 'pendiente',
        ])->toArray();

        $prompt = $this->promptBuilder->updateTasksPrompt(
            $message,
            $proyecto->nombre,
            $proyecto->descripcion ?? '',
            $tasksForPrompt
        );

        Log::info('[ChatController] Requesting task updates from Ollama', [
            'chat_id'    => $record->id,
            'proyecto'   => $proyecto->nombre,
            'task_count' => $tasks->count(),
        ]);

        $result = $this->ollama->generate($prompt);

        if (!$result['ok']) {
            $record->update(['estado' => 'error', 'respuesta_ia' => $result['error'] ?? '']);
            return response()->json([
                'response' => 'No se pudo obtener la propuesta de la IA. Intenta reformular el prompt.<br>'
                    . '<small style="color:#94a3b8">' . htmlspecialchars($result['error'] ?? '') . '</small>',
            ]);
        }

        $proposal = $result['data'];

        // Normalize the updates key (AI may use different names)
        $updates = $proposal['actualizaciones_tareas']
            ?? $proposal['actualizaciones']
            ?? $proposal['task_updates']
            ?? $proposal['updates']
            ?? [];

        if (empty($updates)) {
            $resumen = $proposal['resumen'] ?? '';
            $record->update(['respuesta_ia' => $resumen, 'estado' => 'aplicado']);
            return response()->json([
                'response' => $resumen ?: 'La IA no propuso cambios en las tareas. Intenta ser más específico.',
            ]);
        }

        // Validate IDs — with title-matching fallback if AI returns wrong/missing ID
        $validIds  = $tasks->pluck('id')->map(fn($id) => (int) $id)->toArray();
        $tasksById = $tasks->keyBy('id');

        $validUpdates = [];
        $invalidCount = 0;

        foreach ($updates as $u) {
            $resolvedId = $this->resolveTaskId(
                (int) ($u['id'] ?? 0),
                (string) ($u['titulo_actual'] ?? ''),
                $validIds, $tasks
            );

            if (!$resolvedId) {
                $invalidCount++;
                continue;
            }

            $u['id']            = $resolvedId;
            $u['titulo_actual'] = $u['titulo_actual'] ?? ($tasksById[$resolvedId]?->titulo ?? '—');
            $validUpdates[]     = $u;
        }

        $storedProposal = [
            'tipo'                   => 'actualizar_tareas',
            'resumen'                => $proposal['resumen'] ?? 'Cambios propuestos por IA',
            'actualizaciones_tareas' => $validUpdates,
            'invalid_count'          => $invalidCount,
        ];

        $record->update([
            'respuesta_ia'     => json_encode($proposal, JSON_UNESCAPED_UNICODE),
            'datos_detectados' => $storedProposal,
            'estado'           => 'borrador',
        ]);

        return response()->json([
            'type'            => 'task_update_proposal',
            'chat_id'         => $record->id,
            'proyecto_id'     => $proyecto->id,
            'proyecto_nombre' => $proyecto->nombre,
            'data'            => $storedProposal,
        ]);
    }

    // ── Apply task updates to real DB ─────────────────────────────────────────

    private function applyTaskUpdates(AiProjectChat $record, int $userId): JsonResponse
    {
        if (!$record->proyecto_id || empty($record->datos_detectados)) {
            return response()->json(['response' => 'No hay propuesta de actualización guardada.']);
        }

        $proyecto = Proyecto::where('id', $record->proyecto_id)
            ->where('user_id', $userId)
            ->first();

        if (!$proyecto) {
            return response()->json(['response' => 'Proyecto no encontrado o sin permiso.']);
        }

        $updates = $record->datos_detectados['actualizaciones_tareas'] ?? [];

        if (empty($updates)) {
            return response()->json(['response' => 'No hay cambios de tareas para aplicar.']);
        }

        try {
            DB::beginTransaction();

            $applied  = 0;
            $skipped  = 0;
            $warnings = [];

            foreach ($updates as $update) {
                $taskId = (int) ($update['id'] ?? 0);

                if (!$taskId) {
                    $skipped++;
                    $warnings[] = 'Una sugerencia no tenía ID de tarea válido y fue omitida.';
                    continue;
                }

                // Security: task must belong to the active project
                $task = Task::where('id', $taskId)
                    ->where('proyecto_id', $proyecto->id)
                    ->first();

                if (!$task) {
                    $skipped++;
                    $warnings[] = "Tarea ID {$taskId}: no existe o pertenece a otro proyecto.";
                    continue;
                }

                $task->update([
                    'titulo'      => $update['titulo_nuevo'] ?? $task->titulo,
                    'descripcion' => $update['descripcion_nueva'] ?? $task->descripcion,
                ]);

                $applied++;
            }

            DB::commit();
            $record->update(['estado' => 'aplicado']);

            Log::info('[ChatController] Task updates applied', [
                'proyecto_id' => $proyecto->id,
                'applied'     => $applied,
                'skipped'     => $skipped,
            ]);

            $urlBoard = route('scrum-board.show', $proyecto);
            $urlProj  = route('proyectos.show', $proyecto);

            $msg = "<strong>✅ {$applied} tarea(s) actualizadas en «{$proyecto->nombre}»</strong><br>";

            foreach ($warnings as $w) {
                $msg .= "<span style='font-size:10px;color:#fcd34d'>⚠ " . htmlspecialchars($w) . "</span><br>";
            }

            $msg .= "<br><div style='display:flex;gap:8px;flex-wrap:wrap;margin-top:6px'>"
                . "<a href='{$urlBoard}' style='display:inline-block;background:rgba(99,102,241,0.85);color:#fff;font-size:12px;font-weight:700;padding:6px 14px;border-radius:8px;text-decoration:none'>Ver en Scrum Board →</a>"
                . "<a href='{$urlProj}' style='display:inline-block;border:1px solid rgba(255,255,255,0.15);color:#f8fafc;font-size:12px;font-weight:700;padding:6px 10px;border-radius:8px;text-decoration:none'>Ver proyecto</a>"
                . "</div>";

            return response()->json(['response' => $msg]);

        } catch (\Exception $e) {
            DB::rollBack();
            $record->update(['estado' => 'error']);
            Log::error('[ChatController] Task updates error', ['message' => $e->getMessage()]);
            return response()->json(['response' => 'Error al actualizar tareas: ' . $e->getMessage()]);
        }
    }

    // ── Apply: delete tasks ───────────────────────────────────────────────────

    private function applyTaskDeletes(AiProjectChat $record, int $userId): JsonResponse
    {
        $proyecto = Proyecto::where('id', $record->proyecto_id)->where('user_id', $userId)->first();
        if (!$proyecto) {
            return response()->json(['response' => 'Proyecto no encontrado o sin permiso.']);
        }

        $toDelete = $record->datos_detectados['tareas_a_eliminar'] ?? [];
        if (empty($toDelete)) {
            return response()->json(['response' => 'No hay tareas marcadas para eliminar.']);
        }

        try {
            DB::beginTransaction();

            $deleted  = 0;
            $warnings = [];

            foreach ($toDelete as $d) {
                $taskId = (int) ($d['id'] ?? 0);
                $task   = Task::where('id', $taskId)->where('proyecto_id', $proyecto->id)->first();

                if (!$task) {
                    $warnings[] = "Tarea ID {$taskId}: no encontrada o pertenece a otro proyecto.";
                    continue;
                }

                $task->delete();
                $deleted++;
            }

            DB::commit();
            $record->update(['estado' => 'aplicado']);

            Log::info('[ChatController] Task deletes applied', [
                'proyecto_id' => $proyecto->id, 'deleted' => $deleted,
            ]);

            $urlBoard = route('scrum-board.show', $proyecto);
            $msg      = "<strong>🗑 {$deleted} tarea(s) eliminadas de «{$proyecto->nombre}»</strong><br>";
            foreach ($warnings as $w) {
                $msg .= "<span style='font-size:10px;color:#fcd34d'>⚠ " . e($w) . "</span><br>";
            }
            $msg .= "<br><a href='{$urlBoard}' style='display:inline-block;background:rgba(99,102,241,0.85);color:#fff;font-size:12px;font-weight:700;padding:6px 14px;border-radius:8px;text-decoration:none'>Ver Scrum Board →</a>";

            return response()->json(['response' => $msg]);

        } catch (\Exception $e) {
            DB::rollBack();
            $record->update(['estado' => 'error']);
            return response()->json(['response' => 'Error al eliminar tareas: ' . $e->getMessage()]);
        }
    }

    // ── Apply: change task statuses ───────────────────────────────────────────

    private function applyTaskStatusChanges(AiProjectChat $record, int $userId): JsonResponse
    {
        $proyecto = Proyecto::where('id', $record->proyecto_id)->where('user_id', $userId)->first();
        if (!$proyecto) {
            return response()->json(['response' => 'Proyecto no encontrado o sin permiso.']);
        }

        $changes = $record->datos_detectados['cambios_estado'] ?? [];
        if (empty($changes)) {
            return response()->json(['response' => 'No hay cambios de estado para aplicar.']);
        }

        try {
            DB::beginTransaction();

            $applied  = 0;
            $warnings = [];

            foreach ($changes as $c) {
                $taskId   = (int) ($c['id'] ?? 0);
                $statusId = (int) ($c['status_id'] ?? 0);

                $task = Task::where('id', $taskId)->where('proyecto_id', $proyecto->id)->first();
                if (!$task) {
                    $warnings[] = "Tarea ID {$taskId}: no encontrada.";
                    continue;
                }

                $status = TaskStatus::find($statusId);
                if (!$status) {
                    $warnings[] = "Estado ID {$statusId}: no válido.";
                    continue;
                }

                $task->update(['task_status_id' => $statusId]);
                $applied++;
            }

            DB::commit();
            $record->update(['estado' => 'aplicado']);

            Log::info('[ChatController] Task status changes applied', [
                'proyecto_id' => $proyecto->id, 'applied' => $applied,
            ]);

            $urlBoard = route('scrum-board.show', $proyecto);
            $msg      = "<strong>✅ {$applied} tarea(s) movidas en «{$proyecto->nombre}»</strong><br>";
            foreach ($warnings as $w) {
                $msg .= "<span style='font-size:10px;color:#fcd34d'>⚠ " . e($w) . "</span><br>";
            }
            $msg .= "<br><a href='{$urlBoard}' style='display:inline-block;background:rgba(99,102,241,0.85);color:#fff;font-size:12px;font-weight:700;padding:6px 14px;border-radius:8px;text-decoration:none'>Ver Scrum Board →</a>";

            return response()->json(['response' => $msg]);

        } catch (\Exception $e) {
            DB::rollBack();
            $record->update(['estado' => 'error']);
            return response()->json(['response' => 'Error al mover tareas: ' . $e->getMessage()]);
        }
    }

    private function discardProposal(AiProjectChat $record): JsonResponse
    {
        $record->update(['estado' => 'descartado']);
        return response()->json(['response' => 'Propuesta descartada. ¿Qué más puedo ayudarte?']);
    }

    private function redirectToExistingProject(Request $request, AiProjectChat $record, int $userId): JsonResponse
    {
        $proyecto = Proyecto::where('id', $request->project_id)->where('user_id', $userId)->first();

        if (!$proyecto) {
            return response()->json(['response' => 'Proyecto no encontrado.']);
        }

        $record->update(['proyecto_id' => $proyecto->id, 'estado' => 'aplicado']);

        $urlShow = route('proyectos.show', $proyecto);
        $urlEdit = route('proyectos.edit', $proyecto);
        $msg     = "<strong>Redirigiendo a «{$proyecto->nombre}»</strong><br>"
            . "<span style='font-size:11px;color:#64748b'>Edita sus módulos desde el panel del proyecto.</span><br><br>"
            . "<div style='display:flex;gap:8px;flex-wrap:wrap;margin-top:6px'>"
            . "<a href='{$urlShow}' style='display:inline-block;background:rgba(99,102,241,0.8);color:#fff;font-size:12px;font-weight:700;padding:6px 12px;border-radius:8px;text-decoration:none'>Ver proyecto →</a>"
            . "<a href='{$urlEdit}' style='display:inline-block;border:1px solid rgba(255,255,255,0.15);color:#f8fafc;font-size:12px;font-weight:700;padding:6px 10px;border-radius:8px;text-decoration:none'>Editar</a>"
            . "</div>";

        return response()->json(['response' => $msg]);
    }

    // ── History endpoints ─────────────────────────────────────────────────────

    public function history(Request $request): JsonResponse
    {
        $userId     = auth()->id() ?? 1;
        $proyectoId = $request->query('proyecto_id');

        $query = AiProjectChat::where('user_id', $userId)->with('proyecto:id,nombre');
        if ($proyectoId) {
            $query->where('proyecto_id', $proyectoId);
        }

        $records = $query->latest()->take(60)->get();

        return response()->json([
            'history' => $records->map(fn(AiProjectChat $r) => [
                'id'                 => $r->id,
                'tipo_accion'        => $r->tipo_accion,
                'tipo_label'         => $this->intentService->label($r->tipo_accion),
                'prompt_usuario'     => Str::limit($r->prompt_usuario, 120),
                'prompt_completo'    => $r->prompt_usuario,
                'respuesta_resumida' => $r->respuesta_ia
                    ? Str::limit(strip_tags($r->respuesta_ia), 160)
                    : null,
                'respuesta_completa' => $r->respuesta_ia,
                'estado'             => $r->estado,
                'proyecto_id'        => $r->proyecto_id,
                'proyecto_nombre'    => $r->proyecto?->nombre,
                'proyecto_url'       => $r->proyecto_id
                    ? route('proyectos.show', $r->proyecto_id)
                    : null,
                'created_at_human'   => $r->created_at->diffForHumans(),
                'created_at_full'    => $r->created_at->format('d/m/Y H:i'),
            ]),
        ]);
    }

    public function deleteHistoryItem(Request $request, int $id): JsonResponse
    {
        $record = AiProjectChat::where('id', $id)
            ->where('user_id', auth()->id() ?? 1)
            ->first();

        if (!$record) {
            return response()->json(['ok' => false, 'error' => 'Registro no encontrado.'], 404);
        }

        $record->delete();
        return response()->json(['ok' => true]);
    }

    public function clearHistory(Request $request): JsonResponse
    {
        $userId     = auth()->id() ?? 1;
        $proyectoId = $request->input('proyecto_id');

        $query = AiProjectChat::where('user_id', $userId);
        if ($proyectoId) {
            $query->where('proyecto_id', $proyectoId);
        }

        $deleted = $query->delete();
        return response()->json(['ok' => true, 'deleted' => $deleted]);
    }

    // ── Ollama: structured proposal for active project ────────────────────────

    private function callOllamaForProjectProposal(
        string        $message,
        Proyecto      $proyecto,
        AiProjectChat $record,
    ): JsonResponse {
        $prompt = $this->promptBuilder->editProjectPrompt(
            $message,
            $proyecto->nombre,
            $proyecto->descripcion ?? ''
        );

        Log::info('[ChatController] Requesting project proposal from Ollama', [
            'chat_id'  => $record->id,
            'proyecto' => $proyecto->nombre,
        ]);

        // OllamaService forces JSON format — ideal for structured proposals
        $result = $this->ollama->generate($prompt);

        if (!$result['ok']) {
            $record->update(['estado' => 'error', 'respuesta_ia' => $result['error'] ?? '']);
            return response()->json([
                'response' => 'No se pudo interpretar la respuesta de la IA. Intenta reformular el prompt.<br>'
                    . '<small style="color:#94a3b8">' . htmlspecialchars($result['error'] ?? '') . '</small>',
            ]);
        }

        $proposal = $result['data'];

        // Count proposed changes
        $totalChanges =
            count($proposal['requerimientos'] ?? []) +
            count($proposal['tareas'] ?? []) +
            count($proposal['sprints'] ?? []) +
            count($proposal['insumos'] ?? []);

        // If AI only returned a summary with no concrete changes → treat as plain chat
        if ($totalChanges === 0) {
            $resumen = $proposal['resumen'] ?? '';
            $record->update(['respuesta_ia' => $resumen, 'estado' => 'aplicado']);

            $response = $resumen
                ?: 'La IA no propuso cambios concretos. Intenta ser más específico sobre qué quieres agregar.';

            $projectUrl = route('proyectos.show', $proyecto);
            return response()->json([
                'response' => $response
                    . "<br><br><a href='{$projectUrl}' style='font-size:11px;color:#818cf8'>Ir al proyecto →</a>",
            ]);
        }

        // Store proposal for confirmation
        $record->update([
            'respuesta_ia'     => json_encode($proposal, JSON_UNESCAPED_UNICODE),
            'datos_detectados' => $proposal,
            'estado'           => 'borrador',
        ]);

        return response()->json([
            'type'            => 'project_proposal',
            'chat_id'         => $record->id,
            'proyecto_id'     => $proyecto->id,
            'proyecto_nombre' => $proyecto->nombre,
            'data'            => $proposal,
        ]);
    }

    // ── Ollama: standard project creation (no active project) ─────────────────

    private function callOllamaAndCreate(
        string        $message,
        AiProjectChat $record,
        int           $userId,
    ): JsonResponse {
        try {
            $ochat    = new Ochat();
            $response = $ochat->send($message);
        } catch (\Throwable $e) {
            $record->update(['estado' => 'error', 'respuesta_ia' => $e->getMessage()]);
            Log::error('[ChatController] Ochat error', ['message' => $e->getMessage()]);
            return response()->json([
                'response' => 'Error al contactar la IA: ' . $e->getMessage(),
            ]);
        }

        $aiText = $response['response'] ?? '';
        if (empty($aiText)) {
            $record->update(['estado' => 'error']);
            return response()->json(['response' => 'La IA no devolvió respuesta.']);
        }

        $record->update(['respuesta_ia' => $aiText]);

        $cleaned = str_replace(['```json', '```'], '', $aiText);
        $data    = json_decode(trim($cleaned), true);

        if (!$data) {
            $record->update(['estado' => 'error']);
            return response()->json([
                'response' => 'La IA no devolvió un JSON válido.<br>'
                    . '<pre style="font-size:10px;color:#94a3b8;white-space:pre-wrap;margin-top:8px">'
                    . htmlspecialchars($aiText)
                    . '</pre>',
            ]);
        }

        if (isset($data['tipo']) && $data['tipo'] === 'chat') {
            $record->update(['estado' => 'aplicado']);
            return response()->json(['response' => $data['mensaje'] ?? 'Hola, soy tu asistente.']);
        }

        if (!isset($data['nombre'])) {
            $record->update(['estado' => 'error']);
            return response()->json(['response' => 'Faltan datos del proyecto en la respuesta de la IA.']);
        }

        return $this->persistNewProject($data, $record, $userId);
    }

    private function persistNewProject(array $data, AiProjectChat $record, int $userId): JsonResponse
    {
        try {
            DB::beginTransaction();

            $proyecto = Proyecto::create([
                'user_id'     => $userId,
                'nombre'      => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? 'Proyecto generado por IA.',
                'estado'      => 'activo',
                'fecha_inicio' => now(),
            ]);

            $reqCount = 0;
            foreach ((array) ($data['requirements'] ?? $data['requerimientos'] ?? []) as $req) {
                $proyecto->requirements()->create([
                    'titulo'      => $req['titulo'] ?? 'Requerimiento',
                    'descripcion' => $req['descripcion'] ?? '',
                    'tipo'        => $req['tipo'] ?? 'funcional',
                    'prioridad'   => $req['prioridad'] ?? 'media',
                ]);
                $reqCount++;
            }

            $taskCount = 0;
            $tasks     = $data['tasks'] ?? $data['tareas'] ?? [];
            if (!empty($tasks)) {
                $status = TaskStatus::first()
                    ?? TaskStatus::create(['nombre' => 'To Do', 'color' => '#808080', 'orden' => 1]);
                foreach ((array) $tasks as $task) {
                    $proyecto->tasks()->create([
                        'titulo'         => $task['titulo'] ?? 'Tarea',
                        'descripcion'    => $task['descripcion'] ?? '',
                        'task_status_id' => $status->id,
                    ]);
                    $taskCount++;
                }
            }

            DB::commit();
            $record->update(['proyecto_id' => $proyecto->id, 'estado' => 'aplicado']);

            $url = route('scrum-board.show', $proyecto);
            $msg = "<strong>¡Proyecto «{$proyecto->nombre}» creado!</strong><br><br>"
                . "✅ <strong>{$reqCount}</strong> requerimientos<br>"
                . "✅ <strong>{$taskCount}</strong> tareas<br><br>"
                . "<a href='{$url}' style='display:inline-block;background:rgba(99,102,241,0.85);color:#fff;font-size:12px;font-weight:700;padding:6px 14px;border-radius:8px;text-decoration:none'>Ir al Tablero Scrum →</a>";

            return response()->json(['response' => $msg]);

        } catch (\Exception $e) {
            DB::rollBack();
            $record->update(['estado' => 'error']);
            return response()->json(['response' => 'Error al guardar el proyecto: ' . $e->getMessage()]);
        }
    }
}
