<?php

namespace App\Contexts\Billing\Domain\Entities;

/**
 * Entidad de dominio pura que representa un plan SaaS sin acoplamiento al ORM.
 */
final class PlanEntity
{
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $name,
        public readonly int $priceCop,
        public readonly float $monthlyPrice,
        public readonly int $monthlyAiCredits,
        public readonly bool $isCustom = false,
        public readonly bool $active = true
    ) {}

    /**
     * Calcula el precio en centavos requerido por pasarelas de pago.
     */
    public function amountInCents(): int
    {
        // Si existe precio fijo en COP tiene prioridad sobre el valor decimal base
        if ($this->priceCop > 0) {
            return $this->priceCop * 100;
        }

        return (int) round($this->monthlyPrice * 100);
    }

    public function isFree(): bool
    {
        return $this->monthlyPrice == 0.0 && ! $this->isCustom;
    }
}
