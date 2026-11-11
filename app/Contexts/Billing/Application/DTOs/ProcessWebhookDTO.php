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
        public readonly int $amountInCents
    ) {}

    public static function fromPayload(array $payload): self
    {
        $transaction = data_get($payload, 'data.transaction', []);

        return new self(
            rawPayload: $payload,
            event: $payload['event'] ?? 'unknown',
            transactionId: $transaction['id'] ?? null,
            reference: $transaction['reference'] ?? null,
            status: $transaction['status'] ?? null,
            amountInCents: (int) ($transaction['amount_in_cents'] ?? 0)
        );
    }
}
