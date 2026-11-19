<?php

namespace App\Contexts\Billing\Infrastructure\Adapters;

use App\Contexts\Billing\Domain\Entities\PlanEntity;
use App\Contexts\Billing\Domain\Entities\SubscriptionEntity;
use App\Contexts\Billing\Domain\Ports\SubscriptionRepositoryInterface;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserAiCredit;
use DateTimeImmutable;

/**
 * Adaptador de infraestructura para suscripciones y planes SaaS con Eloquent.
 */
class EloquentSubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function findPlanById(int $planId): ?PlanEntity
    {
        $plan = Plan::find($planId);

        return $plan ? $this->toPlanEntity($plan) : null;
    }

    public function findPlanBySlug(string $slug): ?PlanEntity
    {
        $plan = Plan::where('slug', $slug)->first();

        return $plan ? $this->toPlanEntity($plan) : null;
    }

    public function getActiveSubscription(int $userId): ?SubscriptionEntity
    {
        $subscription = Subscription::where('user_id', $userId)
            ->where('status', 'active')
            ->latest('current_period_end')
            ->first();

        return $subscription ? $this->toSubscriptionEntity($subscription) : null;
    }

    public function activatePlanForUser(int $userId, int $planId, string $providerSubscriptionId): SubscriptionEntity
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

        return $this->toSubscriptionEntity($subscription);
    }

    private function toPlanEntity(Plan $plan): PlanEntity
    {
        return new PlanEntity(
            id: (int) $plan->id,
            slug: (string) $plan->slug,
            name: (string) $plan->name,
            priceCop: (int) ($plan->price_cop ?? 0),
            monthlyPrice: (float) ($plan->monthly_price ?? 0),
            monthlyAiCredits: (int) ($plan->monthly_ai_credits ?? 0),
            isCustom: (bool) ($plan->is_custom ?? false),
            active: (bool) ($plan->active ?? true)
        );
    }

    private function toSubscriptionEntity(Subscription $subscription): SubscriptionEntity
    {
        return new SubscriptionEntity(
            id: (int) $subscription->id,
            userId: (int) $subscription->user_id,
            planId: (int) $subscription->plan_id,
            status: (string) $subscription->status,
            provider: $subscription->provider,
            providerSubscriptionId: $subscription->provider_subscription_id,
            currentPeriodStart: $subscription->current_period_start
                ? DateTimeImmutable::createFromInterface($subscription->current_period_start)
                : null,
            currentPeriodEnd: $subscription->current_period_end
                ? DateTimeImmutable::createFromInterface($subscription->current_period_end)
                : null,
            cancelAtPeriodEnd: (bool) $subscription->cancel_at_period_end
        );
    }
}
