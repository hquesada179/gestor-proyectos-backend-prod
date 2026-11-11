<?php

namespace App\Contexts\Billing\Infrastructure\Adapters;

use App\Contexts\Billing\Domain\Ports\PaymentGatewayInterface;
use App\Models\Plan;
use App\Services\Payments\WompiPaymentService;

/**
 * Adaptador de infraestructura que envuelve WompiPaymentService.
 */
class WompiGatewayAdapter implements PaymentGatewayInterface
{
    public function __construct(
        private readonly WompiPaymentService $wompiService
    ) {}

    public function isConfigured(): bool
    {
        return $this->wompiService->isConfigured();
    }

    public function generateReference(int $userId, int $planId): string
    {
        return $this->wompiService->generateReference($userId, $planId);
    }

    public function calculateAmountInCents(Plan $plan): int
    {
        return $this->wompiService->amountInCents($plan);
    }

    public function validateWebhookSignature(array $payload): bool
    {
        return $this->wompiService->validateWebhookSignature($payload);
    }
}
