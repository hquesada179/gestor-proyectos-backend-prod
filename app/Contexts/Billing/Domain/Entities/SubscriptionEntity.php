<?php

namespace App\Contexts\Billing\Domain\Entities;

use DateTimeImmutable;

/**
 * Entidad de dominio pura que representa una suscripción de usuario.
 */
final class SubscriptionEntity
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly int $planId,
        public readonly string $status,
        public readonly ?string $provider = null,
        public readonly ?string $providerSubscriptionId = null,
        public readonly ?DateTimeImmutable $currentPeriodStart = null,
        public readonly ?DateTimeImmutable $currentPeriodEnd = null,
        public readonly bool $cancelAtPeriodEnd = false
    ) {}

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
