<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UserAiCredit;
use App\Services\Payments\WompiPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(private WompiPaymentService $wompi) {}

    /**
     * Initiate checkout for a given plan.
     * - Free plan: activates instantly without Wompi.
     * - Custom/Enterprise plan: reject (contact sales).
     * - Paid plan: redirect to Wompi hosted checkout.
     */
    public function checkout(Request $request, Plan $plan): RedirectResponse
    {
        // Guard: plan must be active and purchasable
        if (!$plan->active) {
            return back()->with('error', 'Este plan no está disponible.');
        }

        if ($plan->is_custom) {
            return back()->with('error', 'Para el plan Enterprise debes contactar a ventas directamente.');
        }

        $user = auth()->user();

        // Guard: don't allow re-purchasing the same plan
        if ($user->plan_id === $plan->id) {
            return redirect()->route('planes.index')->with('info', 'Ya estás en este plan.');
        }

        // Free plan: activate directly without payment gateway
        if ($plan->isFree()) {
            $this->activatePlanDirectly($user->id, $plan);
            return redirect()->route('pagos.resultado')
                ->with('plan_name', $plan->name)
                ->with('free', true);
        }

        // Paid plan: require Wompi configuration
        if (!$this->wompi->isConfigured()) {
            return back()->with('error', 'La pasarela de pagos aún no está configurada. Contacta al administrador.');
        }

        // Create pending payment record
        $reference    = $this->wompi->generateReference($user->id, $plan->id);
        $amountCents  = $this->wompi->amountInCents($plan);   // price_cop * 100
        $amountCop    = $plan->price_cop;                      // e.g. 119900
        $amountUsd    = (float) $plan->monthly_price;          // e.g. 29.99

        // Build checkout URL before persisting so it's saved for audit/retry
        $redirectUrl = route('pagos.resultado');
        $checkoutUrl = $this->wompi->buildCheckoutUrl($reference, $amountCents, $redirectUrl);

        Payment::create([
            'user_id'      => $user->id,
            'plan_id'      => $plan->id,
            'provider'     => 'wompi',
            'reference'    => $reference,
            'amount'       => $amountCents,                    // COP cents for Wompi
            'currency'     => config('services.wompi.currency', 'COP'),
            'status'       => 'pending',
            'checkout_url' => $checkoutUrl,
            'raw_payload'  => [                                // pricing audit trail
                'amount_cop'       => $amountCop,
                'amount_in_cents'  => $amountCents,
                'amount_usd'       => $amountUsd,
                'currency'         => 'COP',
                'provider'         => 'wompi',
                'pricing_note'     => "USD {$amountUsd} ≈ COP {$amountCop} (precio fijo Colombia)",
            ],
        ]);

        Log::info('PaymentController: checkout initiated', [
            'user_id'         => $user->id,
            'plan'            => $plan->slug,
            'reference'       => $reference,
            'amount_in_cents' => $amountCents,
            'amount_cop'      => $amountCop,
            'amount_usd'      => $amountUsd,
        ]);

        return redirect()->away($checkoutUrl);
    }

    /**
     * Unified result page — Wompi redirects here after payment attempt.
     * Wompi appends ?id={transactionId} to the redirect URL.
     * Reads the payment status and shows the appropriate view.
     */
    public function resultado(Request $request): View
    {
        // Wompi appends ?id=TRANSACTION_ID on redirect
        $txId      = $request->query('id');
        $isFree    = session('free', false);
        $planName  = session('plan_name');

        $payment = null;

        if ($txId) {
            // Match by Wompi's transaction ID stored after webhook
            $payment = Payment::where('provider_payment_id', $txId)->with('plan')->first();
        }

        if (!$payment && ($ref = $request->query('reference'))) {
            $payment = Payment::where('reference', $ref)->with('plan')->first();
        }

        $status = $payment?->status ?? ($isFree ? 'approved' : 'pending');

        return view('payments.resultado', compact('payment', 'status', 'isFree', 'planName'));
    }

    /** Page shown after Wompi redirects back on success/pending. */
    public function success(Request $request): View
    {
        $planName = session('plan_name');
        $isFree   = session('free', false);

        // Try to find the payment from Wompi's return parameters
        $reference = $request->query('id') ?? $request->query('reference');
        $payment   = $reference
            ? Payment::where('reference', $reference)->with('plan')->first()
            : null;

        return view('payments.success', compact('planName', 'isFree', 'payment'));
    }

    /** Page shown for pending payments. */
    public function pending(Request $request): View
    {
        $reference = $request->query('id') ?? $request->query('reference');
        $payment   = $reference
            ? Payment::where('reference', $reference)->with('plan')->first()
            : null;

        return view('payments.pending', compact('payment'));
    }

    /** Page shown for failed/rejected payments. */
    public function failed(Request $request): View
    {
        $reference = $request->query('id') ?? $request->query('reference');
        $payment   = $reference
            ? Payment::where('reference', $reference)->with('plan')->first()
            : null;

        return view('payments.failed', compact('payment'));
    }

    /** Activate free plan without going through payment gateway. */
    private function activatePlanDirectly(int $userId, Plan $plan): void
    {
        DB::transaction(function () use ($userId, $plan) {
            // Update user plan
            \App\Models\User::where('id', $userId)->update(['plan_id' => $plan->id]);

            // Update or create subscription
            Subscription::updateOrCreate(
                ['user_id' => $userId],
                [
                    'plan_id'              => $plan->id,
                    'status'               => 'active',
                    'provider'             => 'internal',
                    'current_period_start' => now(),
                    'current_period_end'   => now()->addMonth(),
                    'cancel_at_period_end' => false,
                ]
            );

            // Update AI credits
            UserAiCredit::updateOrCreate(
                ['user_id' => $userId],
                [
                    'plan_id'           => $plan->id,
                    'credits_available' => $plan->monthly_ai_credits,
                    'credits_used'      => 0,
                    'period_starts_at'  => now(),
                    'period_ends_at'    => now()->addMonth(),
                ]
            );
        });

        Log::info('PaymentController: free plan activated', [
            'user_id' => $userId,
            'plan'    => $plan->slug,
        ]);
    }
}
