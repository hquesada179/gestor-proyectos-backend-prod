<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OpenAiAssistantService
{
    /**
     * Generate text from OpenAI and parse it as JSON for the current assistant flow.
     *
     * @return array{ok: bool, data?: array, error?: string, raw?: string}
     */
    public function generate(string $prompt, ?string $model = null): array
    {
        $result = $this->generateTextResult($prompt, $model);

        if (!$result['ok']) {
            return [
                'ok'    => false,
                'error' => $result['error'] ?? 'No se pudo generar la propuesta en este momento.',
            ];
        }

        $text = $result['text'] ?? '';
        $decoded = $this->parseJson($text);

        if ($decoded === null) {
            return [
                'ok'    => false,
                'error' => 'La respuesta del asistente no tiene el formato esperado. Intenta nuevamente.',
                'raw'   => $text,
            ];
        }

        return ['ok' => true, 'data' => $decoded];
    }

    /**
     * Return only the clean assistant text, without leaking credentials.
     *
     * @return array{ok: bool, text?: string, error?: string, raw?: mixed}
     */
    public function generateTextResult(string $prompt, ?string $model = null): array
    {
        $key = config('services.openai.key');

        if (!is_string($key) || trim($key) === '') {
            return [
                'ok'    => false,
                'error' => 'El servicio de generacion IA no esta activo. Contacta al administrador.',
            ];
        }

        $model = $model ?: config('services.openai.model', 'gpt-4.1-mini');

        try {
            $response = Http::withToken($key)
                ->acceptJson()
                ->timeout(120)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Eres un asistente para gestion de proyectos. Responde en espanol. Si el usuario solicita JSON o el prompt exige JSON, responde solo con JSON valido.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.3,
                ]);

            if (!$response->successful()) {
                return [
                    'ok'    => false,
                    'error' => 'No se pudo generar la propuesta en este momento. Intenta nuevamente.',
                ];
            }

            $text = trim((string) data_get($response->json(), 'choices.0.message.content', ''));

            if ($text === '') {
                return [
                    'ok'    => false,
                    'error' => 'El asistente no genero una respuesta. Intenta nuevamente.',
                ];
            }

            return ['ok' => true, 'text' => $text];
        } catch (ConnectionException) {
            return [
                'ok'    => false,
                'error' => 'No se pudo conectar con el servicio de IA. Verifica la conexion del servidor.',
            ];
        } catch (\Throwable) {
            return [
                'ok'    => false,
                'error' => 'Ocurrio un error al consultar el asistente. Intenta nuevamente.',
            ];
        }
    }

    public function defaultModel(): string
    {
        return (string) config('services.openai.model', 'gpt-4.1-mini');
    }

    public function isConfigured(): bool
    {
        $key = config('services.openai.key');

        return is_string($key) && trim($key) !== '';
    }

    private function parseJson(string $text): ?array
    {
        if (str_contains($text, '```')) {
            preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $text, $matches);
            if (!empty($matches[1])) {
                $text = trim($matches[1]);
            }
        }

        if (!str_starts_with(trim($text), '{')) {
            preg_match('/\{[\s\S]*\}/', $text, $matches);
            $text = $matches[0] ?? $text;
        }

        $decoded = json_decode($text, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded)
            ? $decoded
            : null;
    }
}
