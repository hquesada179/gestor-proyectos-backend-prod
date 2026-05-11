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
            'history' => $records->map(fn(AiProjectChat $r) => [
                'id'               => $r->id,
                'tipo_accion'      => $r->tipo_accion,
                'modo'             => $r->tipo_accion === 'crear_proyecto' ? 'Crear proyecto' : 'Mejorar proyecto',
                'prompt_usuario'   => Str::limit($r->prompt_usuario, 100),
                'estado'           => $r->estado,
                'proyecto_id'      => $r->proyecto_id,
                'proyecto_nombre'  => $r->proyecto?->nombre,
                'created_at_full'  => $r->created_at->format('d/m/Y H:i'),
                'created_at_human' => $r->created_at->diffForHumans(),
            ]),
        ]);
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
