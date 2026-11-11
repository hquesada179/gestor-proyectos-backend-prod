<?php

namespace App\Contexts\Billing\Domain\Ports;

use App\Models\Plan;

/**
 * Puerto de salida para interacción con la pasarela de pagos (Wompi).
 */
interface PaymentGatewayInterface
{
    /**
     * Determina si la pasarela cuenta con credenciales activas.
     */
    public function isConfigured(): bool;

    /**
     * Genera una referencia única de transacción.
     */
    public function generateReference(int $userId, int $planId): string;

    /**
     * Calcula el monto en centavos para el checkout.
     */
    public function calculateAmountInCents(Plan $plan): int;

    /**
     * Valida la firma criptográfica SHA-256 del webhook recibido.
     */
    public function validateWebhookSignature(array $payload): bool;
}
