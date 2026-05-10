<?php

namespace App\Http\Controllers;

use App\Models\AiProjectChat;
use App\Models\AiUsageLog;
use App\Models\Proyecto;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Services\Ai\AiAssistantService;
use App\Services\Ai\AiCreditService;
use App\Services\Ai\AiProposalEditorService;
use App\Services\AiPromptBuilderService;
use App\Services\AiResponseParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiAssistantController extends Controller
{
    public function __construct(
        private readonly AiAssistantService      $aiAssistant,
        private readonly AiProposalEditorService $proposalEditor,
        private readonly AiPromptBuilderService  $promptBuilder,
        private readonly AiResponseParserService $parser,
        private readonly AiCreditService         $aiCredits,
    ) {}

    // ── Main view ─────────────────────────────────────────────────────────────

    public function index(): \Illuminate\View\View
    {
        $defaultModel    = $this->aiAssistant->defaultModel();
        $aiOnline        = $this->aiAssistant->isReachable();
        $ai              = [
            'routes' => [
                'generateProject'   => route('api.ai.generate-project'),
                'improveProject'    => route('api.ai.improve-project'),
                'chat'              => route('api.ai.chat'),
                'refineProposal'    => route('asistente-ia.refine'),
                'regenerateSection' => route('asistente-ia.regenerate-section'),
                'regenerateItem'    => route('asistente-ia.regenerate-item'),
                'estructuraBase'    => url('asistente-ia/proyecto'),
                'balance'           => route('asistente-ia.balance'),
            ],
        ];
        $proyectos       = Proyecto::where('user_id', auth()->id())
            ->orderByDesc('updated_at')
            ->get(['id', 'nombre', 'estado', 'descripcion']);

        return view('ai.assistant', compact('defaultModel', 'aiOnline', 'ai', 'proyectos'));
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

        // ── Credit check BEFORE creating any records ─────────────────────────
        $actionType  = $modo === 'crear' ? 'generar_proyecto' : 'mejorar_proyecto';
        $creditCheck = $this->aiCredits->canUseAi($userId, $actionType);
        if (!$creditCheck['allowed']) {
            return response()->json(['ok' => false, 'error' => $creditCheck['message']], 429);
        }

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

        // Create pending usage log
        $usageLog = $this->aiCredits->createPendingLog($userId, $actionType, $proyectoId);

        return $modo === 'crear'
            ? $this->handleCreate($prompt, $modelo, $record, $usageLog, $creditCheck['credits'] ?? 0)
            : $this->handleImprove($prompt, $modelo, $proyectoId, $userId, $record, $parentChatId, $usageLog, $creditCheck['credits'] ?? 0);
    }

    // ── Credit balance (safe for frontend) ───────────────────────────────────

    public function getBalance(): JsonResponse
    {
        $balance = $this->aiCredits->getBalance(auth()->id());
        return response()->json(array_merge(['ok' => true], $balance));
    }

    // ── Load current project structure as editable draft ─────────────────────

    public function loadProjectStructure(int $id): JsonResponse
    {
        $userId   = auth()->id();
        $proyecto = Proyecto::where('id', $id)
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereHas('members', fn($m) => $m->where('user_id', $userId)->where('status', 'activo'));
            })
            ->first();

        if (!$proyecto) {
            return response()->json(['ok' => false, 'error' => 'Proyecto no encontrado.'], 404);
        }

        $requerimientos = $proyecto->requirements()
            ->get(['id', 'titulo', 'descripcion', 'tipo', 'prioridad'])
            ->map(fn($r) => [
                '_id'         => $r->id,
                'titulo'      => $r->titulo,
                'descripcion' => $r->descripcion ?? '',
                'tipo'        => $r->tipo ?? 'funcional',
                'prioridad'   => $r->prioridad ?? 'media',
            ])->toArray();

        $tareas = $proyecto->tasks()
            ->with('status:id,nombre')
            ->get(['id', 'titulo', 'descripcion', 'task_status_id'])
            ->map(fn($t) => [
                '_id'         => $t->id,
                'titulo'      => $t->titulo,
                'descripcion' => $t->descripcion ?? '',
                'prioridad'   => 'media',
                'estado'      => $t->status?->nombre ?? 'pendiente',
            ])->toArray();

        $sprints = $proyecto->sprints()
            ->get(['id', 'nombre', 'objetivo', 'fecha_inicio', 'fecha_fin'])
            ->map(function ($s) {
                $semanas = 2;
                if ($s->fecha_inicio && $s->fecha_fin) {
                    $semanas = max(1, (int) ceil($s->fecha_inicio->diffInDays($s->fecha_fin) / 7));
                }
                return ['_id' => $s->id, 'nombre' => $s->nombre, 'objetivo' => $s->objetivo ?? '', 'semanas' => $semanas];
            })->toArray();

        $insumos = $proyecto->inputs()
            ->get(['id', 'titulo', 'tipo', 'contenido'])
            ->map(fn($i) => [
                '_id'      => $i->id,
                'titulo'   => $i->titulo,
                'tipo'     => $i->tipo ?? 'otro',
                'contenido'=> $i->contenido ?? '',
            ])->toArray();

        $data = [
            '_tipo'          => 'project_edit',
            '_proyecto_id'   => $proyecto->id,
            'nombre'         => $proyecto->nombre,
            'descripcion'    => $proyecto->descripcion ?? '',
            'requerimientos' => $requerimientos,
            'tareas'         => $tareas,
            'sprints'        => $sprints,
            'insumos'        => $insumos,
        ];

        // Create a draft chat record so confirm/discard flow works
        $record = AiProjectChat::create([
            'user_id'          => $userId,
            'proyecto_id'      => $proyecto->id,
            'tipo_accion'      => 'editar_proyecto',
            'prompt_usuario'   => 'Carga de estructura del proyecto para edición directa',
            'estado'           => 'borrador',
            'datos_detectados' => $data,
        ]);

        return response()->json([
            'ok'              => true,
            'chat_id'         => $record->id,
            'proyecto_nombre' => $proyecto->nombre,
            'counts'          => [
                'requerimientos' => count($requerimientos),
                'tareas'         => count($tareas),
                'sprints'        => count($sprints),
                'insumos'        => count($insumos),
            ],
            'data' => $data,
        ]);
    }

    // ── Apply (confirm or discard) ────────────────────────────────────────────

    public function apply(Request $request): JsonResponse
    {
        $request->validate([
            'chat_id' => ['required', 'integer'],
            'action'  => ['required', 'string', 'in:confirm,discard'],
            'data'    => ['nullable', 'array'],
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

        $data = $request->has('data')
            ? $this->normalizeClientProposal($request->input('data', []), $record->datos_detectados ?? [])
            : ($record->datos_detectados ?? []);
        $tipo = $data['_tipo'] ?? 'create';

        $record->update([
            'datos_detectados' => $data,
            'respuesta_ia' => json_encode($data, JSON_UNESCAPED_UNICODE),
        ]);

        return match ($tipo) {
            'improve'      => $this->applyImprove($data, $record),
            'project_edit' => $this->applyProjectEdit($data, $record),
            default        => $this->applyCreate($data, $record),
        };
    }

    public function refineProposal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'proposal'    => ['required', 'array'],
            'instruction' => ['required', 'string', 'min:3', 'max:2000'],
            'model'       => ['nullable', 'string', 'max:100'],
        ]);

        // Credit check
        $userId      = auth()->id();
        $creditCheck = $this->aiCredits->canUseAi($userId, 'refinar_propuesta');
        if (!$creditCheck['allowed']) {
            return response()->json(['ok' => false, 'error' => $creditCheck['message']], 429);
        }
        $usageLog = $this->aiCredits->createPendingLog($userId, 'refinar_propuesta',
            $validated['proposal']['_proyecto_id'] ?? null);

        $originalProposal = $this->normalizeClientProposal($validated['proposal']);
        $result = $this->proposalEditor->refineProposal($originalProposal, $validated['instruction'], $validated['model'] ?? null);

        if (!$result['ok']) {
            $this->aiCredits->markFailed($usageLog, $result['error'] ?? 'AI call failed');
            return response()->json(['ok' => false, 'error' => $result['error'] ?? 'No se pudo ajustar la propuesta.', 'raw' => $result['raw'] ?? null], 422);
        }

        $this->aiCredits->chargeCredits($usageLog, $creditCheck['credits'] ?? 0);

        $refined = $this->normalizeClientProposal($result['data'], $originalProposal);
        $refined = $this->mergeRefinedWithOriginal($refined, $originalProposal);

        return response()->json(['ok' => true, 'data' => $refined]);
    }

    public function regenerateSection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'proposal'    => ['required', 'array'],
            'section'     => ['required', 'string', 'in:requerimientos,tareas,sprints,insumos,requerimientos_nuevos,tareas_nuevas,actualizaciones_tareas,sprints_nuevos,insumos_nuevos'],
            'instruction' => ['nullable', 'string', 'max:1000'],
            'model'       => ['nullable', 'string', 'max:100'],
        ]);

        // Credit check
        $userId      = auth()->id();
        $creditCheck = $this->aiCredits->canUseAi($userId, 'regenerar_seccion');
        if (!$creditCheck['allowed']) {
            return response()->json(['ok' => false, 'error' => $creditCheck['message']], 429);
        }
        $usageLog = $this->aiCredits->createPendingLog($userId, 'regenerar_seccion',
            $validated['proposal']['_proyecto_id'] ?? null);

        $proposal = $this->normalizeClientProposal($validated['proposal']);
        $section  = $validated['section'];
        $result   = $this->proposalEditor->regenerateSection($proposal, $section, $validated['instruction'] ?? null, $validated['model'] ?? null);

        if (!$result['ok']) {
            $this->aiCredits->markFailed($usageLog, $result['error'] ?? 'AI call failed');
            return response()->json(['ok' => false, 'error' => $result['error'] ?? 'No se pudo regenerar la seccion.', 'raw' => $result['raw'] ?? null], 422);
        }

        $this->aiCredits->chargeCredits($usageLog, $creditCheck['credits'] ?? 0);

        $items = $result['data'][$section] ?? $result['data']['items'] ?? [];

        return response()->json([
            'ok'      => true,
            'section' => $section,
            'items'   => is_array($items) ? array_values($items) : [],
        ]);
    }

    public function regenerateItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'proposal'    => ['required', 'array'],
            'section'     => ['required', 'string', 'max:80'],
            'item'        => ['required', 'array'],
            'instruction' => ['nullable', 'string', 'max:1000'],
            'model'       => ['nullable', 'string', 'max:100'],
        ]);

        // Credit check
        $userId      = auth()->id();
        $creditCheck = $this->aiCredits->canUseAi($userId, 'editar_elemento');
        if (!$creditCheck['allowed']) {
            return response()->json(['ok' => false, 'error' => $creditCheck['message']], 429);
        }
        $usageLog = $this->aiCredits->createPendingLog($userId, 'editar_elemento',
            $validated['proposal']['_proyecto_id'] ?? null);

        $proposal = $this->normalizeClientProposal($validated['proposal']);
        $result   = $this->proposalEditor->regenerateItem($proposal, $validated['section'], $validated['item'], $validated['instruction'] ?? null, $validated['model'] ?? null);

        if (!$result['ok']) {
            $this->aiCredits->markFailed($usageLog, $result['error'] ?? 'AI call failed');
            return response()->json(['ok' => false, 'error' => $result['error'] ?? 'No se pudo regenerar el elemento.', 'raw' => $result['raw'] ?? null], 422);
        }

        $this->aiCredits->chargeCredits($usageLog, $creditCheck['credits'] ?? 0);

        return response()->json([
            'ok'   => true,
            'item' => $result['data']['item'] ?? $result['data'],
        ]);
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
                'credits_cost'     => match ($r->tipo_accion) {
                    'crear_proyecto'  => 10,
                    'editar_proyecto' => 8,
                    default           => 0,
                },
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

        // Legacy endpoint: protected with credits (generar_proyecto = 10)
        $userId      = auth()->id();
        $creditCheck = $this->aiCredits->canUseAi($userId, 'generar_proyecto');
        if (!$creditCheck['allowed']) {
            return response()->json(['ok' => false, 'error' => $creditCheck['message']], 429);
        }
        $usageLog = $this->aiCredits->createPendingLog($userId, 'generar_proyecto');

        $prompt = $this->buildClassicPrompt($request->string('descripcion')->toString());
        $result = $this->aiAssistant->generate($prompt, $request->string('modelo')->toString());

        if (!$result['ok']) {
            $this->aiCredits->markFailed($usageLog, $result['error'] ?? 'AI call failed');
        } else {
            $this->aiCredits->chargeCredits($usageLog, $creditCheck['credits'] ?? 0);
        }

        return response()->json($result);
    }

    public function guided(): \Illuminate\View\View
    {
        $defaultModel    = $this->aiAssistant->defaultModel();
        $aiOnline        = $this->aiAssistant->isReachable();

        return view('ai.guided', compact('defaultModel', 'aiOnline'));
    }

    public function interpret(Request $request): JsonResponse
    {
        $request->validate([
            'idea'   => ['required', 'string', 'min:10', 'max:2000'],
            'modelo' => ['required', 'string', 'max:100'],
        ]);

        // Credit check (guided wizard costs 1 credit)
        $userId      = auth()->id();
        $creditCheck = $this->aiCredits->canUseAi($userId, 'chat_ia');
        if (!$creditCheck['allowed']) {
            return response()->json(['ok' => false, 'error' => $creditCheck['message']], 429);
        }
        $usageLog = $this->aiCredits->createPendingLog($userId, 'chat_ia');

        $prompt = $this->promptBuilder->interpretationPrompt(
            $request->string('idea')->toString()
        );
        $result = $this->aiAssistant->generate($prompt, $request->string('modelo')->toString());

        if (!$result['ok']) {
            $this->aiCredits->markFailed($usageLog, $result['error'] ?? 'AI call failed');
            return response()->json(['ok' => false, 'error' => $result['error'] ?? ''], 422);
        }

        $parsed = $this->parser->parseInterpretation($result['data']);
        if (!$parsed['ok']) {
            $this->aiCredits->markFailed($usageLog, $parsed['error'] ?? 'Parse failed');
            return response()->json(['ok' => false, 'error' => $parsed['error']], 422);
        }

        $this->aiCredits->chargeCredits($usageLog, $creditCheck['credits'] ?? 0);
        return response()->json(['ok' => true, 'data' => $parsed['data']]);
    }

    // ── Private: create mode ──────────────────────────────────────────────────

    private function handleCreate(
        string        $prompt,
        string        $modelo,
        AiProjectChat $record,
        ?AiUsageLog   $usageLog = null,
        int           $credits  = 0,
    ): JsonResponse {
        $result = $this->aiAssistant->generate($this->promptBuilder->createProjectPrompt($prompt), $modelo);

        if (!$result['ok']) {
            $record->update(['estado' => 'error', 'respuesta_ia' => $result['error'] ?? '']);
            if ($usageLog) $this->aiCredits->markFailed($usageLog, $result['error'] ?? 'AI call failed');
            return response()->json([
                'ok'    => false,
                'error' => $result['error'] ?? 'El proveedor de IA no respondió.',
                'raw'   => $result['raw'] ?? null,
            ], 422);
        }

        // Charge credits on successful AI response
        if ($usageLog) $this->aiCredits->chargeCredits($usageLog, $credits);

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
        ?AiUsageLog   $usageLog     = null,
        int           $credits      = 0,
    ): JsonResponse {
        if (!$proyectoId) {
            $record->update(['estado' => 'descartado']);
            if ($usageLog) $this->aiCredits->markFailed($usageLog, 'No project selected');
            return response()->json([
                'ok'    => false,
                'error' => 'Selecciona un proyecto activo para usar el modo "Mejorar proyecto".',
            ], 422);
        }

        $proyecto = Proyecto::where('id', $proyectoId)->where('user_id', $userId)->first();

        if (!$proyecto) {
            $record->update(['estado' => 'descartado']);
            if ($usageLog) $this->aiCredits->markFailed($usageLog, 'Project not found');
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

        $result = $this->aiAssistant->generate(
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
            if ($usageLog) $this->aiCredits->markFailed($usageLog, $result['error'] ?? 'AI call failed');
            return response()->json([
                'ok'    => false,
                'error' => $result['error'] ?? 'El proveedor de IA no respondió.',
            ], 422);
        }

        // Charge credits on successful AI response
        if ($usageLog) $this->aiCredits->chargeCredits($usageLog, $credits);

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
        $validationError = $this->validateCreateDraft($data);
        if ($validationError) {
            return response()->json(['ok' => false, 'error' => $validationError], 422);
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

    private function normalizeClientProposal(array $data, array $fallback = []): array
    {
        if (isset($data['project']) && is_array($data['project'])) {
            $data['nombre'] = $data['project']['nombre'] ?? $data['project']['name'] ?? $data['nombre'] ?? null;
            $data['descripcion'] = $data['project']['descripcion'] ?? $data['project']['description'] ?? $data['descripcion'] ?? null;
        }

        $type = $data['_tipo'] ?? $fallback['_tipo'] ?? 'create';

        if ($type === 'improve') {
            $normalized = $this->normalizeImproveProposal($data);
            $normalized['_tipo'] = 'improve';
            $normalized['_proyecto_id'] = $data['_proyecto_id'] ?? $fallback['_proyecto_id'] ?? null;
            return $normalized;
        }

        if ($type === 'project_edit') {
            $normalized = $this->normalizeCreateProposal($data);
            $normalized['_tipo'] = 'project_edit';
            $normalized['_proyecto_id'] = $data['_proyecto_id'] ?? $fallback['_proyecto_id'] ?? null;
            return $normalized;
        }

        $normalized = $this->normalizeCreateProposal($data);
        $normalized['_tipo'] = 'create';

        return $normalized;
    }

    private function validateCreateDraft(array $data): ?string
    {
        if (blank($data['nombre'] ?? null)) {
            return 'El borrador debe tener nombre de proyecto.';
        }

        if (blank($data['descripcion'] ?? null)) {
            return 'El borrador debe tener descripcion del proyecto.';
        }

        if (empty($data['requerimientos']) || !is_array($data['requerimientos'])) {
            return 'Agrega al menos un requerimiento antes de guardar.';
        }

        if (empty($data['tareas']) || !is_array($data['tareas'])) {
            return 'Agrega al menos una tarea antes de guardar.';
        }

        return null;
    }

    // ── Private: apply project_edit (update existing project) ────────────────

    private function applyProjectEdit(array $data, AiProjectChat $record): JsonResponse
    {
        $proyectoId = $data['_proyecto_id'] ?? null;

        if (!$proyectoId) {
            return response()->json(['ok' => false, 'error' => 'No se encontro el proyecto en la propuesta.']);
        }

        $proyecto = Proyecto::where('id', $proyectoId)->where('user_id', auth()->id())->first();

        if (!$proyecto) {
            return response()->json(['ok' => false, 'error' => 'Proyecto no encontrado o sin permiso.']);
        }

        try {
            DB::beginTransaction();

            $counts = ['reqs_new' => 0, 'reqs_updated' => 0, 'tasks_new' => 0, 'tasks_updated' => 0,
                       'sprints_new' => 0, 'sprints_updated' => 0, 'insumos_new' => 0, 'insumos_updated' => 0];

            // Update project name/description if changed
            $proyecto->update([
                'nombre'      => $data['nombre']      ?? $proyecto->nombre,
                'descripcion' => $data['descripcion'] ?? $proyecto->descripcion,
            ]);

            // Requirements: update existing (_id) or insert new (no _id)
            foreach ($data['requerimientos'] ?? [] as $req) {
                if (empty($req['titulo'])) continue;
                $tipo = in_array($req['tipo'] ?? '', ['funcional', 'no_funcional']) ? $req['tipo'] : 'funcional';
                $prio = in_array($req['prioridad'] ?? '', ['alta', 'media', 'baja']) ? $req['prioridad'] : 'media';

                if (!empty($req['_id'])) {
                    $existing = $proyecto->requirements()->find($req['_id']);
                    if ($existing) {
                        $existing->update(['titulo' => $req['titulo'], 'descripcion' => $req['descripcion'] ?? '', 'tipo' => $tipo, 'prioridad' => $prio]);
                        $counts['reqs_updated']++;
                        continue;
                    }
                }
                $proyecto->requirements()->create(['titulo' => $req['titulo'], 'descripcion' => $req['descripcion'] ?? '', 'tipo' => $tipo, 'prioridad' => $prio]);
                $counts['reqs_new']++;
            }

            // Tasks: update existing (_id) or insert new (no _id)
            $status = TaskStatus::orderBy('orden')->first()
                ?? TaskStatus::create(['nombre' => 'Pendiente', 'color' => '#6B7280', 'orden' => 1]);

            foreach ($data['tareas'] ?? [] as $task) {
                if (empty($task['titulo'])) continue;

                if (!empty($task['_id'])) {
                    $existing = $proyecto->tasks()->find($task['_id']);
                    if ($existing) {
                        $existing->update(['titulo' => $task['titulo'], 'descripcion' => $task['descripcion'] ?? '']);
                        $counts['tasks_updated']++;
                        continue;
                    }
                }
                $proyecto->tasks()->create(['titulo' => $task['titulo'], 'descripcion' => $task['descripcion'] ?? '', 'task_status_id' => $status->id]);
                $counts['tasks_new']++;
            }

            // Sprints: update nombre/objetivo for existing, insert new
            foreach ($data['sprints'] ?? [] as $sprint) {
                if (empty($sprint['nombre'])) continue;
                $semanas = max(1, (int) ($sprint['semanas'] ?? 2));

                if (!empty($sprint['_id'])) {
                    $existing = $proyecto->sprints()->find($sprint['_id']);
                    if ($existing) {
                        $existing->update(['nombre' => $sprint['nombre'], 'objetivo' => $sprint['objetivo'] ?? '']);
                        $counts['sprints_updated']++;
                        continue;
                    }
                }
                $proyecto->sprints()->create([
                    'nombre'       => $sprint['nombre'],
                    'objetivo'     => $sprint['objetivo'] ?? '',
                    'fecha_inicio' => now()->toDateString(),
                    'fecha_fin'    => now()->addWeeks($semanas)->toDateString(),
                    'estado'       => 'planificado',
                ]);
                $counts['sprints_new']++;
            }

            // Inputs: update existing (_id) or insert new
            foreach ($data['insumos'] ?? [] as $insumo) {
                $titulo = $insumo['titulo'] ?? $insumo['nombre'] ?? '';
                if (empty($titulo)) continue;

                if (!empty($insumo['_id'])) {
                    $existing = $proyecto->inputs()->find($insumo['_id']);
                    if ($existing) {
                        $existing->update(['titulo' => $titulo, 'tipo' => $insumo['tipo'] ?? 'otro', 'contenido' => $insumo['contenido'] ?? '']);
                        $counts['insumos_updated']++;
                        continue;
                    }
                }
                $proyecto->inputs()->create(['tipo' => $insumo['tipo'] ?? 'otro', 'titulo' => $titulo, 'contenido' => $insumo['contenido'] ?? '']);
                $counts['insumos_new']++;
            }

            DB::commit();
            $record->update(['estado' => 'aplicado']);

            Log::info('AiAssistantController@applyProjectEdit: applied', ['proyecto_id' => $proyecto->id, 'counts' => $counts]);

            return response()->json([
                'ok'      => true,
                'message' => "Cambios aplicados al proyecto «{$proyecto->nombre}».",
                'counts'  => $counts,
                'url'     => route('proyectos.show', $proyecto),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $record->update(['estado' => 'error']);
            Log::error('AiAssistantController@applyProjectEdit: error', ['message' => $e->getMessage()]);
            return response()->json(['ok' => false, 'error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    private function mergeRefinedWithOriginal(array $refined, array $original): array
    {
        $tipo = $refined['_tipo'] ?? $original['_tipo'] ?? 'create';

        $sections = $tipo === 'improve'
            ? ['requerimientos_nuevos', 'tareas_nuevas', 'actualizaciones_tareas', 'sprints_nuevos', 'insumos_nuevos']
            : ['requerimientos', 'tareas', 'sprints', 'insumos']; // handles both 'create' and 'project_edit'

        foreach ($sections as $section) {
            $originalItems = $original[$section] ?? [];
            $refinedItems  = $refined[$section] ?? [];

            // If the AI returned an empty section but the original had content,
            // fall back to the original to protect against partial AI responses.
            if (empty($refinedItems) && !empty($originalItems)) {
                $refined[$section] = $originalItems;
            }
        }

        if ($tipo === 'create') {
            if (blank($refined['nombre'] ?? null) && !blank($original['nombre'] ?? null)) {
                $refined['nombre'] = $original['nombre'];
            }
            if (blank($refined['descripcion'] ?? null) && !blank($original['descripcion'] ?? null)) {
                $refined['descripcion'] = $original['descripcion'];
            }
        }

        return $refined;
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
