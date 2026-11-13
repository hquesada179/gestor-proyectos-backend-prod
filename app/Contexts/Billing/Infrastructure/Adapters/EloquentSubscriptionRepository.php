<?php

namespace App\Contexts\Billing\Infrastructure\Adapters;

use App\Contexts\Billing\Domain\Ports\SubscriptionRepositoryInterface;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserAiCredit;

/**
 * Adaptador de infraestructura para suscripciones y planes SaaS con Eloquent.
 */
class EloquentSubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function findPlanById(int $planId): ?Plan
    {
        return Plan::find($planId);
    }

    public function findPlanBySlug(string $slug): ?Plan
    {
        return Plan::where('slug', $slug)->first();
    }

    public function getActiveSubscription(int $userId): ?Subscription
    {
        return Subscription::where('user_id', $userId)
            ->where('status', 'active')
            ->latest('current_period_end')
            ->first();
    }

    public function activatePlanForUser(int $userId, int $planId, string $providerSubscriptionId): Subscription
    {
        // La transacción la abre el caso de uso que orquesta pago, suscripción
        // y créditos; este adaptador solo aplica las escrituras.
        $plan = Plan::findOrFail($planId);
        /** @var User $user */
        $user = User::findOrFail($userId);

        // Se usan las columnas reales de subscriptions: periodo vigente y
        // el identificador de la transacción en la pasarela.
        $subscription = Subscription::updateOrCreate(
            ['user_id' => $userId],
            [
                'plan_id' => $planId,
                'status' => 'active',
                'provider' => 'wompi',
                'provider_subscription_id' => $providerSubscriptionId,
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
                'cancel_at_period_end' => false,
            ]
        );

        // Sincronizar el plan en el usuario
        $user->update(['plan_id' => $planId]);

        // Recargar o ajustar créditos de IA mensuales acordes al plan
        UserAiCredit::updateOrCreate(
            ['user_id' => $userId],
            [
                'plan_id' => $planId,
                'credits_available' => $plan->monthly_ai_credits,
                'credits_used' => 0,
                'period_starts_at' => now(),
                'period_ends_at' => now()->addMonth(),
            ]
        );

        return $subscription;
    }
}
