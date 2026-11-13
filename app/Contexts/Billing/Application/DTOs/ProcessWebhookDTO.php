<?php

namespace App\Contexts\Billing\Application\DTOs;

/**
 * DTO para procesar eventos asíncronos recibidos desde la pasarela de pagos.
 */
class ProcessWebhookDTO
{
    public function __construct(
        public readonly array $rawPayload,
        public readonly string $event,
        public readonly ?string $transactionId,
        public readonly ?string $reference,
        public readonly ?string $status,
        public readonly int $amountInCents,
        public readonly ?string $providerEventId = null,
        public readonly ?string $currency = null
    ) {}

    public static function fromPayload(array $payload): self
    {
        $transaction = data_get($payload, 'data.transaction', []);
        $sentAt = $payload['sent_at'] ?? null;
        $event = $payload['event'] ?? 'unknown';

        // Wompi no envía un ID de evento único; se compone uno determinista
        // para que los reintentos del mismo evento sean idempotentes.
        $providerEventId = $sentAt
            ? hash('sha256', implode('|', [
                (string) $sentAt,
                (string) $event,
                (string) ($transaction['id'] ?? ''),
                (string) ($transaction['status'] ?? ''),
            ]))
            : null;

        // Normalizamos la moneda reportada por la pasarela para compararla contra el pago local.
        $rawCurrency = $transaction['currency'] ?? null;
        $currency = is_string($rawCurrency) && trim($rawCurrency) !== ''
            ? strtoupper(trim($rawCurrency))
            : null;

        return new self(
            rawPayload: $payload,
            event: $event,
            transactionId: $transaction['id'] ?? null,
            reference: $transaction['reference'] ?? null,
            status: $transaction['status'] ?? null,
            amountInCents: (int) ($transaction['amount_in_cents'] ?? 0),
            providerEventId: $providerEventId,
            currency: $currency
        );
    }
}
