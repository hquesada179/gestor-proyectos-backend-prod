<?php

namespace App\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Ochat
{
    public function send(string $message): array
    {
        $url     = rtrim(config('services.ollama.url', 'http://127.0.0.1:11434'), '/');
        $model   = config('services.ollama.model', 'phi3');
        $timeout = (int) config('services.ollama.timeout', 180);

        $systemPrompt =
            "You are an expert Software Architect and Project Assistant. " .
            "Analyze the user's input. If the user wants to create or scaffold a new project, output a JSON with this exact structure: " .
            '{ "tipo": "proyecto", "nombre": "Project Name", "descripcion": "Description", "requirements": [{"titulo": "Req 1", "descripcion": "...", "tipo": "funcional"}], "tasks": [{"titulo": "Task 1", "descripcion": "..."}] }. ' .
            "If the user is just saying hello, asking a question, or the prompt is NOT about creating a project, output a JSON with this exact structure: " .
            '{ "tipo": "chat", "mensaje": "Your conversational response here" }. ' .
            "You MUST output STRICTLY valid JSON and nothing else. Do not use markdown blocks.";

        Log::info('[Ochat] Sending request', ['url' => $url, 'model' => $model]);

        $response = Http::timeout($timeout)
            ->post("{$url}/api/generate", [
                'model'   => $model,
                'prompt'  => $systemPrompt . "\n\nUser Idea: " . $message,
                'stream'  => false,
                'options' => ['temperature' => 0.2],
            ]);

        if ($response->status() === 404) {
            Log::error('[Ochat] Model not found', ['model' => $model]);
            return ['response' => json_encode(['tipo' => 'chat', 'mensaje' => "El modelo '{$model}' no está instalado. Ejecuta: ollama pull {$model}"])];
        }

        if (!$response->successful()) {
            Log::error('[Ochat] HTTP error from Ollama', [
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 300),
            ]);
            return [];
        }

        $body = $response->json();
        Log::info('[Ochat] Response received', [
            'model'            => $body['model'] ?? '?',
            'response_preview' => substr($body['response'] ?? '', 0, 120),
        ]);

        return $body;
    }
}
