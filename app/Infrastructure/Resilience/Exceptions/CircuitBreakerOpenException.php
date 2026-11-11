<?php

namespace App\Infrastructure\Resilience\Exceptions;

use RuntimeException;

/**
 * Excepción lanzada cuando una llamada a un servicio externo es rechazada preventivamente
 * por encontrarse el circuito en estado OPEN (prevención de cascading failure).
 */
class CircuitBreakerOpenException extends RuntimeException
{
    public function __construct(
        public readonly string $serviceName,
        public readonly int $cooldownRemaining = 60,
        string $message = ''
    ) {
        $msg = $message ?: "El servicio externo «{$serviceName}» se encuentra temporalmente fuera de línea por protección del sistema. Reintente en {$cooldownRemaining} segundos.";
        parent::__construct($msg);
    }
}
