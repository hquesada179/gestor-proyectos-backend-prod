<?php

namespace App\Contexts\Billing\Domain\Ports;

use App\Contexts\Billing\Domain\Entities\PlanEntity;
use App\Contexts\Billing\Domain\Entities\SubscriptionEntity;

/**
 * Puerto de salida para gestión de planes y suscripciones SaaS.
 */
interface SubscriptionRepositoryInterface
{
    /**
     * Localiza un plan por su ID.
     */
    public function findPlanById(int $planId): ?PlanEntity;

    /**
     * Localiza un plan por su slug (free, pro, enterprise).
     */
    public function findPlanBySlug(string $slug): ?PlanEntity;

    /**
     * Obtiene la suscripción activa del usuario.
     */
    public function getActiveSubscription(int $userId): ?SubscriptionEntity;

    /**
     * Registra o actualiza la suscripción y recarga los créditos correspondientes.
     */
    public function activatePlanForUser(int $userId, int $planId, string $providerSubscriptionId): SubscriptionEntity;
}
