<?php

namespace App\Infrastructure\Resilience;

use App\Infrastructure\Resilience\Exceptions\CircuitBreakerOpenException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Patrón Circuit Breaker para mitigar fallas en cascada y saturación de hilos
 * ante indisponibilidad o latencia excesiva de dependencias externas (OpenAI, Wompi, etc.).
 */
class CircuitBreaker
{
    public const STATE_CLOSED = 'CLOSED';

    public const STATE_OPEN = 'OPEN';

    public const STATE_HALF_OPEN = 'HALF_OPEN';

    private readonly CacheRepository $cache;

    public function __construct(
        public readonly string $serviceName,
        public readonly int $failureThreshold = 5,
        public readonly int $cooldownSeconds = 60,
        ?CacheRepository $cache = null
    ) {
        $this->cache = $cache ?: Cache::store();
    }

    /**
     * Ejecuta una acción protegida por el interruptor. Si el circuito está abierto,
     * ejecuta el fallback o lanza una CircuitBreakerOpenException de inmediato.
     *
     * @template T
     *
     * @param  callable(): T  $action
     * @param  (callable(Throwable|null): T)|null  $fallback
     * @return T
     */
    public function execute(callable $action, ?callable $fallback = null): mixed
    {
        $state = $this->getState();

        // 1. Si el circuito está abierto, verificar si ya se cumplió la ventana de enfriamiento
        if ($state === self::STATE_OPEN) {
            $remaining = $this->getRemainingCooldown();

            // Si aún no transcurre el cooldown, se corta la llamada de inmediato
            if ($remaining > 0) {
                if ($fallback !== null) {
                    return $fallback(null);
                }

                throw new CircuitBreakerOpenException($this->serviceName, $remaining);
            }

            // Ventana cumplida: transicionar a HALF_OPEN para realizar una petición de prueba
            $this->setState(self::STATE_HALF_OPEN);
            $state = self::STATE_HALF_OPEN;
        }

        // 2. Ejecutar la acción real (en estados CLOSED o HALF_OPEN)
        try {
            $result = $action();
            $this->recordSuccess();

            return $result;
        } catch (Throwable $e) {
            $this->recordFailure($e);

            if ($fallback !== null) {
                return $fallback($e);
            }

            throw $e;
        }
    }

    /**
     * Retorna el estado actual del circuito considerando el tiempo transcurrido.
     */
    public function getState(): string
    {
        $state = (string) $this->cache->get($this->key('state'), self::STATE_CLOSED);

        // Si está en OPEN pero el cooldown ya venció, se considera elegible para HALF_OPEN
        if ($state === self::STATE_OPEN && $this->getRemainingCooldown() <= 0) {
            return self::STATE_HALF_OPEN;
        }

        return $state;
    }

    /**
     * Comprueba si el servicio está apto para recibir peticiones.
     */
    public function isAvailable(): bool
    {
        return $this->getState() !== self::STATE_OPEN;
    }

    /**
     * Registra una ejecución exitosa, cerrando el circuito y limpiando fallas.
     */
    public function recordSuccess(): void
    {
        $this->cache->forget($this->key('failures'));
        $this->cache->forget($this->key('last_failure'));
        $this->setState(self::STATE_CLOSED);
    }

    /**
     * Registra una falla en el servicio externo y actualiza las métricas.
     */
    public function recordFailure(Throwable $e): void
    {
        $lastState = $this->getState();
        $this->cache->put($this->key('last_failure'), time(), now()->addDay());

        // Si falla en HALF_OPEN, se regresa inmediatamente a OPEN
        if ($lastState === self::STATE_HALF_OPEN) {
            $this->setState(self::STATE_OPEN);

            return;
        }

        // Incrementar el contador atómico de fallos consecutivos
        $failures = (int) $this->cache->get($this->key('failures'), 0) + 1;
        $this->cache->put($this->key('failures'), $failures, now()->addDay());

        // Si se alcanza el umbral de fallas consecutivas, abrir el circuito
        if ($failures >= $this->failureThreshold) {
            $this->setState(self::STATE_OPEN);
        }
    }

    /**
     * Restablece el circuito manualmente a estado CLOSED.
     */
    public function reset(): void
    {
        $this->cache->forget($this->key('state'));
        $this->cache->forget($this->key('failures'));
        $this->cache->forget($this->key('last_failure'));
    }

    /**
     * Retorna los segundos restantes antes de poder intentar una prueba de recuperación.
     */
    public function getRemainingCooldown(): int
    {
        $lastFailure = (int) $this->cache->get($this->key('last_failure'), 0);
        if ($lastFailure === 0) {
            return 0;
        }

        $elapsed = time() - $lastFailure;
        $remaining = $this->cooldownSeconds - $elapsed;

        return max(0, $remaining);
    }

    private function setState(string $state): void
    {
        $this->cache->put($this->key('state'), $state, now()->addDay());
    }

    private function key(string $attribute): string
    {
        return "circuit_breaker:{$this->serviceName}:{$attribute}";
    }
}
