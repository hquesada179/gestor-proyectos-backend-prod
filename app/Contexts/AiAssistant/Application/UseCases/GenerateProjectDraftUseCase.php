<?php

namespace App\Contexts\AiAssistant\Application\UseCases;

use App\Contexts\AiAssistant\Application\DTOs\GenerateProjectDraftDTO;
use App\Contexts\AiAssistant\Domain\Ports\AIEngineInterface;
use App\Contexts\AiAssistant\Domain\Ports\CreditManagerInterface;
use App\Contexts\AiAssistant\Domain\Ports\DraftStorageInterface;
use RuntimeException;

/**
 * Caso de uso: Generar un borrador estructurado de Scrum a partir del brief de entrada.
 */
class GenerateProjectDraftUseCase
{
    public function __construct(
        private readonly AIEngineInterface $aiEngine,
        private readonly CreditManagerInterface $creditManager,
        private readonly DraftStorageInterface $draftStorage
    ) {}

    /**
     * Ejecuta el ciclo: valida créditos -> genera con LLM -> almacena borrador -> debita créditos.
     */
    public function execute(GenerateProjectDraftDTO $dto): array
    {
        // 1. Verificación previa de balance de créditos del usuario
        if (! $this->creditManager->hasEnoughCredits($dto->userId, 1)) {
            throw new RuntimeException('Créditos insuficientes para generar propuesta con IA.');
        }

        $systemPrompt = 'Eres un asistente experto en Scrum y metodologías ágiles. '.
            'Genera una estructura de requerimientos, historias de usuario y tareas en formato JSON.';

        // 2. Inferencia mediante el puerto del motor de IA
        $aiResult = $this->aiEngine->generate(
            $systemPrompt,
            $dto->inputBrief,
            ['temperature' => 0.7]
        );

        // 3. Persistencia temporal del borrador semiestructurado
        $draftId = $this->draftStorage->saveDraft(
            $dto->projectId,
            $dto->userId,
            $aiResult
        );

        // 4. Cobro posterior garantizado solo tras éxito en la llamada
        $this->creditManager->deductCredits($dto->userId, 1, 'generate_project_draft');

        return [
            'draft_id' => $draftId,
            'status' => 'generated',
            'proposal' => $aiResult,
        ];
    }
}
