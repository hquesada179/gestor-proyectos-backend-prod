<?php

namespace App\Services\Ai;

use App\Models\AiUsageLog;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserAiCredit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiCreditService
{
    // ── Credit cost per action ────────────────────────────────────────────────

    const COSTS = [
        'generar_proyecto'  => 10,
        'mejorar_proyecto'  => 8,
        'regenerar_seccion' => 4,
        'refinar_propuesta' => 4,
        'editar_elemento'   => 2,
        'chat_ia'           => 1,
    ];

    const FREE_PLAN_SLUG = 'free';

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Check whether the user may perform an AI action.
     *
     * @return array{allowed:bool, message?:string, reason?:string, credits?:int, record?:UserAiCredit}
     */
    public function canUseAi(int $userId, string $actionType): array
    {
        $credits = self::COSTS[$actionType] ?? 1;

        try {
            $record = $this->getOrCreateCreditRecord($userId);
            $plan   = $record->plan;

            // 1. Sufficient credits
            if (!$record->hasSufficientCredits($credits)) {
                return [
                    'allowed' => false,
                    'reason'  => 'insufficient_credits',
                    'message' => 'No tienes créditos IA suficientes para esta acción.',
                ];
            }

            // 2. Daily limit
            $todayCount = AiUsageLog::where('user_id', $userId)
                ->where('status', 'success')
                ->whereDate('created_at', today())
                ->count();

            if ($todayCount >= $plan->daily_ai_limit) {
                return [
                    'allowed' => false,
                    'reason'  => 'daily_limit',
                    'message' => 'Has alcanzado el límite diario de IA de tu plan.',
                ];
            }

            // 3. Per-minute rate limit
            $minuteCount = AiUsageLog::where('user_id', $userId)
                ->where('status', 'success')
                ->where('created_at', '>=', now()->subMinute())
                ->count();

            if ($minuteCount >= $plan->minute_ai_limit) {
                return [
                    'allowed' => false,
                    'reason'  => 'rate_limit',
                    'message' => 'El asistente está temporalmente limitado. Intenta de nuevo en unos segundos.',
                ];
            }

            return [
                'allowed' => true,
                'credits' => $credits,
                'record'  => $record,
            ];
        } catch (\Throwable $e) {
            Log::error('AiCreditService@canUseAi: error', [
                'user_id' => $userId,
                'action'  => $actionType,
                'error'   => $e->getMessage(),
            ]);

            // Fail open: if credit infrastructure is broken, don't block the user
            return ['allowed' => true, 'credits' => $credits, 'record' => null];
        }
    }

    /**
     * Create a pending usage log entry before the AI call.
     */
    public function createPendingLog(int $userId, string $actionType, ?int $projectId = null): AiUsageLog
    {
        return AiUsageLog::create([
            'user_id'         => $userId,
            'project_id'      => $projectId,
            'action_type'     => $actionType,
            'credits_charged' => 0,
            'status'          => 'pending',
        ]);
    }

    /**
     * Deduct credits and mark log as successful.
     * Called only after a confirmed successful AI response.
     */
    public function chargeCredits(AiUsageLog $log, int $credits): void
    {
        if ($credits <= 0) {
            $log->update(['status' => 'success']);
            return;
        }

        try {
            DB::transaction(function () use ($log, $credits) {
                $record = UserAiCredit::where('user_id', $log->user_id)->lockForUpdate()->first();

                if ($record && $record->credits_available >= $credits) {
                    $record->decrement('credits_available', $credits);
                    $record->increment('credits_used', $credits);
                }

                $log->update([
                    'credits_charged' => $credits,
                    'status'          => 'success',
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('AiCreditService@chargeCredits: failed to deduct credits', [
                'log_id'  => $log->id,
                'credits' => $credits,
                'error'   => $e->getMessage(),
            ]);
            // Mark log as success anyway — the AI call worked
            $log->update(['status' => 'success', 'credits_charged' => 0]);
        }
    }

    /**
     * Mark log as failed. Credits are NOT deducted.
     */
    public function markFailed(AiUsageLog $log, string $errorMessage): void
    {
        try {
            $log->update([
                'status'          => 'failed',
                'error_message'   => mb_substr($errorMessage, 0, 1000),
                'credits_charged' => 0,
            ]);
        } catch (\Throwable $e) {
            Log::error('AiCreditService@markFailed: could not update log', ['log_id' => $log->id]);
        }
    }

    /**
     * Return credit balance info for a user (safe for frontend exposure, no technical details).
     */
    public function getBalance(int $userId): array
    {
        try {
            $record = $this->getOrCreateCreditRecord($userId);
            $plan   = $record->plan;
            $total  = $plan->monthly_ai_credits;
            $pct    = $total > 0
                ? min(100, (int) round(($record->credits_used / $total) * 100))
                : 0;

            $usedTodayCalls = AiUsageLog::where('user_id', $userId)
                ->where('status', 'success')
                ->whereDate('created_at', today())
                ->count();

            $chargedToday = (int) AiUsageLog::where('user_id', $userId)
                ->where('status', 'success')
                ->whereDate('created_at', today())
                ->sum('credits_charged');

            return [
                'plan_name'          => $plan->name,
                'credits_available'  => $record->credits_available,
                'credits_used'       => $record->credits_used,
                'credits_total'      => $total,
                'pct_used'           => $pct,
                'used_today'         => $chargedToday,
                'used_today_calls'   => $usedTodayCalls,
                'daily_limit'        => $plan->daily_ai_limit,
                'remaining_today'    => max(0, $plan->daily_ai_limit - $usedTodayCalls),
                'period_starts_at'   => $record->period_starts_at?->toDateString(),
                'period_ends_at'     => $record->period_ends_at?->toDateString(),
            ];
        } catch (\Throwable) {
            return [
                'plan_name'         => '—',
                'credits_available' => 0,
                'credits_used'      => 0,
                'credits_total'     => 0,
                'pct_used'          => 0,
                'used_today'        => 0,
                'used_today_calls'  => 0,
                'daily_limit'       => 0,
                'remaining_today'   => 0,
                'period_starts_at'  => null,
                'period_ends_at'    => null,
            ];
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function getOrCreateCreditRecord(int $userId): UserAiCredit
    {
        // Fast path: record already exists
        $record = UserAiCredit::with('plan')->where('user_id', $userId)->first();

        if ($record) {
            return $record;
        }

        // Resolve which plan this user should use:
        // 1. Read from users.plan_id (set by UserObserver on registration)
        // 2. Fall back to free plan if null (covers users created before the observer)
        $user = User::find($userId);
        $plan = null;

        if ($user?->plan_id) {
            $plan = Plan::where('id', $user->plan_id)->where('active', true)->first();
        }

        if (!$plan) {
            $plan = Plan::where('slug', self::FREE_PLAN_SLUG)->where('active', true)->first();
        }

        // Last resort: create the free plan if it doesn't exist yet (bootstrap safety net)
        if (!$plan) {
            $plan = Plan::create([
                'name'               => 'Gratis',
                'slug'               => self::FREE_PLAN_SLUG,
                'monthly_price'      => 0,
                'monthly_ai_credits' => 50,
                'daily_ai_limit'     => 20,
                'minute_ai_limit'    => 2,
                'max_users'          => 1,
                'max_projects'       => 2,
                'is_custom'          => false,
                'active'             => true,
            ]);
        }

        // Ensure users.plan_id is in sync (covers legacy users without plan_id)
        if ($user && !$user->plan_id) {
            $user->updateQuietly(['plan_id' => $plan->id]);
        }

        $record = UserAiCredit::create([
            'user_id'           => $userId,
            'plan_id'           => $plan->id,
            'credits_available' => $plan->monthly_ai_credits,
            'credits_used'      => 0,
            'period_starts_at'  => now(),
            'period_ends_at'    => now()->addMonth(),
        ]);

        $record->load('plan');

        Log::info('AiCreditService: created credit record', [
            'user_id' => $userId,
            'plan'    => $plan->slug,
            'credits' => $plan->monthly_ai_credits,
        ]);

        return $record;
    }
}
