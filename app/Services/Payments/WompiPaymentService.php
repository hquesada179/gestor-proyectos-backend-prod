<?php

namespace App\Services\Payments;

use App\Models\Plan;

class WompiPaymentService
{
    private readonly string $publicKey;
    private readonly string $privateKey;
    private readonly string $integritySecret;
    private readonly string $eventsSecret;
    private readonly string $currency;
    private readonly bool   $configured;

    public function __construct()
    {
        $this->publicKey       = config('services.wompi.public_key',       '');
        $this->privateKey      = config('services.wompi.private_key',      '');
        $this->integritySecret = config('services.wompi.integrity_secret', '');
        $this->eventsSecret    = config('services.wompi.events_secret',    '');
        $this->currency        = config('services.wompi.currency',         'COP');

        $this->configured = !empty($this->publicKey) && !empty($this->integritySecret);
    }

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    /**
     * Generate a unique, traceable payment reference.
     * Format: GP-{userId}-{planId}-{timestamp}
     */
    public function generateReference(int $userId, int $planId): string
    {
        return sprintf('GP-%d-%d-%d', $userId, $planId, time());
    }

    /**
     * Convert plan price (stored in USD/COP) to integer cents for Wompi.
     * For sandbox: treats monthly_price as-is * 100 (e.g. 9.99 → 999 cents).
     * For production: plans should have COP pricing.
     */
    public function amountInCents(Plan $plan): int
    {
        return (int) round($plan->monthly_price * 100);
    }

    /**
     * Compute SHA256 integrity hash for the Wompi checkout widget.
     * Formula: SHA256(reference + amount_in_cents + currency + integrity_secret)
     */
    public function computeIntegrityHash(string $reference, int $amountInCents): string
    {
        return hash('sha256', $reference . $amountInCents . $this->currency . $this->integritySecret);
    }

    /**
     * Build the full Wompi hosted-checkout URL.
     */
    public function buildCheckoutUrl(string $reference, int $amountInCents, string $redirectUrl): string
    {
        $hash = $this->computeIntegrityHash($reference, $amountInCents);

        return 'https://checkout.wompi.co/p/?' . http_build_query([
            'public-key'          => $this->publicKey,
            'currency'            => $this->currency,
            'amount-in-cents'     => $amountInCents,
            'reference'           => $reference,
            'signature:integrity' => $hash,
            'redirect-url'        => $redirectUrl,
        ]);
    }

    /**
     * Validate the event checksum sent by Wompi in the webhook payload.
     *
     * Wompi sends:
     * {
     *   "signature": {
     *     "properties": ["transaction.id", "transaction.status", "transaction.amount_in_cents"],
     *     "checksum": "sha256hex..."
     *   },
     *   "data": { "transaction": { ... } }
     * }
     *
     * Validation: SHA256(value1 + value2 + ... + events_secret) === checksum
     */
    public function validateWebhookSignature(array $payload): bool
    {
        if (empty($this->eventsSecret)) {
            return false;
        }

        $signature  = $payload['signature']  ?? null;
        $properties = $signature['properties'] ?? [];
        $checksum   = $signature['checksum']   ?? '';

        if (empty($properties) || empty($checksum)) {
            return false;
        }

        $concatenated = '';
        foreach ($properties as $prop) {
            // Navigate dot-notation path inside $payload['data']
            $concatenated .= (string) data_get($payload['data'], $prop, '');
        }
        $concatenated .= $this->eventsSecret;

        return hash_equals(hash('sha256', $concatenated), $checksum);
    }
}
