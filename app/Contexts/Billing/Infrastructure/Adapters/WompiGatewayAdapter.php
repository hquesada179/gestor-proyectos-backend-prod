<?php

namespace App\Contexts\Billing\Infrastructure\Adapters;

use App\Contexts\Billing\Domain\Entities\PlanEntity;
use App\Contexts\Billing\Domain\Ports\PaymentGatewayInterface;
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

    public function calculateAmountInCents(PlanEntity $plan): int
    {
        return $plan->amountInCents();
    }

    public function validateWebhookSignature(array $payload): bool
    {
        return $this->wompiService->validateWebhookSignature($payload);
    }
}
