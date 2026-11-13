<?php

namespace App\Http\Controllers;

use App\Contexts\Billing\Application\DTOs\ProcessWebhookDTO;
use App\Contexts\Billing\Application\UseCases\ProcessWompiWebhookUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WompiWebhookController extends Controller
{
    public function __construct(
        private readonly ProcessWompiWebhookUseCase $processWebhook
    ) {}

    /**
     * Recibe y procesa los eventos de Wompi.
     * Siempre responde 200 para evitar reintentos de la pasarela por errores de lógica.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        try {
            $result = $this->processWebhook->execute(ProcessWebhookDTO::fromPayload($payload));

            return response()->json($result, 200);
        } catch (\Throwable $e) {
            Log::error('WompiWebhook: error no controlado', [
                'error' => $e->getMessage(),
            ]);

            // La pasarela no debe reintentar por un fallo interno de nuestro flujo.
            return response()->json(['ok' => true], 200);
        }
    }
}
