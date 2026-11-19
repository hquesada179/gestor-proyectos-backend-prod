<?php

namespace App\Contexts\AiAssistant\Application\DTOs;

/**
 * DTO para la solicitud de generación de borrador de proyecto con IA.
 */
class GenerateProjectDraftDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly int $projectId,
        public readonly string $inputBrief,
        public readonly string $targetLanguage = 'es',
        public readonly array $constraints = []
    ) {}

    public static function fromRequest(int $userId, int $projectId, array $data): self
    {
        return new self(
            userId: $userId,
            projectId: $projectId,
            inputBrief: $data['prompt'] ?? $data['input'] ?? '',
            targetLanguage: $data['language'] ?? 'es',
            constraints: $data['constraints'] ?? []
        );
    }
}
