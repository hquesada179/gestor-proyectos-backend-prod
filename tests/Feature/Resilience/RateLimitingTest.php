<?php

namespace Tests\Feature\Resilience;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('firebase-auth:127.0.0.1');
        RateLimiter::clear('public-api:127.0.0.1');
    }

    public function test_firebase_login_rate_limit_blocks_after_threshold(): void
    {
        $payload = ['id_token' => 'invalid-token-test'];

        // Enviar 5 peticiones (dentro del límite permitido)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/auth/firebase-login', $payload);
            // Las primeras 5 son procesadas por el controlador (401 o 422 por token inválido, no 429)
            $this->assertNotEquals(429, $response->status());
        }

        // La 6.ª petición debe ser bloqueada inmediatamente por el middleware de throttle
        $blockedResponse = $this->postJson('/api/auth/firebase-login', $payload);

        $blockedResponse->assertStatus(429)
            ->assertJsonStructure(['message', 'retry_after'])
            ->assertJsonPath('message', 'Demasiados intentos de autenticación. Por favor intente más tarde.');
    }

    public function test_public_api_throttles_exceeding_requests(): void
    {
        // Configurar límite acotado para verificación precisa en test
        RateLimiter::for('public-api', function ($request) {
            return Limit::perMinute(2)->by($request->ip())->response(function ($request, array $headers) {
                return response()->json([
                    'message' => 'Demasiadas solicitudes a la API pública. Cuota por minuto excedida.',
                    'retry_after' => $headers['Retry-After'] ?? 60,
                ], 429, $headers);
            });
        });

        // Las primeras 2 peticiones se procesan exitosamente
        $this->getJson('/api/v1/public/ping')->assertStatus(200);
        $this->getJson('/api/v1/public/ping')->assertStatus(200);

        // La 3.ª petición debe ser rechazada con 429 Too Many Requests
        $response = $this->getJson('/api/v1/public/ping');

        $response->assertStatus(429)
            ->assertJsonPath('message', 'Demasiadas solicitudes a la API pública. Cuota por minuto excedida.');
    }
}
