<?php

namespace App\Services\Ai;

class AiAssistantService
{
    public function __construct(
        private readonly OpenAiAssistantService $openAi,
    ) {}

    /**
     * Generate a structured JSON response compatible with the existing assistant.
     *
     * @return array{ok: bool, data?: array, error?: string, raw?: string}
     */
    public function generate(string $prompt, ?string $model = null): array
    {
        return $this->openAi->generate($prompt, $model);
    }

    /**
     * Generate plain text for REST endpoints that do not need parsed JSON.
     *
     * @return array{ok: bool, text?: string, error?: string, raw?: mixed}
     */
    public function generateText(string $prompt, ?string $model = null): array
    {
        return $this->openAi->generateTextResult($prompt, $model);
    }

    public function provider(): string
    {
        return 'openai';
    }

    public function providerLabel(): string
    {
        return 'OpenAI';
    }

    public function defaultModel(): string
    {
        return $this->openAi->defaultModel();
    }

    public function listModels(): array
    {
        return [$this->openAi->defaultModel()];
    }

    public function isReachable(): bool
    {
        return $this->openAi->isConfigured();
    }
}
