<?php

namespace App\Contexts\Billing\Infrastructure\Adapters;

use App\Contexts\Billing\Domain\Ports\SubscriptionRepositoryInterface;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserAiCredit;
use Illuminate\Support\Facades\DB;

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
            ->latest()
            ->first();
    }

    public function activatePlanForUser(int $userId, int $planId, string $reference): Subscription
    {
        return DB::transaction(function () use ($userId, $planId, $reference) {
            $plan = Plan::findOrFail($planId);
            /** @var User $user */
            $user = User::findOrFail($userId);

            // Actualizar o crear la suscripción activa
            $subscription = Subscription::updateOrCreate(
                ['user_id' => $userId],
                [
                    'plan_id' => $planId,
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => now()->addMonth(),
                    'reference' => $reference,
                ]
            );

            // Sincronizar el plan en el usuario
            $user->update(['plan_id' => $planId]);

            // Recargar o ajustar créditos de IA mensuales acordes al plan
            $creditRecord = UserAiCredit::firstOrCreate(
                ['user_id' => $userId],
                ['credits_available' => 0, 'credits_used' => 0, 'plan_id' => $planId]
            );

            $creditRecord->update([
                'plan_id' => $planId,
                'credits_available' => $plan->monthly_ai_credits,
                'credits_used' => 0,
            ]);

            return $subscription;
        });
    }
}
