<?php

namespace Tests\Unit\Resilience;

use App\Infrastructure\Resilience\CircuitBreaker;
use App\Infrastructure\Resilience\Exceptions\CircuitBreakerOpenException;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

class CircuitBreakerTest extends TestCase
{
    private CircuitBreaker $circuitBreaker;

    protected function setUp(): void
    {
        parent::setUp();
        // Usar almacén de memoria array para pruebas aisladas
        $this->circuitBreaker = new CircuitBreaker(
            serviceName: 'test_service',
            failureThreshold: 3,
            cooldownSeconds: 10,
            cache: Cache::store('array')
        );
        $this->circuitBreaker->reset();
    }

    public function test_initial_state_is_closed(): void
    {
        $this->assertEquals(CircuitBreaker::STATE_CLOSED, $this->circuitBreaker->getState());
        $this->assertTrue($this->circuitBreaker->isAvailable());
    }

    public function test_successful_calls_keep_circuit_closed(): void
    {
        $result = $this->circuitBreaker->execute(fn () => 'success');

        $this->assertEquals('success', $result);
        $this->assertEquals(CircuitBreaker::STATE_CLOSED, $this->circuitBreaker->getState());
    }

    public function test_consecutive_failures_trip_circuit_to_open(): void
    {
        // Provocar fallos consecutivos hasta alcanzar el umbral (3)
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->execute(function () {
                    throw new RuntimeException('Connection timeout');
                });
            } catch (RuntimeException) {
                // Capturar el error para continuar el bucle
            }
        }

        $this->assertEquals(CircuitBreaker::STATE_OPEN, $this->circuitBreaker->getState());
        $this->assertFalse($this->circuitBreaker->isAvailable());
    }

    public function test_calls_are_blocked_immediately_when_open(): void
    {
        // Forzar 3 fallos
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->execute(fn () => throw new RuntimeException('Error'));
            } catch (RuntimeException) {
            }
        }

        $invoked = false;

        $this->expectException(CircuitBreakerOpenException::class);
        $this->expectExceptionMessage('se encuentra temporalmente fuera de línea');

        $this->circuitBreaker->execute(function () use (&$invoked) {
            $invoked = true;

            return 'should_not_run';
        });

        $this->assertFalse($invoked);
    }

    public function test_fallback_is_returned_when_circuit_is_open(): void
    {
        // Forzar apertura del circuito
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->execute(fn () => throw new RuntimeException('Error'));
            } catch (RuntimeException) {
            }
        }

        $result = $this->circuitBreaker->execute(
            action: fn () => 'real_call',
            fallback: fn () => ['fallback' => true, 'data' => []]
        );

        $this->assertTrue($result['fallback']);
    }

    public function test_successful_probe_resets_circuit_to_closed(): void
    {
        // Forzar apertura con cooldown de 1 segundo para prueba rápida
        $fastBreaker = new CircuitBreaker(
            serviceName: 'fast_service',
            failureThreshold: 2,
            cooldownSeconds: 1,
            cache: Cache::store('array')
        );

        for ($i = 0; $i < 2; $i++) {
            try {
                $fastBreaker->execute(fn () => throw new RuntimeException('Fallo'));
            } catch (RuntimeException) {
            }
        }

        $this->assertEquals(CircuitBreaker::STATE_OPEN, $fastBreaker->getState());

        // Esperar a que venza el cooldown de 1s
        sleep(2);

        $this->assertEquals(CircuitBreaker::STATE_HALF_OPEN, $fastBreaker->getState());

        // La llamada de prueba (probe) tiene éxito
        $result = $fastBreaker->execute(fn () => 'recuperado');

        $this->assertEquals('recuperado', $result);
        $this->assertEquals(CircuitBreaker::STATE_CLOSED, $fastBreaker->getState());
    }
}
