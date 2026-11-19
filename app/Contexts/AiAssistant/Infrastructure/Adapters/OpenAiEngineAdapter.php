<?php

namespace App\Contexts\AiAssistant\Infrastructure\Adapters;

use App\Contexts\AiAssistant\Domain\Ports\AIEngineInterface;
use App\Infrastructure\Resilience\CircuitBreaker;
use App\Services\Ai\OpenAiAssistantService;
use RuntimeException;

/**
 * Adaptador que implementa AIEngineInterface delegando en OpenAiAssistantService
 * y protegiendo las llamadas externas con el patrón Circuit Breaker.
 */
class OpenAiEngineAdapter implements AIEngineInterface
{
    private readonly CircuitBreaker $circuitBreaker;

    public function __construct(
        private readonly OpenAiAssistantService $openAiService,
        ?CircuitBreaker $circuitBreaker = null
    ) {
        $this->circuitBreaker = $circuitBreaker ?: new CircuitBreaker(
            serviceName: 'openai',
            failureThreshold: 5,
            cooldownSeconds: 60
        );
    }

    public function generate(string $systemPrompt, string $userPrompt, array $options = []): array
    {
        $combinedPrompt = "{$systemPrompt}\n\nInstrucción del usuario:\n{$userPrompt}";

        // Ejecución protegida a través del Circuit Breaker para evitar caídas en cascada
        return $this->circuitBreaker->execute(function () use ($combinedPrompt, $options) {
            $result = $this->openAiService->generate($combinedPrompt, $options['model'] ?? null);

            // Validación de respuesta exitosa del proveedor externo
            if (! $result['ok']) {
                throw new RuntimeException($result['error'] ?? 'Fallo en la comunicación con el motor de IA.');
            }

            return $result['data'] ?? [];
        });
    }

    public function getCircuitBreaker(): CircuitBreaker
    {
        return $this->circuitBreaker;
    }
}
