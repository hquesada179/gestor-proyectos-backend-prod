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
        if (! $this->isModelAllowed($model)) {
            return [
                'ok' => false,
                'error' => 'Modelo de IA no permitido.',
            ];
        }

        return $this->openAi->generate($prompt, $model);
    }

    /**
     * Generate plain text for REST endpoints that do not need parsed JSON.
     *
     * @return array{ok: bool, text?: string, error?: string, raw?: mixed}
     */
    public function generateText(string $prompt, ?string $model = null): array
    {
        if (! $this->isModelAllowed($model)) {
            return [
                'ok' => false,
                'error' => 'Modelo de IA no permitido.',
            ];
        }

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

    /**
     * Devuelve los modelos permitidos combinando el modelo por defecto y la allowlist configurada.
     *
     * @return list<string>
     */
    public function allowedModels(): array
    {
        $configured = config('services.openai.allowed_models');
        $extra = [];

        // Aceptamos lista separada por comas desde .env o arreglo directo desde config.
        if (is_string($configured) && trim($configured) !== '') {
            $extra = array_map('trim', explode(',', $configured));
        } elseif (is_array($configured)) {
            $extra = array_map(static fn ($item) => trim((string) $item), $configured);
        }

        return array_values(array_unique(array_filter([
            $this->defaultModel(),
            ...$extra,
        ], static fn ($item) => is_string($item) && $item !== '')));
    }

    /**
     * Verifica si el modelo es nulo/vacío (usará el predeterminado) o pertenece a la lista blanca.
     */
    public function isModelAllowed(?string $model): bool
    {
        if ($model === null || trim($model) === '') {
            return true;
        }

        return in_array(trim($model), $this->allowedModels(), true);
    }

    public function listModels(): array
    {
        return $this->allowedModels();
    }

    public function isReachable(): bool
    {
        return $this->openAi->isConfigured();
    }
}
