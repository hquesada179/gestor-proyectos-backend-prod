<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserAiCredit;
use App\Services\Payments\WompiPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WompiWebhookController extends Controller
{
    public function __construct(private WompiPaymentService $wompi) {}

    /**
     * Receive and process Wompi webhook events.
     * Always returns 200 to avoid Wompi retries on logic errors.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        // ── 1. Log raw event (always, for audit) ──────────────────────
        $eventType       = $payload['event']     ?? null;
        $providerEventId = $payload['sent_at']   ?? null;   // Wompi uses sent_at as event identifier

        // Idempotency check: if this event was already processed, skip
        if ($providerEventId) {
            $existing = PaymentEvent::where('provider_event_id', $providerEventId)->first();
            if ($existing?->isProcessed()) {
                Log::info('WompiWebhook: duplicate event ignored', ['id' => $providerEventId]);
                return response()->json(['ok' => true, 'msg' => 'duplicate']);
            }
        }

        // Store the event regardless of validation outcome
        $event = PaymentEvent::create([
            'provider'          => 'wompi',
            'event_type'        => $eventType,
            'provider_event_id' => $providerEventId,
            'payload'           => $payload,
        ]);

        // ── 2. Validate signature ──────────────────────────────────────
        if (!$this->wompi->validateWebhookSignature($payload)) {
            Log::warning('WompiWebhook: invalid signature', ['event_id' => $event->id]);
            // Return 200 but do NOT process — prevents exposing validation details
            return response()->json(['ok' => false, 'msg' => 'invalid_signature'], 200);
        }

        // ── 3. Only process transaction events ─────────────────────────
        if ($eventType !== 'transaction.updated') {
            $event->update(['processed_at' => now()]);
            return response()->json(['ok' => true, 'msg' => 'event_ignored']);
        }

        // ── 4. Extract transaction data ────────────────────────────────
        $transaction = $payload['data']['transaction'] ?? null;
        if (!$transaction) {
            Log::error('WompiWebhook: missing transaction data', ['event_id' => $event->id]);
            return response()->json(['ok' => false, 'msg' => 'missing_data'], 200);
        }

        $reference = $transaction['reference']  ?? null;
        $status    = $transaction['status']      ?? null;
        $txId      = $transaction['id']          ?? null;

        // ── 5. Find our payment record ─────────────────────────────────
        $payment = Payment::where('reference', $reference)->first();

        if (!$payment) {
            Log::warning('WompiWebhook: payment not found', ['reference' => $reference]);
            $event->update(['processed_at' => now()]);
            return response()->json(['ok' => false, 'msg' => 'payment_not_found'], 200);
        }

        // Idempotency: if already approved, skip
        if ($payment->isApproved()) {
            Log::info('WompiWebhook: payment already approved', ['reference' => $reference]);
            $event->update(['processed_at' => now()]);
            return response()->json(['ok' => true, 'msg' => 'already_approved']);
        }

        // ── 6. Process based on status ─────────────────────────────────
        try {
            if ($status === 'APPROVED') {
                $this->activatePlan($payment, $transaction, $txId);
                Log::info('WompiWebhook: plan activated', [
                    'reference' => $reference,
                    'user_id'   => $payment->user_id,
                    'plan_id'   => $payment->plan_id,
                ]);
            } elseif (in_array($status, ['DECLINED', 'VOIDED', 'ERROR'])) {
                $payment->update([
                    'status'      => 'rejected',
                    'raw_payload' => $transaction,
                ]);
                Log::info('WompiWebhook: payment rejected', ['reference' => $reference, 'status' => $status]);
            }
            // PENDING: do nothing, wait for next event

            $event->update(['processed_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('WompiWebhook: processing error', [
                'reference' => $reference,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            // Return 200 anyway — Wompi should not retry on our logic errors
        }

        return response()->json(['ok' => true]);
    }

    // ── Plan activation (transactional, idempotent) ───────────────────────

    private function activatePlan(Payment $payment, array $transaction, ?string $txId): void
    {
        DB::transaction(function () use ($payment, $transaction, $txId) {

            // Mark payment as approved
            $payment->update([
                'status'              => 'approved',
                'provider_payment_id' => $txId,
                'raw_payload'         => $transaction,
                'paid_at'             => now(),
            ]);

            // Update or create subscription
            $subscription = Subscription::updateOrCreate(
                ['user_id' => $payment->user_id],
                [
                    'plan_id'                  => $payment->plan_id,
                    'status'                   => 'active',
                    'provider'                 => 'wompi',
                    'provider_subscription_id' => $txId,
                    'current_period_start'     => now(),
                    'current_period_end'       => now()->addMonth(),
                    'cancel_at_period_end'     => false,
                ]
            );

            // Link payment to subscription
            $payment->update(['subscription_id' => $subscription->id]);

            // Update users.plan_id
            User::where('id', $payment->user_id)
                ->update(['plan_id' => $payment->plan_id]);

            // Update or reset AI credits for the new plan
            $plan = Plan::findOrFail($payment->plan_id);
            UserAiCredit::updateOrCreate(
                ['user_id' => $payment->user_id],
                [
                    'plan_id'           => $payment->plan_id,
                    'credits_available' => $plan->monthly_ai_credits,
                    'credits_used'      => 0,
                    'period_starts_at'  => now(),
                    'period_ends_at'    => now()->addMonth(),
                ]
            );
        });
    }
}
