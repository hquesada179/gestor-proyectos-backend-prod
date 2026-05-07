<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    private string $baseUrl;
    private int    $timeout;
    private string $defaultModel;

    public function __construct()
    {
        $this->baseUrl      = rtrim(config('services.ollama.url', 'http://localhost:11434'), '/');
        $this->timeout      = (int) config('services.ollama.timeout', 120);
        $this->defaultModel = config('services.ollama.model', 'gemma3');
    }

    /**
     * Send a prompt to Ollama and return a structured result.
     *
     * @return array{ok: bool, data?: array, error?: string, raw?: string}
     */
    public function generate(string $prompt, ?string $model = null): array
    {
        $model = $model ?: $this->defaultModel;

        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/api/generate", [
                    'model'  => $model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'format' => 'json',
                    'options' => [
                        'temperature' => 0.3,
                    ],
                ]);

            if ($response->status() === 404) {
                return [
                    'ok'    => false,
                    'error' => "El modelo '{$model}' no está instalado. Ejecuta: ollama pull {$model}",
                ];
            }

            if (!$response->successful()) {
                return [
                    'ok'    => false,
                    'error' => "Ollama respondió con código HTTP {$response->status()}. Revisa que el servicio esté activo.",
                ];
            }

            $body = $response->json();
            $text = trim($body['response'] ?? '');

            if ($text === '') {
                return [
                    'ok'    => false,
                    'error' => 'Ollama devolvió una respuesta vacía. Intenta con otro modelo.',
                ];
            }

            $decoded = $this->parseJson($text);

            if ($decoded === null) {
                return [
                    'ok'    => false,
                    'error' => 'La respuesta no tiene formato JSON válido. Prueba con un modelo diferente.',
                    'raw'   => $text,
                ];
            }

            return ['ok' => true, 'data' => $decoded];

        } catch (ConnectionException) {
            return [
                'ok'    => false,
                'error' => 'No se pudo conectar con Ollama en ' . $this->baseUrl . '. ¿Está corriendo? Ejecuta: ollama serve',
            ];
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('OllamaService request error', ['message' => $e->getMessage()]);
            return [
                'ok'    => false,
                'error' => 'Error en la solicitud HTTP: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error('OllamaService unexpected error', ['message' => $e->getMessage()]);
            return [
                'ok'    => false,
                'error' => 'Error inesperado: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * List models installed in Ollama. Returns empty array on failure.
     */
    public function listModels(): array
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/api/tags");

            if (!$response->successful()) {
                return [];
            }

            return collect($response->json('models', []))
                ->pluck('name')
                ->sort()
                ->values()
                ->toArray();

        } catch (\Exception) {
            return [];
        }
    }

    /**
     * Check if Ollama is reachable.
     */
    public function isReachable(): bool
    {
        try {
            return Http::timeout(3)->get($this->baseUrl)->successful();
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Try to extract valid JSON from model output.
     * Models sometimes wrap JSON in markdown code blocks.
     */
    private function parseJson(string $text): ?array
    {
        // Strip markdown code fences if present
        if (str_contains($text, '```')) {
            preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $text, $m);
            if (!empty($m[1])) {
                $text = trim($m[1]);
            }
        }

        // Find the outermost JSON object if extra text surrounds it
        if (!str_starts_with($text, '{')) {
            preg_match('/\{[\s\S]*\}/', $text, $m);
            $text = $m[0] ?? $text;
        }

        $decoded = json_decode($text, true);

        return (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
            ? $decoded
            : null;
    }
}
