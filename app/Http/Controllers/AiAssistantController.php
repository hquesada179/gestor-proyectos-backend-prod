<?php

namespace App\Http\Controllers;

use App\Models\AiProjectChat;
use App\Models\Proyecto;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Services\AiPromptBuilderService;
use App\Services\AiResponseParserService;
use App\Services\OllamaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiAssistantController extends Controller
{
    public function __construct(
        private readonly OllamaService           $ollama,
        private readonly AiPromptBuilderService  $promptBuilder,
        private readonly AiResponseParserService $parser,
    ) {}

    // ── Main view ─────────────────────────────────────────────────────────────

    public function index(): \Illuminate\View\View
    {
        $models       = $this->ollama->listModels();
        $defaultModel = config('services.ollama.model', 'gemma3');
        $ollamaOnline = $this->ollama->isReachable();
        $proyectos    = Proyecto::where('user_id', auth()->id())
            ->orderByDesc('updated_at')
            ->get(['id', 'nombre', 'estado', 'descripcion']);

        return view('ai.assistant', compact('models', 'defaultModel', 'ollamaOnline', 'proyectos'));
    }

    // ── Unified submit (create or improve) ───────────────────────────────────

    public function submit(Request $request): JsonResponse
    {
        $request->validate([
            'modo'           => ['required', 'string', 'in:crear,mejorar'],
            'prompt'         => ['required', 'string', 'min:10', 'max:3000'],
            'modelo'         => ['required', 'string', 'max:100'],
            'proyecto_id'    => ['nullable', 'integer'],
            'parent_chat_id' => ['nullable', 'integer'],
        ], [
            'prompt.required' => 'Escribe una descripción o instrucción.',
            'prompt.min'      => 'El prompt debe tener al menos 10 caracteres.',
            'modelo.required' => 'Selecciona un modelo.',
        ]);

        $modo         = $request->modo;
        $prompt       = trim($request->prompt);
        $modelo       = trim($request->modelo);
        $proyectoId   = $request->input('proyecto_id') ?: null;
        $parentChatId = $request->input('parent_chat_id') ?: null;
        $userId       = auth()->id();

        // Inherit project from parent if not explicitly provided
        if ($parentChatId && !$proyectoId) {
            $parentRecord = AiProjectChat::where('id', $parentChatId)
                ->where('user_id', $userId)
                ->value('proyecto_id');
            if ($parentRecord) {
                $proyectoId = $parentRecord;
            }
        }

        Log::info('AiAssistantController@submit', [
            'user_id' => $userId, 'modo' => $modo, 'modelo' => $modelo,
            'prompt_length' => strlen($prompt),
        ]);

        // Save to history (with parent link if continuing)
        $record = AiProjectChat::create([
            'user_id'        => $userId,
            'proyecto_id'    => $proyectoId,
            'parent_chat_id' => $parentChatId,
            'tipo_accion'    => $modo === 'crear' ? 'crear_proyecto' : 'editar_proyecto',
            'prompt_usuario' => $prompt,
            'estado'         => 'borrador',
        ]);

        return $modo === 'crear'
            ? $this->handleCreate($prompt, $modelo, $record)
            : $this->handleImprove($prompt, $modelo, $proyectoId, $userId, $record, $parentChatId);
    }

    // ── Apply (confirm or discard) ────────────────────────────────────────────

    public function apply(Request $request): JsonResponse
    {
        $request->validate([
            'chat_id' => ['required', 'integer'],
            'action'  => ['required', 'string', 'in:confirm,discard'],
        ]);

        $record = AiProjectChat::where('id', $request->chat_id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$record) {
            return response()->json(['ok' => false, 'error' => 'Registro no encontrado.']);
        }

        if ($request->action === 'discard') {
            $record->update(['estado' => 'descartado']);
            return response()->json(['ok' => true, 'message' => 'Propuesta descartada.']);
        }

        $data = $record->datos_detectados ?? [];
        $tipo = $data['_tipo'] ?? 'create';

        return $tipo === 'improve'
            ? $this->applyImprove($data, $record)
            : $this->applyCreate($data, $record);
    }

    // ── History ───────────────────────────────────────────────────────────────

    // ── Single history record detail ──────────────────────────────────────────

    public function historyDetail(int $id): JsonResponse
    {
        $record = AiProjectChat::where('id', $id)
            ->where('user_id', auth()->id())
            ->with('proyecto:id,nombre,estado,descripcion')
            ->first();

        if (!$record) {
            return response()->json(['ok' => false, 'error' => 'Registro no encontrado.'], 404);
        }

        return response()->json([
            'ok'     => true,
            'record' => [
                'id'               => $record->id,
                'tipo_accion'      => $record->tipo_accion,
                'modo'             => $record->tipo_accion === 'crear_proyecto' ? 'crear' : 'mejorar',
                'modo_label'       => $record->tipo_accion === 'crear_proyecto' ? 'Crear proyecto' : 'Mejorar proyecto',
                'prompt_usuario'   => $record->prompt_usuario,
                'respuesta_ia'     => $record->respuesta_ia,
                'datos_detectados' => $record->datos_detectados,
                'estado'           => $record->estado,
                'proyecto_id'      => $record->proyecto_id,
                'proyecto_nombre'  => $record->proyecto?->nombre,
                'proyecto_estado'  => $record->proyecto?->estado,
                'proyecto_url'     => $record->proyecto_id ? route('proyectos.show', $record->proyecto_id) : null,
                'parent_chat_id'   => $record->parent_chat_id,
                'created_at_full'  => $record->created_at->format('d/m/Y H:i'),
                'created_at_human' => $record->created_at->diffForHumans(),
            ],
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $proyectoId = $request->query('proyecto_id');

        $query = AiProjectChat::where('user_id', auth()->id())
            ->with('proyecto:id,nombre');

        if ($proyectoId) {
            $query->where('proyecto_id', $proyectoId);
        }

        $records = $query->latest()->take(50)->get();

        return response()->json([
            'history' => $records->map(fn(AiProjectChat $r) => [
                'id'               => $r->id,
                'tipo_accion'      => $r->tipo_accion,
                'modo'             => $r->tipo_accion === 'crear_proyecto' ? 'Crear proyecto' : 'Mejorar proyecto',
                'prompt_usuario'   => Str::limit($r->prompt_usuario, 100),
                'prompt_completo'  => $r->prompt_usuario,
                'estado'           => $r->estado,
                'proyecto_id'      => $r->proyecto_id,
                'proyecto_nombre'  => $r->proyecto?->nombre,
                'proyecto_url'     => $r->proyecto_id ? route('proyectos.show', $r->proyecto_id) : null,
                'created_at_human' => $r->created_at->diffForHumans(),
                'created_at_full'  => $r->created_at->format('d/m/Y H:i'),
            ]),
        ]);
    }

    public function deleteHistoryItem(Request $request, int $id): JsonResponse
    {
        $record = AiProjectChat::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$record) {
            return response()->json(['ok' => false, 'error' => 'Registro no encontrado.'], 404);
        }

        $record->delete();
        return response()->json(['ok' => true]);
    }

    public function clearHistory(Request $request): JsonResponse
    {
        $proyectoId = $request->input('proyecto_id');
        $query      = AiProjectChat::where('user_id', auth()->id());

        if ($proyectoId) {
            $query->where('proyecto_id', $proyectoId);
        }

        $deleted = $query->delete();
        return response()->json(['ok' => true, 'deleted' => $deleted]);
    }

    // ── Legacy endpoints (kept intact) ───────────────────────────────────────

    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'descripcion' => ['required', 'string', 'min:20', 'max:3000'],
            'modelo'      => ['required', 'string', 'max:100'],
        ]);

        $prompt = $this->buildClassicPrompt($request->string('descripcion')->toString());
        $result = $this->ollama->generate($prompt, $request->string('modelo')->toString());

        return response()->json($result);
    }

    public function guided(): \Illuminate\View\View
    {
        $models       = $this->ollama->listModels();
        $defaultModel = config('services.ollama.model', 'gemma3');
        $ollamaOnline = $this->ollama->isReachable();

        return view('ai.guided', compact('models', 'defaultModel', 'ollamaOnline'));
    }

    public function interpret(Request $request): JsonResponse
    {
        $request->validate([
            'idea'   => ['required', 'string', 'min:10', 'max:2000'],
            'modelo' => ['required', 'string', 'max:100'],
        ]);

        $prompt = $this->promptBuilder->interpretationPrompt(
            $request->string('idea')->toString()
        );
        $result = $this->ollama->generate($prompt, $request->string('modelo')->toString());

        if (!$result['ok']) {
            return response()->json(['ok' => false, 'error' => $result['error'] ?? ''], 422);
        }

        $parsed = $this->parser->parseInterpretation($result['data']);
        if (!$parsed['ok']) {
            return response()->json(['ok' => false, 'error' => $parsed['error']], 422);
        }

        return response()->json(['ok' => true, 'data' => $parsed['data']]);
    }

    // ── Private: create mode ──────────────────────────────────────────────────

    private function handleCreate(string $prompt, string $modelo, AiProjectChat $record): JsonResponse
    {
        $result = $this->ollama->generate($this->promptBuilder->createProjectPrompt($prompt), $modelo);

        if (!$result['ok']) {
            $record->update(['estado' => 'error', 'respuesta_ia' => $result['error'] ?? '']);
            return response()->json([
                'ok'    => false,
                'error' => $result['error'] ?? 'Ollama no respondió.',
                'raw'   => $result['raw'] ?? null,
            ], 422);
        }

        $raw  = $result['data'];
        $data = $this->normalizeCreateProposal($raw);
        $data['_tipo'] = 'create';

        $record->update([
            'respuesta_ia'     => json_encode($raw, JSON_UNESCAPED_UNICODE),
            'datos_detectados' => $data,
        ]);

        return response()->json([
            'ok'      => true,
            'type'    => 'create_proposal',
            'chat_id' => $record->id,
            'data'    => $data,
        ]);
    }

    private function handleImprove(
        string        $prompt,
        string        $modelo,
        ?int          $proyectoId,
        int           $userId,
        AiProjectChat $record,
        ?int          $parentChatId = null,
    ): JsonResponse {
        if (!$proyectoId) {
            $record->update(['estado' => 'descartado']);
            return response()->json([
                'ok'    => false,
                'error' => 'Selecciona un proyecto activo para usar el modo "Mejorar proyecto".',
            ], 422);
        }

        $proyecto = Proyecto::where('id', $proyectoId)->where('user_id', $userId)->first();

        if (!$proyecto) {
            $record->update(['estado' => 'descartado']);
            return response()->json(['ok' => false, 'error' => 'Proyecto no encontrado.'], 422);
        }

        $context = $this->loadProjectContext($proyecto);

        // If continuing from a parent chat, enrich the prompt with previous context
        $finalPrompt = $prompt;
        if ($parentChatId) {
            $parentRecord = AiProjectChat::where('id', $parentChatId)
                ->where('user_id', $userId)
                ->first(['id', 'prompt_usuario', 'datos_detectados']);

            if ($parentRecord) {
                $finalPrompt = $this->buildContinuationPrompt($parentRecord, $prompt);
            }
        }

        $result = $this->ollama->generate(
            $this->promptBuilder->improveProjectPrompt(
                $finalPrompt,
                $proyecto->nombre,
                $proyecto->descripcion ?? '',
                $context
            ),
            $modelo
        );

        if (!$result['ok']) {
            $record->update(['estado' => 'error', 'respuesta_ia' => $result['error'] ?? '']);
            return response()->json([
                'ok'    => false,
                'error' => $result['error'] ?? 'Ollama no respondió.',
            ], 422);
        }

        $raw  = $result['data'];
        $data = $this->normalizeImproveProposal($raw);
        $data['_tipo']        = 'improve';
        $data['_proyecto_id'] = $proyecto->id;

        $record->update([
            'respuesta_ia'     => json_encode($raw, JSON_UNESCAPED_UNICODE),
            'datos_detectados' => $data,
        ]);

        return response()->json([
            'ok'              => true,
            'type'            => 'improve_proposal',
            'chat_id'         => $record->id,
            'proyecto_id'     => $proyecto->id,
            'proyecto_nombre' => $proyecto->nombre,
            'data'            => $data,
        ]);
    }

    // ── Private: apply create ─────────────────────────────────────────────────

    private function applyCreate(array $data, AiProjectChat $record): JsonResponse
    {
        if (empty($data['nombre'])) {
            return response()->json(['ok' => false, 'error' => 'La propuesta no tiene nombre de proyecto.']);
        }

        try {
            DB::beginTransaction();

            $proyecto = Proyecto::create([
                'user_id'     => auth()->id(),
                'nombre'      => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? '',
                'estado'      => 'activo',
                'fecha_inicio' => now(),
            ]);

            $counts = ['requerimientos' => 0, 'tareas' => 0, 'sprints' => 0, 'insumos' => 0];

            foreach ($data['requerimientos'] ?? [] as $i => $req) {
                if (empty($req['titulo'])) continue;
                $tipo = in_array($req['tipo'] ?? '', ['funcional', 'no_funcional']) ? $req['tipo'] : 'funcional';
                $proyecto->requirements()->create([
                    'codigo'      => ($tipo === 'no_funcional' ? 'RNF-' : 'RF-') . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                    'titulo'      => $req['titulo'],
                    'descripcion' => $req['descripcion'] ?? '',
                    'tipo'        => $tipo,
                    'prioridad'   => in_array($req['prioridad'] ?? '', ['alta', 'media', 'baja']) ? $req['prioridad'] : 'media',
                ]);
                $counts['requerimientos']++;
            }

            $status = TaskStatus::orderBy('orden')->first()
                ?? TaskStatus::create(['nombre' => 'Pendiente', 'color' => '#6B7280', 'orden' => 1]);

            foreach ($data['tareas'] ?? [] as $task) {
                if (empty($task['titulo'])) continue;
                $proyecto->tasks()->create([
                    'titulo'         => $task['titulo'],
                    'descripcion'    => $task['descripcion'] ?? '',
                    'task_status_id' => $status->id,
                ]);
                $counts['tareas']++;
            }

            foreach ($data['sprints'] ?? [] as $sprint) {
                if (empty($sprint['nombre'])) continue;
                $semanas = max(1, (int) ($sprint['semanas'] ?? $sprint['duracion_semanas'] ?? 2));
                $proyecto->sprints()->create([
                    'nombre'       => $sprint['nombre'],
                    'objetivo'     => $sprint['objetivo'] ?? '',
                    'fecha_inicio' => now()->toDateString(),
                    'fecha_fin'    => now()->addWeeks($semanas)->toDateString(),
                    'estado'       => 'planificado',
                ]);
                $counts['sprints']++;
            }

            foreach ($data['insumos'] ?? [] as $insumo) {
                $titulo = $insumo['titulo'] ?? $insumo['nombre'] ?? '';
                if (empty($titulo)) continue;
                $proyecto->inputs()->create([
                    'tipo'      => $insumo['tipo'] ?? 'otro',
                    'titulo'    => $titulo,
                    'contenido' => $insumo['contenido'] ?? $insumo['descripcion'] ?? '',
                ]);
                $counts['insumos']++;
            }

            DB::commit();
            $record->update(['proyecto_id' => $proyecto->id, 'estado' => 'aplicado']);

            Log::info('AiAssistantController@applyCreate: project created', [
                'proyecto_id' => $proyecto->id, 'counts' => $counts,
            ]);

            return response()->json([
                'ok'      => true,
                'message' => "Proyecto «{$proyecto->nombre}» creado con éxito.",
                'counts'  => $counts,
                'url'     => route('proyectos.show', $proyecto),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $record->update(['estado' => 'error']);
            Log::error('AiAssistantController@applyCreate: error', ['message' => $e->getMessage()]);
            return response()->json(['ok' => false, 'error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    // ── Private: apply improve ────────────────────────────────────────────────

    private function applyImprove(array $data, AiProjectChat $record): JsonResponse
    {
        $proyectoId = $data['_proyecto_id'] ?? null;

        if (!$proyectoId) {
            return response()->json(['ok' => false, 'error' => 'No se encontró el proyecto en la propuesta.']);
        }

        $proyecto = Proyecto::where('id', $proyectoId)->where('user_id', auth()->id())->first();

        if (!$proyecto) {
            return response()->json(['ok' => false, 'error' => 'Proyecto no encontrado o sin permiso.']);
        }

        try {
            DB::beginTransaction();

            $counts   = ['reqs_new' => 0, 'tasks_new' => 0, 'tasks_updated' => 0, 'sprints' => 0, 'insumos' => 0];
            $warnings = [];

            // New requirements
            foreach ($data['requerimientos_nuevos'] ?? [] as $i => $req) {
                if (empty($req['titulo'])) continue;
                $tipo = in_array($req['tipo'] ?? '', ['funcional', 'no_funcional']) ? $req['tipo'] : 'funcional';
                $proyecto->requirements()->create([
                    'titulo'      => $req['titulo'],
                    'descripcion' => $req['descripcion'] ?? '',
                    'tipo'        => $tipo,
                    'prioridad'   => in_array($req['prioridad'] ?? '', ['alta', 'media', 'baja']) ? $req['prioridad'] : 'media',
                ]);
                $counts['reqs_new']++;
            }

            // New tasks
            $status = TaskStatus::orderBy('orden')->first()
                ?? TaskStatus::create(['nombre' => 'Pendiente', 'color' => '#6B7280', 'orden' => 1]);

            foreach ($data['tareas_nuevas'] ?? [] as $task) {
                if (empty($task['titulo'])) continue;
                $proyecto->tasks()->create([
                    'titulo'         => $task['titulo'],
                    'descripcion'    => $task['descripcion'] ?? '',
                    'task_status_id' => $status->id,
                ]);
                $counts['tasks_new']++;
            }

            // Update existing tasks (title + description, validated by proyecto_id)
            $projectTasks = $proyecto->tasks()->get(['id', 'titulo', 'descripcion']);
            $taskIds      = $projectTasks->pluck('id')->map(fn($id) => (int) $id)->toArray();

            foreach ($data['actualizaciones_tareas'] ?? [] as $upd) {
                $taskId = (int) ($upd['id'] ?? 0);

                // Fallback: find by title if ID is wrong/missing
                if (!in_array($taskId, $taskIds, true)) {
                    $needle  = mb_strtolower(trim($upd['titulo_actual'] ?? ''));
                    $matched = $projectTasks->first(fn($t) => mb_strtolower(trim($t->titulo)) === $needle);
                    $taskId  = $matched?->id ?? 0;
                }

                if (!$taskId || !in_array($taskId, $taskIds, true)) {
                    $warnings[] = "Tarea «{$upd['titulo_actual']}»: no encontrada en el proyecto.";
                    continue;
                }

                Task::where('id', $taskId)->where('proyecto_id', $proyecto->id)->update([
                    'titulo'      => $upd['titulo_nuevo']      ?? Task::find($taskId)->titulo,
                    'descripcion' => $upd['descripcion_nueva'] ?? Task::find($taskId)->descripcion,
                ]);
                $counts['tasks_updated']++;
            }

            // New sprints
            foreach ($data['sprints_nuevos'] ?? [] as $sprint) {
                if (empty($sprint['nombre'])) continue;
                $semanas = max(1, (int) ($sprint['semanas'] ?? 2));
                $proyecto->sprints()->create([
                    'nombre'       => $sprint['nombre'],
                    'objetivo'     => $sprint['objetivo'] ?? '',
                    'fecha_inicio' => now()->toDateString(),
                    'fecha_fin'    => now()->addWeeks($semanas)->toDateString(),
                    'estado'       => 'planificado',
                ]);
                $counts['sprints']++;
            }

            // New inputs
            foreach ($data['insumos_nuevos'] ?? [] as $insumo) {
                $titulo = $insumo['titulo'] ?? $insumo['nombre'] ?? '';
                if (empty($titulo)) continue;
                $proyecto->inputs()->create([
                    'tipo'      => $insumo['tipo'] ?? 'otro',
                    'titulo'    => $titulo,
                    'contenido' => $insumo['contenido'] ?? '',
                ]);
                $counts['insumos']++;
            }

            DB::commit();
            $record->update(['estado' => 'aplicado']);

            return response()->json([
                'ok'       => true,
                'message'  => "Cambios aplicados al proyecto «{$proyecto->nombre}».",
                'counts'   => $counts,
                'warnings' => $warnings,
                'url'      => route('proyectos.show', $proyecto),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $record->update(['estado' => 'error']);
            return response()->json(['ok' => false, 'error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function buildContinuationPrompt(AiProjectChat $parent, string $newInstruction): string
    {
        $prevSummary = '';
        $data        = $parent->datos_detectados ?? [];

        if (!empty($data['resumen'])) {
            $prevSummary = "Contexto: en una solicitud anterior el usuario pidió \"{$parent->prompt_usuario}\" y la IA propuso: {$data['resumen']}.";
        } elseif (!empty($data['nombre'])) {
            $prevSummary = "Contexto: en una solicitud anterior el usuario describió: \"{$parent->prompt_usuario}\".";
        }

        return $prevSummary
            ? "{$prevSummary}\n\nNueva instrucción del usuario: {$newInstruction}"
            : $newInstruction;
    }

    private function loadProjectContext(Proyecto $proyecto): array
    {
        return [
            'requerimientos' => $proyecto->requirements()
                ->get(['id', 'titulo', 'tipo'])
                ->map(fn($r) => ['id' => $r->id, 'titulo' => $r->titulo, 'tipo' => $r->tipo])
                ->toArray(),
            'tareas' => $proyecto->tasks()
                ->with('status:id,nombre')
                ->get(['id', 'titulo', 'task_status_id'])
                ->map(fn($t) => ['id' => $t->id, 'titulo' => $t->titulo, 'estado' => $t->status?->nombre ?? 'pendiente'])
                ->toArray(),
            'sprints' => $proyecto->sprints()
                ->get(['id', 'nombre'])
                ->map(fn($s) => ['id' => $s->id, 'nombre' => $s->nombre])
                ->toArray(),
            'insumos' => $proyecto->inputs()
                ->get(['id', 'titulo', 'tipo'])
                ->map(fn($i) => ['id' => $i->id, 'titulo' => $i->titulo, 'tipo' => $i->tipo])
                ->toArray(),
        ];
    }

    private function normalizeCreateProposal(array $data): array
    {
        // Handle old format (sprint_sugerido) and new (sprints array)
        $sprints = $data['sprints'] ?? [];
        if (empty($sprints) && isset($data['sprint_sugerido'])) {
            $s       = $data['sprint_sugerido'];
            $sprints = [[
                'nombre'   => $s['nombre'] ?? 'Sprint 1',
                'objetivo' => $s['objetivo'] ?? '',
                'semanas'  => $s['duracion_semanas'] ?? 2,
            ]];
        }

        // Merge reqs from old format (requerimientos_funcionales + requerimientos_no_funcionales)
        $reqs = $data['requerimientos'] ?? [];
        if (empty($reqs)) {
            $func    = $data['requerimientos_funcionales']    ?? [];
            $nofunc  = $data['requerimientos_no_funcionales'] ?? [];
            foreach ($func   as $r) { $r['tipo']   = 'funcional';    $reqs[] = $r; }
            foreach ($nofunc as $r) { $r['tipo']   = 'no_funcional'; $reqs[] = $r; }
        }

        $tareas = $data['tareas']
            ?? $data['tasks']
            ?? $data['tareas_sugeridas']
            ?? [];

        return [
            'nombre'      => $data['nombre'] ?? $data['name'] ?? 'Proyecto sin nombre',
            'descripcion' => $data['descripcion'] ?? $data['description'] ?? $data['resumen'] ?? '',
            'requerimientos' => $reqs,
            'tareas'      => $tareas,
            'sprints'     => $sprints,
            'insumos'     => $data['insumos'] ?? $data['inputs'] ?? [],
        ];
    }

    private function normalizeImproveProposal(array $data): array
    {
        return [
            'resumen'               => $data['resumen'] ?? $data['summary'] ?? 'Propuesta de mejora generada por IA',
            'requerimientos_nuevos' => $data['requerimientos_nuevos'] ?? $data['requerimientos'] ?? $data['requirements'] ?? [],
            'tareas_nuevas'         => $data['tareas_nuevas'] ?? $data['tareas'] ?? $data['tasks'] ?? [],
            'actualizaciones_tareas' => $data['actualizaciones_tareas'] ?? $data['actualizaciones'] ?? $data['task_updates'] ?? [],
            'sprints_nuevos'        => $data['sprints_nuevos'] ?? $data['sprints'] ?? [],
            'insumos_nuevos'        => $data['insumos_nuevos'] ?? $data['insumos'] ?? [],
        ];
    }

    // ── Classic prompt (legacy, keep for /asistente-ia/generar) ──────────────

    private function buildClassicPrompt(string $descripcion): string
    {
        return <<<PROMPT
Eres un analista de software experto en metodologías ágiles y Scrum.
Analiza la siguiente descripción de proyecto y genera un borrador estructurado de análisis de requerimientos.

DESCRIPCIÓN DEL PROYECTO:
{$descripcion}

Responde ÚNICAMENTE con un objeto JSON válido sin texto adicional.
{
  "resumen": "Descripción resumida del proyecto en 1-2 oraciones",
  "requerimientos_funcionales": [
    {"codigo": "RF-001", "titulo": "Título breve", "descripcion": "Descripción detallada", "prioridad": "alta"}
  ],
  "requerimientos_no_funcionales": [
    {"codigo": "RNF-001", "titulo": "Título breve", "descripcion": "Descripción detallada", "prioridad": "media"}
  ],
  "historias_usuario": [
    {"titulo": "Título", "como_usuario": "tipo", "quiero": "acción", "para_poder": "beneficio", "prioridad": "alta"}
  ],
  "tareas_sugeridas": [
    {"titulo": "Título de la tarea", "descripcion": "Descripción", "prioridad": "alta"}
  ],
  "sprint_sugerido": {
    "nombre": "Sprint 1 - Nombre corto",
    "objetivo": "Objetivo en 1 oración",
    "duracion_semanas": 2,
    "tareas_incluidas": ["Tarea 1", "Tarea 2"]
  }
}

REGLAS: prioridad = alta/media/baja. RF-001... RNF-001... Todo en español. Solo el JSON.
PROMPT;
    }
}
