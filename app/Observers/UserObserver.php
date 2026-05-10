<?php

namespace App\Observers;

use App\Models\Plan;
use App\Models\User;
use App\Models\UserAiCredit;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * Assign the free plan and create the initial AI credit record when a new user registers.
     * This replaces the on-demand assignment that was previously done inside AiCreditService.
     */
    public function created(User $user): void
    {
        try {
            $freePlan = Plan::where('slug', 'free')->where('active', true)->first();

            if (!$freePlan) {
                Log::warning('UserObserver: free plan not found, skipping credit assignment', [
                    'user_id' => $user->id,
                ]);
                return;
            }

            // Assign plan to user
            $user->updateQuietly(['plan_id' => $freePlan->id]);

            // Create initial AI credit record (avoid duplicate if already exists)
            UserAiCredit::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'plan_id'           => $freePlan->id,
                    'credits_available' => $freePlan->monthly_ai_credits,
                    'credits_used'      => 0,
                    'period_starts_at'  => now(),
                    'period_ends_at'    => now()->addMonth(),
                ]
            );

            Log::info('UserObserver: free plan assigned', [
                'user_id' => $user->id,
                'plan'    => $freePlan->slug,
                'credits' => $freePlan->monthly_ai_credits,
            ]);
        } catch (\Throwable $e) {
            // Never block registration due to plan assignment failure
            Log::error('UserObserver: failed to assign free plan', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
