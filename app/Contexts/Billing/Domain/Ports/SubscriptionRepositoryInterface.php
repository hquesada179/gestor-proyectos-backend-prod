<?php

namespace App\Contexts\Billing\Domain\Ports;

use App\Models\Plan;
use App\Models\Subscription;

/**
 * Puerto de salida para gestión de planes y suscripciones SaaS.
 */
interface SubscriptionRepositoryInterface
{
    /**
     * Localiza un plan por su ID.
     */
    public function findPlanById(int $planId): ?Plan;

    /**
     * Localiza un plan por su slug (free, pro, enterprise).
     */
    public function findPlanBySlug(string $slug): ?Plan;

    /**
     * Obtiene la suscripción activa del usuario.
     */
    public function getActiveSubscription(int $userId): ?Subscription;

    /**
     * Registra o actualiza la suscripción y recarga los créditos correspondientes.
     */
    public function activatePlanForUser(int $userId, int $planId, string $providerSubscriptionId): Subscription;
}
