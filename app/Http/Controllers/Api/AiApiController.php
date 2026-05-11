<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiAssistantService;
use App\Services\Ai\AiCreditService;
use App\Services\Ai\AiProposalEditorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiApiController extends Controller
{
    public function __construct(
        private readonly AiAssistantService      $aiAssistant,
        private readonly AiProposalEditorService $proposalEditor,
        private readonly AiCreditService         $aiCredits,
    ) {}

    public function credits(Request $request): JsonResponse
    {
        $balance = $this->aiCredits->getBalance($request->user()->id);

        return response()->json([
            'credits' => [
                'available'  => $balance['credits_available'],
                'used'       => $balance['credits_used'],
                'total'      => $balance['credits_total'],
                'plan'       => $balance['plan_name'],
                'expires_at' => $balance['period_ends_at'],
            ],
        ]);
    }

    public function generateProject(Request $request): JsonResponse
    {
        return $this->generateFromRequest($request);
    }

    public function improveProject(Request $request): JsonResponse
    {
        return $this->generateFromRequest($request);
    }

    public function chat(Request $request): JsonResponse
    {
        return $this->generateFromRequest($request);
    }

    public function refineProposal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'proposal' => ['required', 'array'],
            'instruction' => ['required', 'string', 'min:3', 'max:2000'],
            'model' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $this->proposalEditor->refineProposal($validated['proposal'], $validated['instruction'], $validated['model'] ?? null);

        return $this->proposalResponse($result);
    }

    public function regenerateSection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'proposal' => ['required', 'array'],
            'section' => ['required', 'string', 'max:80'],
            'instruction' => ['nullable', 'string', 'max:1000'],
            'model' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $this->proposalEditor->regenerateSection($validated['proposal'], $validated['section'], $validated['instruction'] ?? null, $validated['model'] ?? null);

        return $this->proposalResponse($result);
    }

    public function regenerateItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'proposal' => ['required', 'array'],
            'section' => ['required', 'string', 'max:80'],
            'item' => ['required', 'array'],
            'instruction' => ['nullable', 'string', 'max:1000'],
            'model' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $this->proposalEditor->regenerateItem($validated['proposal'], $validated['section'], $validated['item'], $validated['instruction'] ?? null, $validated['model'] ?? null);

        return $this->proposalResponse($result);
    }

    private function generateFromRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['nullable', 'string', 'max:12000'],
            'message' => ['nullable', 'string', 'max:12000'],
            'descripcion' => ['nullable', 'string', 'max:12000'],
            'model' => ['nullable', 'string', 'max:100'],
        ]);

        $prompt = trim($validated['prompt'] ?? $validated['message'] ?? $validated['descripcion'] ?? '');

        if ($prompt === '') {
            return response()->json([
                'ok' => false,
                'error' => 'Envia un prompt, message o descripcion para generar la respuesta.',
            ], 422);
        }

        $result = $this->aiAssistant->generateText($prompt, $validated['model'] ?? null);

        if (!$result['ok']) {
            return response()->json([
                'ok'    => false,
                'error' => $result['error'] ?? 'No se pudo generar la respuesta.',
            ], 422);
        }

        return response()->json([
            'ok'   => true,
            'text' => $result['text'] ?? '',
        ]);
    }

    private function proposalResponse(array $result): JsonResponse
    {
        if (!$result['ok']) {
            return response()->json([
                'ok'    => false,
                'error' => $result['error'] ?? 'No se pudo procesar la propuesta.',
                'raw'   => $result['raw'] ?? null,
            ], 422);
        }

        return response()->json([
            'ok'   => true,
            'data' => $result['data'] ?? [],
        ]);
    }
}
