<?php

namespace App\Http\Controllers;

use App\Models\AiProjectChat;
use App\Models\AiUsageLog;
use App\Models\Proyecto;
use App\Services\Ai\AiAssistantService;
use App\Services\Ai\AiCreditService;
use App\Services\Ai\AiProposalApplierService;
use App\Services\Ai\AiProposalEditorService;
use App\Services\AiPromptBuilderService;
use App\Services\AiResponseParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AiAssistantController extends Controller
{
    public function __construct(
        private readonly AiAssistantService $aiAssistant,
        private readonly AiProposalEditorService $proposalEditor,
        private readonly AiPromptBuilderService $promptBuilder,
        private readonly AiResponseParserService $parser,
        private readonly AiCreditService $aiCredits,
        private readonly AiProposalApplierService $proposalApplier,
    ) {}

    // ── Main view ─────────────────────────────────────────────────────────────

    public function index(): View
    {
        $defaultModel = $this->aiAssistant->defaultModel();
        $aiOnline = $this->aiAssistant->isReachable();
        $ai = [
            'routes' => [
                'generateProject' => route('api.ai.generate-project', [], false),
                'improveProject' => route('api.ai.improve-project', [], false),
                'chat' => route('api.ai.chat', [], false),
                'refineProposal' => route('asistente-ia.refine', [], false),
                'regenerateSection' => route('asistente-ia.regenerate-section', [], false),
                'regenerateItem' => route('asistente-ia.regenerate-item', [], false),
                'estructuraBase' => '/asistente-ia/proyecto',
                'balance' => route('asistente-ia.balance', [], false),
            ],
        ];
        $proyectos = Proyecto::accessibleBy(auth()->id())
            ->orderByDesc('updated_at')
            ->get(['id', 'nombre', 'estado', 'descripcion']);

        return view('ai.assistant', compact('defaultModel', 'aiOnline', 'ai', 'proyectos'));
    }

    // ── Unified submit (create or improve) ───────────────────────────────────

    public function submit(Request $request): JsonResponse
    {
        $request->validate([
            'modo' => ['required', 'string', 'in:crear,mejorar'],
            'prompt' => ['required', 'string', 'min:10', 'max:3000'],
            'modelo' => $this->modelValidationRules(true),
            'proyecto_id' => ['nullable', 'integer'],
            'parent_chat_id' => ['nullable', 'integer'],
        ], [
            'prompt.required' => 'Escribe una descripción o instrucción.',
            'prompt.min' => 'El prompt debe tener al menos 10 caracteres.',
            'modelo.required' => 'Selecciona un modelo.',
            'modelo.in' => 'El modelo seleccionado no está permitido.',
        ]);

        $modo = $request->modo;
        $prompt = trim($request->prompt);
        $modelo = trim($request->modelo);
        $proyectoId = $request->input('proyecto_id') ?: null;
        $parentChatId = $request->input('parent_chat_id') ?: null;
        $userId = auth()->id();

        // Verificamos propiedad del chat padre antes de heredar contexto o crear registros nuevos.
        if ($parentChatId !== null) {
            $parentRecord = AiProjectChat::where('id', $parentChatId)
                ->where('user_id', $userId)
                ->first(['id', 'proyecto_id']);

            if (! $parentRecord) {
                return response()->json([
                    'ok' => false,
                    'error' => 'La conversación de origen no existe o no pertenece a tu cuenta.',
                ], 404);
            }

            if (! $proyectoId && $parentRecord->proyecto_id) {
                $proyectoId = (int) $parentRecord->proyecto_id;
            }
        }

        // Validamos acceso al proyecto antes de consumir créditos o crear registros en historial.
        if ($proyectoId !== null && ! $this->canAccessProject($userId, (int) $proyectoId)) {
            return response()->json([
                'ok' => false,
                'error' => 'Proyecto no encontrado o sin permiso.',
            ], 404);
        }

        // ── Credit check BEFORE creating any records ─────────────────────────
        $actionType = $modo === 'crear' ? 'generar_proyecto' : 'mejorar_proyecto';
        $creditCheck = $this->aiCredits->canUseAi($userId, $actionType);
        if (! $creditCheck['allowed']) {
            return response()->json(['ok' => false, 'error' => $creditCheck['message']], 429);
        }

        Log::info('AiAssistantController@submit', [
            'user_id' => $userId, 'modo' => $modo, 'modelo' => $modelo,
            'prompt_length' => strlen($prompt),
        ]);

        // Save to history (with parent link if continuing)
        $record = AiProjectChat::create([
            'user_id' => $userId,
            'proyecto_id' => $proyectoId,
            'parent_chat_id' => $parentChatId,
            'tipo_accion' => $modo === 'crear' ? 'crear_proyecto' : 'editar_proyecto',
            'prompt_usuario' => $prompt,
            'estado' => 'borrador',
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
        try {
            // Delegamos la carga y formateo de la estructura de proyecto al servicio
            $data = $this->proposalApplier->loadProjectStructure($id, auth()->id());

            return response()->json($data);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 404);
        }
    }

    // ── Apply (confirm or discard) ────────────────────────────────────────────

    public function apply(Request $request): JsonResponse
    {
        $request->validate([
            'chat_id' => ['required', 'integer'],
            'action' => ['required', 'string', 'in:confirm,discard'],
            'data' => ['nullable', 'array'],
        ]);

        $userId = auth()->id();
        $chatId = (int) $request->input('chat_id');

        // Serializamos la confirmación bajo transacción y lock de fila para impedir duplicados concurrentes.
        return DB::transaction(function () use ($request, $userId, $chatId) {
            $record = AiProjectChat::where('id', $chatId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $record) {
                return response()->json(['ok' => false, 'error' => 'Registro no encontrado.'], 404);
            }

            // Protección atómica contra doble aplicación o reintentos simultáneos.
            if ($record->estado === 'aplicado') {
                return response()->json([
                    'ok' => false,
                    'error' => 'Esta propuesta ya fue aplicada anteriormente.',
                ], 422);
            }

            if ($request->action === 'discard') {
                $record->update(['estado' => 'descartado']);

                return response()->json(['ok' => true, 'message' => 'Propuesta descartada.']);
            }

            $data = $request->has('data')
                ? $this->parser->normalizeClientProposal($request->input('data', []), $record->datos_detectados ?? [])
                : ($record->datos_detectados ?? []);
            $tipo = $data['_tipo'] ?? 'create';

            $record->update([
                'datos_detectados' => $data,
                'respuesta_ia' => json_encode($data, JSON_UNESCAPED_UNICODE),
            ]);

            try {
                // Delegamos la persistencia en base de datos dentro de la misma transacción bloqueada.
                $result = match ($tipo) {
                    'improve' => $this->proposalApplier->applyImprove($data, $userId),
                    'project_edit' => $this->proposalApplier->applyProjectEdit($data, $userId),
                    default => $this->proposalApplier->applyCreate($data, $userId),
                };

                $record->update([
                    'proyecto_id' => $result['proyecto']->id,
                    'estado' => 'aplicado',
                ]);

                return response()->json([
                    'ok' => true,
                    'message' => $result['message'],
                    'counts' => $result['counts'],
                    'warnings' => $result['warnings'] ?? [],
                    'url' => route('proyectos.show', $result['proyecto']),
                ]);
            } catch (\Throwable $e) {
                $record->update(['estado' => 'error']);

                return response()->json([
                    'ok' => false,
                    'error' => $e->getMessage(),
                ], 422);
            }
        });
    }

    public function refineProposal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'proposal' => ['required', 'array'],
            'instruction' => ['required', 'string', 'min:3', 'max:2000'],
            'model' => $this->modelValidationRules(false),
        ], [
            'model.in' => 'El modelo seleccionado no está permitido.',
        ]);

        $userId = auth()->id();
        if ($denied = $this->rejectIfInaccessibleProposalProject($userId, $validated['proposal'])) {
            return $denied;
        }

        // Credit check
        $creditCheck = $this->aiCredits->canUseAi($userId, 'refinar_propuesta');
        if (! $creditCheck['allowed']) {
            return response()->json(['ok' => false, 'error' => $creditCheck['message']], 429);
        }
        $usageLog = $this->aiCredits->createPendingLog(
            $userId,
            'refinar_propuesta',
            $this->extractProposalProjectId($validated['proposal'])
        );

        $originalProposal = $this->parser->normalizeClientProposal($validated['proposal']);
        $result = $this->proposalEditor->refineProposal($originalProposal, $validated['instruction'], $validated['model'] ?? null);

        if (! $result['ok']) {
            $this->aiCredits->markFailed($usageLog, $result['error'] ?? 'AI call failed');

            return response()->json(['ok' => false, 'error' => $result['error'] ?? 'No se pudo ajustar la propuesta.', 'raw' => $result['raw'] ?? null], 422);
        }

        $this->aiCredits->chargeCredits($usageLog, $creditCheck['credits'] ?? 0);

        $refined = $this->parser->normalizeClientProposal($result['data'], $originalProposal);
        $refined = $this->parser->mergeRefinedWithOriginal($refined, $originalProposal);

        return response()->json(['ok' => true, 'data' => $refined]);
    }

    public function regenerateSection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'proposal' => ['required', 'array'],
            'section' => ['required', 'string', 'in:requerimientos,tareas,sprints,insumos,requerimientos_nuevos,tareas_nuevas,actualizaciones_tareas,sprints_nuevos,insumos_nuevos'],
            'instruction' => ['nullable', 'string', 'max:1000'],
            'model' => $this->modelValidationRules(false),
        ], [
            'model.in' => 'El modelo seleccionado no está permitido.',
        ]);

        $userId = auth()->id();
        if ($denied = $this->rejectIfInaccessibleProposalProject($userId, $validated['proposal'])) {
            return $denied;
        }

        // Credit check
        $creditCheck = $this->aiCredits->canUseAi($userId, 'regenerar_seccion');
        if (! $creditCheck['allowed']) {
            return response()->json(['ok' => false, 'error' => $creditCheck['message']], 429);
        }
        $usageLog = $this->aiCredits->createPendingLog(
            $userId,
            'regenerar_seccion',
            $this->extractProposalProjectId($validated['proposal'])
        );

        $proposal = $this->parser->normalizeClientProposal($validated['proposal']);
        $section = $validated['section'];
        $result = $this->proposalEditor->regenerateSection($proposal, $section, $validated['instruction'] ?? null, $validated['model'] ?? null);

        if (! $result['ok']) {
            $this->aiCredits->markFailed($usageLog, $result['error'] ?? 'AI call failed');

            return response()->json(['ok' => false, 'error' => $result['error'] ?? 'No se pudo regenerar la seccion.', 'raw' => $result['raw'] ?? null], 422);
        }

        $this->aiCredits->chargeCredits($usageLog, $creditCheck['credits'] ?? 0);

        $items = $result['data'][$section] ?? $result['data']['items'] ?? [];

        return response()->json([
            'ok' => true,
            'section' => $section,
            'items' => is_array($items) ? array_values($items) : [],
        ]);
    }

    public function regenerateItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'proposal' => ['required', 'array'],
            'section' => ['required', 'string', 'max:80'],
            'item' => ['required', 'array'],
            'instruction' => ['nullable', 'string', 'max:1000'],
            'model' => $this->modelValidationRules(false),
        ], [
            'model.in' => 'El modelo seleccionado no está permitido.',
        ]);

        $userId = auth()->id();
        if ($denied = $this->rejectIfInaccessibleProposalProject($userId, $validated['proposal'])) {
            return $denied;
        }

        // Credit check
        $creditCheck = $this->aiCredits->canUseAi($userId, 'editar_elemento');
        if (! $creditCheck['allowed']) {
            return response()->json(['ok' => false, 'error' => $creditCheck['message']], 429);
        }
        $usageLog = $this->aiCredits->createPendingLog(
            $userId,
            'editar_elemento',
            $this->extractProposalProjectId($validated['proposal'])
        );

        $proposal = $this->parser->normalizeClientProposal($validated['proposal']);
        $result = $this->proposalEditor->regenerateItem($proposal, $validated['section'], $validated['item'], $validated['instruction'] ?? null, $validated['model'] ?? null);

        if (! $result['ok']) {
            $this->aiCredits->markFailed($usageLog, $result['error'] ?? 'AI call failed');

            return response()->json(['ok' => false, 'error' => $result['error'] ?? 'No se pudo regenerar el elemento.', 'raw' => $result['raw'] ?? null], 422);
        }

        $this->aiCredits->chargeCredits($usageLog, $creditCheck['credits'] ?? 0);

        return response()->json([
            'ok' => true,
            'item' => $result['data']['item'] ?? $result['data'],
        ]);
    }

    // ── History ───────────────────────────────────────────────────────────────

    // ── Legacy endpoints (kept intact) ───────────────────────────────────────

    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'descripcion' => ['required', 'string', 'min:20', 'max:3000'],
            'modelo' => $this->modelValidationRules(true),
        ], [
            'modelo.in' => 'El modelo seleccionado no está permitido.',
        ]);

        // Legacy endpoint: protected with credits (generar_proyecto = 10)
        $userId = auth()->id();
        $creditCheck = $this->aiCredits->canUseAi($userId, 'generar_proyecto');
        if (! $creditCheck['allowed']) {
            return response()->json(['ok' => false, 'error' => $creditCheck['message']], 429);
        }
        $usageLog = $this->aiCredits->createPendingLog($userId, 'generar_proyecto');

        $prompt = $this->promptBuilder->buildClassicPrompt($request->string('descripcion')->toString());
        $result = $this->aiAssistant->generate($prompt, $request->string('modelo')->toString());

        if (! $result['ok']) {
            $this->aiCredits->markFailed($usageLog, $result['error'] ?? 'AI call failed');
        } else {
            $this->aiCredits->chargeCredits($usageLog, $creditCheck['credits'] ?? 0);
        }

        return response()->json($result);
    }

    public function guided(): View
    {
        $defaultModel = $this->aiAssistant->defaultModel();
        $aiOnline = $this->aiAssistant->isReachable();

        return view('ai.guided', compact('defaultModel', 'aiOnline'));
    }

    public function interpret(Request $request): JsonResponse
    {
        $request->validate([
            'idea' => ['required', 'string', 'min:10', 'max:2000'],
            'modelo' => $this->modelValidationRules(true),
        ], [
            'modelo.in' => 'El modelo seleccionado no está permitido.',
        ]);

        // Credit check (guided wizard costs 1 credit)
        $userId = auth()->id();
        $creditCheck = $this->aiCredits->canUseAi($userId, 'chat_ia');
        if (! $creditCheck['allowed']) {
            return response()->json(['ok' => false, 'error' => $creditCheck['message']], 429);
        }
        $usageLog = $this->aiCredits->createPendingLog($userId, 'chat_ia');

        $prompt = $this->promptBuilder->interpretationPrompt(
            $request->string('idea')->toString()
        );
        $result = $this->aiAssistant->generate($prompt, $request->string('modelo')->toString());

        if (! $result['ok']) {
            $this->aiCredits->markFailed($usageLog, $result['error'] ?? 'AI call failed');

            return response()->json(['ok' => false, 'error' => $result['error'] ?? ''], 422);
        }

        $parsed = $this->parser->parseInterpretation($result['data']);
        if (! $parsed['ok']) {
            $this->aiCredits->markFailed($usageLog, $parsed['error'] ?? 'Parse failed');

            return response()->json(['ok' => false, 'error' => $parsed['error']], 422);
        }

        $this->aiCredits->chargeCredits($usageLog, $creditCheck['credits'] ?? 0);

        return response()->json(['ok' => true, 'data' => $parsed['data']]);
    }

    /**
     * Reglas de validación compartidas para restringir los modelos a la lista blanca.
     *
     * @return list<mixed>
     */
    private function modelValidationRules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'max:100',
            Rule::in($this->aiAssistant->allowedModels()),
        ];
    }

    private function canAccessProject(int $userId, int $proyectoId): bool
    {
        return Proyecto::accessibleBy($userId)->where('id', $proyectoId)->exists();
    }

    private function extractProposalProjectId(array $proposal): ?int
    {
        $rawProjectId = $proposal['_proyecto_id'] ?? null;
        if ($rawProjectId === null || $rawProjectId === '') {
            return null;
        }

        $parsed = filter_var($rawProjectId, FILTER_VALIDATE_INT);

        return $parsed && $parsed > 0 ? (int) $parsed : null;
    }

    /**
     * Bloquea operaciones de edición de propuesta si referencian un proyecto inaccesible.
     */
    private function rejectIfInaccessibleProposalProject(int $userId, array $proposal): ?JsonResponse
    {
        $rawProjectId = $proposal['_proyecto_id'] ?? null;
        if ($rawProjectId === null || $rawProjectId === '') {
            return null;
        }

        $proyectoId = filter_var($rawProjectId, FILTER_VALIDATE_INT);
        if (! $proyectoId || ! $this->canAccessProject($userId, (int) $proyectoId)) {
            return response()->json([
                'ok' => false,
                'error' => 'Proyecto no encontrado o sin permiso.',
            ], 404);
        }

        return null;
    }

    // ── Private: create mode ──────────────────────────────────────────────────

    private function handleCreate(
        string $prompt,
        string $modelo,
        AiProjectChat $record,
        ?AiUsageLog $usageLog = null,
        int $credits = 0,
    ): JsonResponse {
        $result = $this->aiAssistant->generate($this->promptBuilder->createProjectPrompt($prompt), $modelo);

        if (! $result['ok']) {
            $record->update(['estado' => 'error', 'respuesta_ia' => $result['error'] ?? '']);
            if ($usageLog) {
                $this->aiCredits->markFailed($usageLog, $result['error'] ?? 'AI call failed');
            }

            return response()->json([
                'ok' => false,
                'error' => $result['error'] ?? 'El proveedor de IA no respondió.',
                'raw' => $result['raw'] ?? null,
            ], 422);
        }

        // Charge credits on successful AI response
        if ($usageLog) {
            $this->aiCredits->chargeCredits($usageLog, $credits);
        }

        $raw = $result['data'];
        $data = $this->parser->normalizeCreateProposal($raw);
        $data['_tipo'] = 'create';

        $record->update([
            'respuesta_ia' => json_encode($raw, JSON_UNESCAPED_UNICODE),
            'datos_detectados' => $data,
        ]);

        return response()->json([
            'ok' => true,
            'type' => 'create_proposal',
            'chat_id' => $record->id,
            'data' => $data,
        ]);
    }

    private function handleImprove(
        string $prompt,
        string $modelo,
        ?int $proyectoId,
        int $userId,
        AiProjectChat $record,
        ?int $parentChatId = null,
        ?AiUsageLog $usageLog = null,
        int $credits = 0,
    ): JsonResponse {
        if (! $proyectoId) {
            $record->update(['estado' => 'descartado']);
            if ($usageLog) {
                $this->aiCredits->markFailed($usageLog, 'No project selected');
            }

            return response()->json([
                'ok' => false,
                'error' => 'Selecciona un proyecto activo para usar el modo "Mejorar proyecto".',
            ], 422);
        }

        // Verificamos acceso al proyecto con el scope canónico del sistema
        $proyecto = Proyecto::accessibleBy($userId)->where('id', $proyectoId)->first();

        if (! $proyecto) {
            $record->update(['estado' => 'descartado']);
            if ($usageLog) {
                $this->aiCredits->markFailed($usageLog, 'Project not found');
            }

            return response()->json(['ok' => false, 'error' => 'Proyecto no encontrado.'], 422);
        }

        $context = $this->promptBuilder->loadProjectContext($proyecto);

        // If continuing from a parent chat, enrich the prompt with previous context
        $finalPrompt = $prompt;
        if ($parentChatId) {
            $parentRecord = AiProjectChat::where('id', $parentChatId)
                ->where('user_id', $userId)
                ->first(['id', 'prompt_usuario', 'datos_detectados']);

            if ($parentRecord) {
                $finalPrompt = $this->promptBuilder->buildContinuationPrompt($parentRecord, $prompt);
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

        if (! $result['ok']) {
            $record->update(['estado' => 'error', 'respuesta_ia' => $result['error'] ?? '']);
            if ($usageLog) {
                $this->aiCredits->markFailed($usageLog, $result['error'] ?? 'AI call failed');
            }

            return response()->json([
                'ok' => false,
                'error' => $result['error'] ?? 'El proveedor de IA no respondió.',
            ], 422);
        }

        // Charge credits on successful AI response
        if ($usageLog) {
            $this->aiCredits->chargeCredits($usageLog, $credits);
        }

        $raw = $result['data'];
        $data = $this->parser->normalizeImproveProposal($raw);
        $data['_tipo'] = 'improve';
        $data['_proyecto_id'] = $proyecto->id;

        $record->update([
            'respuesta_ia' => json_encode($raw, JSON_UNESCAPED_UNICODE),
            'datos_detectados' => $data,
        ]);

        return response()->json([
            'ok' => true,
            'type' => 'improve_proposal',
            'chat_id' => $record->id,
            'proyecto_id' => $proyecto->id,
            'proyecto_nombre' => $proyecto->nombre,
            'data' => $data,
        ]);
    }
}
