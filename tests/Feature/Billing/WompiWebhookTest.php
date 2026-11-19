<?php

namespace Tests\Feature\Billing;

use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserAiCredit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WompiWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.wompi.events_secret' => 'test-secret']);
    }

    public function test_invalid_signature_is_audited_but_does_not_activate_the_plan(): void
    {
        [$user, $plan, $payment] = $this->createPendingPayment();

        $payload = $this->signedPayload($payment->reference);
        $payload['signature']['checksum'] = str_repeat('0', 64);

        $this->postJson('/webhooks/wompi', $payload)
            ->assertOk()
            ->assertJson(['ok' => false, 'msg' => 'invalid_signature']);

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertDatabaseCount('payment_events', 1);
        $this->assertNull(PaymentEvent::first()->processed_at);
    }

    public function test_approved_event_activates_subscription_and_reloads_credits(): void
    {
        [$user, $plan, $payment] = $this->createPendingPayment();

        $this->postJson('/webhooks/wompi', $this->signedPayload($payment->reference))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $payment->refresh();

        $this->assertSame('approved', $payment->status);
        $this->assertNotNull($payment->subscription_id);
        $this->assertNotNull($payment->paid_at);

        $subscription = Subscription::where('user_id', $user->id)->first();
        $this->assertNotNull($subscription);
        $this->assertSame('active', $subscription->status);
        $this->assertSame($plan->id, $subscription->plan_id);
        $this->assertNotNull($subscription->current_period_start);
        $this->assertNotNull($subscription->current_period_end);

        $this->assertSame($plan->id, $user->fresh()->plan_id);
        $this->assertSame($plan->monthly_ai_credits, UserAiCredit::where('user_id', $user->id)->first()->credits_available);
        $this->assertNotNull(PaymentEvent::first()->processed_at);
    }

    public function test_duplicate_event_does_not_duplicate_the_subscription_or_credits(): void
    {
        [$user, $plan, $payment] = $this->createPendingPayment();
        $payload = $this->signedPayload($payment->reference);

        $this->postJson('/webhooks/wompi', $payload)->assertOk();
        $this->postJson('/webhooks/wompi', $payload)
            ->assertOk()
            ->assertJson(['ok' => true, 'msg' => 'duplicate']);

        $this->assertSame(1, Subscription::where('user_id', $user->id)->count());
        $this->assertSame(1, PaymentEvent::count());
        $this->assertSame($plan->monthly_ai_credits, UserAiCredit::where('user_id', $user->id)->first()->credits_available);
    }

    public function test_declined_event_marks_the_payment_as_rejected(): void
    {
        [$user, $plan, $payment] = $this->createPendingPayment();

        $this->postJson('/webhooks/wompi', $this->signedPayload($payment->reference, 'DECLINED'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame('rejected', $payment->fresh()->status);
        $this->assertNull(Subscription::where('user_id', $user->id)->first());
    }

    public function test_signed_approved_event_with_tampered_amount_does_not_activate_subscription(): void
    {
        [$user, $plan, $payment] = $this->createPendingPayment();

        $this->postJson('/webhooks/wompi', $this->signedPayload($payment->reference, 'APPROVED', 10000))
            ->assertOk()
            ->assertJson(['ok' => false, 'msg' => 'amount_mismatch']);

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertNull(Subscription::where('user_id', $user->id)->first());
        $this->assertNotNull(PaymentEvent::first()->processed_at);
    }

    public function test_signed_approved_event_with_wrong_or_missing_currency_does_not_activate_subscription(): void
    {
        [$user, $plan, $payment] = $this->createPendingPayment();

        $wrongCurrency = $this->signedPayload($payment->reference, 'APPROVED', 3990000, 'USD');
        $wrongCurrency['data']['transaction']['id'] = 'tx-usd';
        $wrongCurrency['signature']['checksum'] = hash('sha256', 'tx-usd'.'APPROVED'. 3990000 .'test-secret');

        $this->postJson('/webhooks/wompi', $wrongCurrency)
            ->assertOk()
            ->assertJson(['ok' => false, 'msg' => 'currency_mismatch']);

        $missingCurrency = $this->signedPayload($payment->reference, 'APPROVED', 3990000, null);
        $missingCurrency['data']['transaction']['id'] = 'tx-missing';
        $missingCurrency['signature']['checksum'] = hash('sha256', 'tx-missing'.'APPROVED'. 3990000 .'test-secret');

        $this->postJson('/webhooks/wompi', $missingCurrency)
            ->assertOk()
            ->assertJson(['ok' => false, 'msg' => 'currency_mismatch']);

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertNull(Subscription::where('user_id', $user->id)->first());
    }

    /**
     * @return array{0: User, 1: Plan, 2: Payment}
     */
    private function createPendingPayment(): array
    {
        $user = User::factory()->create();
        $plan = Plan::create([
            'name' => 'Plan Pro',
            'slug' => 'pro',
            'monthly_price' => 39900,
            'price_cop' => 39900,
            'monthly_ai_credits' => 100,
            'daily_ai_limit' => 20,
            'minute_ai_limit' => 5,
            'max_users' => 5,
            'max_projects' => 5,
        ]);

        $payment = Payment::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'reference' => "GP-{$user->id}-{$plan->id}-1731000000",
            'amount' => 3990000,
            'currency' => 'COP',
            'status' => 'pending',
        ]);

        return [$user, $plan, $payment];
    }

    private function signedPayload(
        string $reference,
        string $status = 'APPROVED',
        int $amountInCents = 3990000,
        ?string $currency = 'COP'
    ): array {
        $transactionId = 'tx-999';

        $transaction = [
            'id' => $transactionId,
            'status' => $status,
            'reference' => $reference,
            'amount_in_cents' => $amountInCents,
        ];

        if ($currency !== null) {
            $transaction['currency'] = $currency;
        }

        return [
            'event' => 'transaction.updated',
            'sent_at' => '2026-11-11T10:00:00.000Z',
            'data' => [
                'transaction' => $transaction,
            ],
            'signature' => [
                'properties' => ['transaction.id', 'transaction.status', 'transaction.amount_in_cents'],
                'checksum' => hash('sha256', $transactionId.$status.$amountInCents.'test-secret'),
            ],
        ];
    }
}
