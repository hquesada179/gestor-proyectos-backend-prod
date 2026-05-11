<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiProjectChat;
use App\Models\Proyecto;
use App\Models\TaskStatus;
use App\Services\Ai\AiAssistantService;
use App\Services\Ai\AiCreditService;
use App\Services\Ai\AiProposalEditorService;
use App\Services\AiPromptBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiProjectApiController extends Controller
{
    public function __construct(
        private readonly AiAssistantService      $aiAssistant,
        private readonly AiCreditService         $aiCredits,
        private readonly AiProposalEditorService $proposalEditor,
        private readonly AiPromptBuilderService  $promptBuilder,
    ) {}

    /**
     * POST /api/ai/projects/generate
     * Genera un borrador estructurado de proyecto usando IA.
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'min:5', 'max:3000'],
            'mode'   => ['nullable', 'string', 'max:50'],
            'model'  => ['nullable', 'string', 'max:100'],
        ]);

        $userId = $request->user()->id;
        $prompt = trim($validated['prompt']);
        $model  = $validated['model'] ?? $this->aiAssistant->defaultModel();

        $creditCheck = $this->aiCredits->canUseAi($userId, 'generar_proyecto');
        if (!$creditCheck['allowed']) {
            return response()->json(['ok' => false, 'error' => $creditCheck['message']], 402);
        }

        $record = AiProjectChat::create([
            'user_id'        => $userId,
            'tipo_accion'    => 'crear_proyecto',
            'prompt_usuario' => $prompt,
            'estado'         => 'borrador',
        ]);

        $usageLog = $this->aiCredits->createPendingLog($userId, 'generar_proyecto');

        $result = $this->aiAssistant->generate($this->promptBuilder->createProjectPrompt($prompt), $model);

        if (!$result['ok']) {
            $record->update(['estado' => 'error']);
            $this->aiCredits->markFailed($usageLog, $result['error'] ?? 'AI failed');
            return response()->json([
                'ok'    => false,
                'error' => $result['error'] ?? 'El proveedor de IA no respondió.',
            ], 500);
        }

        $this->aiCredits->chargeCredits($usageLog, $creditCheck['credits'] ?? 0);

        $proposal           = $this->normalizeCreateProposal($result['data']);
        $proposal['_tipo']  = 'create';

        $record->update([
            'respuesta_ia'     => json_encode($result['data'], JSON_UNESCAPED_UNICODE),
            'datos_detectados' => $proposal,
        ]);

        $balance = $this->aiCredits->getBalance($userId);

        return response()->json([
            'ok'      => true,
            'message' => 'Propuesta generada correctamente',
            'chat_id' => $record->id,
            'proposal' => $proposal,
            'credits' => [
                'available' => $balance['credits_available'],
                'used'      => $balance['credits_used'],
                'total'     => $balance['credits_total'],
            ],
        ], 201);
    }

    /**
     * POST /api/ai/projects/regenerate
     * Re-genera la propuesta con el mismo flujo que generate.
     */
    public function regenerate(Request $request): JsonResponse
    {
        return $this->generate($request);
    }

    /**
     * POST /api/ai/projects/adjust
     * Ajusta una propuesta existente con una instrucción adicional.
     */
    public function adjust(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'proposal'    => ['required', 'array'],
            'instruction' => ['required', 'string', 'min:3', 'max:2000'],
            'model'       => ['nullable', 'string', 'max:100'],
        ]);

        $userId      = $request->user()->id;
        $creditCheck = $this->aiCredits->canUseAi($userId, 'refinar_propuesta');
        if (!$creditCheck['allowed']) {
            return response()->json(['ok' => false, 'error' => $creditCheck['message']], 402);
        }

        $usageLog = $this->aiCredits->createPendingLog($userId, 'refinar_propuesta');

        $result = $this->proposalEditor->refineProposal(
            $validated['proposal'],
            $validated['instruction'],
            $validated['model'] ?? null
        );

        if (!$result['ok']) {
            $this->aiCredits->markFailed($usageLog, $result['error'] ?? 'AI failed');
            return response()->json([
                'ok'    => false,
                'error' => $result['error'] ?? 'No se pudo ajustar la propuesta.',
            ], 422);
        }

        $this->aiCredits->chargeCredits($usageLog, $creditCheck['credits'] ?? 0);

        $refined           = $this->normalizeCreateProposal($result['data']);
        $refined['_tipo']  = $validated['proposal']['_tipo'] ?? 'create';
        if (!empty($validated['proposal']['_chat_id'])) {
            $refined['_chat_id'] = $validated['proposal']['_chat_id'];
        }

        return response()->json([
            'ok'       => true,
            'message'  => 'Propuesta ajustada correctamente',
            'proposal' => $refined,
        ]);
    }

    /**
     * POST /api/ai/projects/confirm
     * Guarda la propuesta como proyecto real en la base de datos.
     */
    public function confirm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'proposal' => ['required', 'array'],
            'chat_id'  => ['nullable', 'integer'],
        ]);

        $userId = $request->user()->id;
        $data   = $this->normalizeCreateProposal($validated['proposal']);

        $error = $this->validateCreateDraft($data);
        if ($error) {
            return response()->json(['ok' => false, 'error' => $error], 422);
        }

        $record = null;
        if (!empty($validated['chat_id'])) {
            $record = AiProjectChat::where('id', $validated['chat_id'])
                ->where('user_id', $userId)
                ->first();
        }

        try {
            DB::beginTransaction();

            $proyecto = Proyecto::create([
                'user_id'      => $userId,
                'nombre'       => $data['nombre'],
                'descripcion'  => $data['descripcion'] ?? '',
                'estado'       => 'activo',
                'fecha_inicio' => now()->toDateString(),
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

            if ($record) {
                $record->update(['proyecto_id' => $proyecto->id, 'estado' => 'aplicado']);
            }

            Log::info('AiProjectApiController@confirm: project created', [
                'user_id' => $userId, 'proyecto_id' => $proyecto->id, 'counts' => $counts,
            ]);

            return response()->json([
                'ok'      => true,
                'message' => "Proyecto «{$proyecto->nombre}» creado con éxito.",
                'project' => [
                    'id'          => $proyecto->id,
                    'nombre'      => $proyecto->nombre,
                    'descripcion' => $proyecto->descripcion,
                    'estado'      => $proyecto->estado,
                    'fecha_inicio'=> $proyecto->fecha_inicio?->toDateString(),
                ],
                'counts' => $counts,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            if ($record) $record->update(['estado' => 'error']);
            Log::error('AiProjectApiController@confirm: error', ['message' => $e->getMessage()]);
            return response()->json(['ok' => false, 'error' => 'Error al guardar el proyecto.'], 500);
        }
    }

    /**
     * GET /api/ai/history
     * Devuelve el historial de interacciones IA del usuario autenticado.
     */
    public function history(Request $request): JsonResponse
    {
        $records = AiProjectChat::where('user_id', $request->user()->id)
            ->with('proyecto:id,nombre')
            ->latest()
            ->take(50)
            ->get();

        return response()->json([
            'ok'      => true,
            'history' => $records
                ->map(fn(AiProjectChat $record) => $this->formatHistoryRecord($record))
                ->values(),
        ]);
    }

    /**
     * GET /api/ai/history/{id}
     * Devuelve el detalle completo de un historial IA del usuario autenticado.
     */
    public function historyDetail(Request $request, int $id): JsonResponse
    {
        $record = AiProjectChat::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('proyecto:id,nombre,estado,descripcion')
            ->first();

        if (!$record) {
            return response()->json([
                'ok'    => false,
                'error' => 'Historial IA no encontrado.',
            ], 404);
        }

        $payload = $this->formatHistoryRecord($record, true);

        return response()->json([
            'ok'     => true,
            'record' => $payload,
        ]);
    }

    private function formatHistoryRecord(AiProjectChat $record, bool $includeRawResponse = false): array
    {
        $proposal = $this->extractStoredProposal($record);
        $response = $proposal ?? $this->decodeJsonValue($record->respuesta_ia);
        $credits  = $this->creditsForHistoryAction($record->tipo_accion);

        $payload = [
            'id'                => $record->id,
            'chat_id'           => $record->id,
            'session_id'        => (string) $record->id,
            'action'            => $record->tipo_accion,
            'tipo_accion'       => $record->tipo_accion,
            'mode'              => $record->tipo_accion === 'crear_proyecto' ? 'create' : 'improve',
            'modo'              => $record->tipo_accion === 'crear_proyecto' ? 'Crear proyecto' : 'Mejorar proyecto',
            'status'            => $record->estado,
            'estado'            => $record->estado,
            'prompt'            => $record->prompt_usuario,
            'prompt_usuario'    => $record->prompt_usuario,
            'prompt_resumen'    => Str::limit($record->prompt_usuario, 100),
            'credits_used'      => $credits,
            'credits_cost'      => $credits,
            'project_id'        => $record->proyecto_id,
            'proyecto_id'       => $record->proyecto_id,
            'project_name'      => $record->proyecto?->nombre,
            'proyecto_nombre'   => $record->proyecto?->nombre,
            'created_at'        => $record->created_at?->toISOString(),
            'created_at_full'   => $record->created_at?->format('d/m/Y H:i'),
            'created_at_human'  => $record->created_at?->diffForHumans(),
            'has_proposal'      => $proposal !== null,
            'proposal_message'  => $proposal === null
                ? 'Este historial no tiene propuesta completa registrada.'
                : null,

            // Aliases consumed by Android and kept intentionally redundant.
            'proposal'          => $proposal,
            'propuesta'         => $proposal,
            'payload'           => $proposal,
            'data'              => $proposal,
            'result'            => $proposal,
            'resultado'         => $proposal,
            'draft'             => $proposal,
            'borrador'          => $proposal,
            'response'          => $response,
            'respuesta_ia'      => $response,
        ];

        if ($includeRawResponse) {
            $payload['raw_response'] = $record->respuesta_ia;
            $payload['datos_detectados'] = $record->datos_detectados;
            $payload['parent_chat_id'] = $record->parent_chat_id;
            $payload['project'] = $record->proyecto ? [
                'id'          => $record->proyecto->id,
                'name'        => $record->proyecto->nombre,
                'nombre'      => $record->proyecto->nombre,
                'status'      => $record->proyecto->estado,
                'estado'      => $record->proyecto->estado,
                'description' => $record->proyecto->descripcion,
                'descripcion' => $record->proyecto->descripcion,
            ] : null;
        }

        return $payload;
    }

    private function extractStoredProposal(AiProjectChat $record): ?array
    {
        $stored = $record->datos_detectados;

        if (is_string($stored)) {
            $stored = $this->decodeJsonValue($stored);
        }

        if (!is_array($stored) || empty($stored)) {
            $stored = $this->decodeJsonValue($record->respuesta_ia);
        }

        if (!is_array($stored) || empty($stored)) {
            return null;
        }

        return $this->withProposalAliases($stored, $record);
    }

    private function withProposalAliases(array $data, AiProjectChat $record): array
    {
        $proposal = $data;

        if (isset($proposal['project']) && is_array($proposal['project'])) {
            $proposal['nombre'] = $proposal['nombre']
                ?? $proposal['project']['nombre']
                ?? $proposal['project']['name']
                ?? $proposal['project']['title']
                ?? null;
            $proposal['descripcion'] = $proposal['descripcion']
                ?? $proposal['project']['descripcion']
                ?? $proposal['project']['description']
                ?? null;
        }

        if (
            empty($proposal['requerimientos'])
            && (
                !empty($proposal['requerimientos_funcionales'])
                || !empty($proposal['requerimientos_no_funcionales'])
                || isset($proposal['sprint_sugerido'])
            )
        ) {
            $proposal = array_replace($proposal, $this->normalizeCreateProposal($proposal));
        }

        $requirements = $proposal['requirements']
            ?? $proposal['requerimientos']
            ?? $proposal['requerimientos_nuevos']
            ?? [];
        $tasks = $proposal['tasks']
            ?? $proposal['tareas']
            ?? $proposal['tareas_nuevas']
            ?? $proposal['tareas_sugeridas']
            ?? [];
        $sprints = $proposal['sprints']
            ?? $proposal['sprints_nuevos']
            ?? [];
        $inputs = $proposal['inputs']
            ?? $proposal['insumos']
            ?? $proposal['insumos_nuevos']
            ?? [];

        $title = $proposal['title']
            ?? $proposal['nombre']
            ?? $proposal['name']
            ?? $record->proyecto?->nombre
            ?? 'Proyecto sin nombre';
        $description = $proposal['description']
            ?? $proposal['descripcion']
            ?? $proposal['resumen']
            ?? $record->proyecto?->descripcion
            ?? '';

        return array_replace($proposal, [
            '_chat_id'       => $record->id,
            '_tipo'          => $proposal['_tipo'] ?? ($record->tipo_accion === 'crear_proyecto' ? 'create' : 'improve'),
            '_proyecto_id'   => $proposal['_proyecto_id'] ?? $record->proyecto_id,
            'title'          => $title,
            'name'           => $proposal['name'] ?? $title,
            'nombre'         => $proposal['nombre'] ?? $title,
            'description'    => $description,
            'descripcion'    => $proposal['descripcion'] ?? $description,
            'requirements'   => is_array($requirements) ? array_values($requirements) : [],
            'requerimientos' => is_array($requirements) ? array_values($requirements) : [],
            'tasks'          => is_array($tasks) ? array_values($tasks) : [],
            'tareas'         => is_array($tasks) ? array_values($tasks) : [],
            'sprints'        => is_array($sprints) ? array_values($sprints) : [],
            'inputs'         => is_array($inputs) ? array_values($inputs) : [],
            'insumos'        => is_array($inputs) ? array_values($inputs) : [],
        ]);
    }

    private function decodeJsonValue(?string $value): mixed
    {
        if (blank($value)) {
            return null;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    private function creditsForHistoryAction(?string $action): int
    {
        return match ($action) {
            'crear_proyecto'  => 10,
            'editar_proyecto' => 8,
            default           => 0,
        };
    }

    private function normalizeCreateProposal(array $data): array
    {
        $sprints = $data['sprints'] ?? [];
        if (empty($sprints) && isset($data['sprint_sugerido'])) {
            $s = $data['sprint_sugerido'];
            $sprints = [['nombre' => $s['nombre'] ?? 'Sprint 1', 'objetivo' => $s['objetivo'] ?? '', 'semanas' => $s['duracion_semanas'] ?? 2]];
        }

        $reqs = $data['requerimientos'] ?? [];
        if (empty($reqs)) {
            foreach ($data['requerimientos_funcionales'] ?? [] as $r) { $r['tipo'] = 'funcional'; $reqs[] = $r; }
            foreach ($data['requerimientos_no_funcionales'] ?? [] as $r) { $r['tipo'] = 'no_funcional'; $reqs[] = $r; }
        }

        return [
            'nombre'         => $data['nombre'] ?? $data['name'] ?? 'Proyecto sin nombre',
            'descripcion'    => $data['descripcion'] ?? $data['description'] ?? $data['resumen'] ?? '',
            'requerimientos' => $reqs,
            'tareas'         => $data['tareas'] ?? $data['tasks'] ?? $data['tareas_sugeridas'] ?? [],
            'sprints'        => $sprints,
            'insumos'        => $data['insumos'] ?? $data['inputs'] ?? [],
        ];
    }

    private function validateCreateDraft(array $data): ?string
    {
        if (blank($data['nombre'] ?? null)) return 'La propuesta debe tener nombre de proyecto.';
        if (blank($data['descripcion'] ?? null)) return 'La propuesta debe tener descripción del proyecto.';
        if (empty($data['requerimientos'])) return 'La propuesta debe incluir al menos un requerimiento.';
        if (empty($data['tareas'])) return 'La propuesta debe incluir al menos una tarea.';
        return null;
    }
}
