<?php

namespace Tests\Unit\Contexts\Billing;

use App\Contexts\Billing\Application\DTOs\ProcessWebhookDTO;
use App\Contexts\Billing\Application\UseCases\ProcessWompiWebhookUseCase;
use App\Contexts\Billing\Domain\Ports\PaymentGatewayInterface;
use App\Contexts\Billing\Domain\Ports\SubscriptionRepositoryInterface;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProcessWompiWebhookUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_rejects_an_invalid_signature_without_changing_the_payment(): void
    {
        [$user, $plan, $payment] = $this->createPendingPayment();
        $payload = $this->approvedPayload($payment->reference);

        $gatewayMock = Mockery::mock(PaymentGatewayInterface::class);
        $gatewayMock->shouldReceive('validateWebhookSignature')
            ->once()
            ->with($payload)
            ->andReturn(false);

        $subscriptionMock = Mockery::mock(SubscriptionRepositoryInterface::class);

        $useCase = new ProcessWompiWebhookUseCase($gatewayMock, $subscriptionMock);
        $result = $useCase->execute(ProcessWebhookDTO::fromPayload($payload));

        $this->assertFalse($result['ok']);
        $this->assertSame('invalid_signature', $result['msg']);
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame(1, PaymentEvent::count());
        $this->assertNull(PaymentEvent::first()->processed_at);
    }

    public function test_it_activates_the_subscription_on_an_approved_transaction(): void
    {
        [$user, $plan, $payment] = $this->createPendingPayment();
        $payload = $this->approvedPayload($payment->reference);

        $gatewayMock = Mockery::mock(PaymentGatewayInterface::class);
        $gatewayMock->shouldReceive('validateWebhookSignature')
            ->once()
            ->with($payload)
            ->andReturn(true);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $subscriptionMock = Mockery::mock(SubscriptionRepositoryInterface::class);
        $subscriptionMock->shouldReceive('activatePlanForUser')
            ->once()
            ->with($user->id, $plan->id, 'tx-999')
            ->andReturn($subscription);

        $useCase = new ProcessWompiWebhookUseCase($gatewayMock, $subscriptionMock);
        $result = $useCase->execute(ProcessWebhookDTO::fromPayload($payload));

        $this->assertTrue($result['ok']);
        $payment->refresh();
        $this->assertSame('approved', $payment->status);
        $this->assertSame($subscription->id, $payment->subscription_id);
        $this->assertNotNull($payment->paid_at);
        $this->assertNotNull(PaymentEvent::first()->processed_at);
    }

    public function test_it_ignores_a_duplicate_processed_event(): void
    {
        [$user, $plan, $payment] = $this->createPendingPayment();
        $payload = $this->approvedPayload($payment->reference);

        $gatewayMock = Mockery::mock(PaymentGatewayInterface::class);
        $gatewayMock->shouldReceive('validateWebhookSignature')
            ->once()
            ->andReturn(true);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $subscriptionMock = Mockery::mock(SubscriptionRepositoryInterface::class);
        $subscriptionMock->shouldReceive('activatePlanForUser')
            ->once()
            ->andReturn($subscription);

        $useCase = new ProcessWompiWebhookUseCase($gatewayMock, $subscriptionMock);
        $dto = ProcessWebhookDTO::fromPayload($payload);

        $first = $useCase->execute($dto);
        $second = $useCase->execute($dto);

        $this->assertTrue($first['ok']);
        $this->assertSame('duplicate', $second['msg']);
        $this->assertSame(1, PaymentEvent::count());
    }

    public function test_it_rejects_approved_event_when_amount_mismatches(): void
    {
        [$user, $plan, $payment] = $this->createPendingPayment();
        $payload = $this->approvedPayload($payment->reference, 1000, 'COP');

        $gatewayMock = Mockery::mock(PaymentGatewayInterface::class);
        $gatewayMock->shouldReceive('validateWebhookSignature')->once()->andReturn(true);

        $subscriptionMock = Mockery::mock(SubscriptionRepositoryInterface::class);
        $subscriptionMock->shouldNotReceive('activatePlanForUser');

        $useCase = new ProcessWompiWebhookUseCase($gatewayMock, $subscriptionMock);
        $result = $useCase->execute(ProcessWebhookDTO::fromPayload($payload));

        $this->assertFalse($result['ok']);
        $this->assertSame('amount_mismatch', $result['msg']);
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertNotNull(PaymentEvent::first()->processed_at);
    }

    public function test_it_rejects_approved_event_when_currency_mismatches_or_is_missing(): void
    {
        [$user, $plan, $payment] = $this->createPendingPayment();

        $gatewayMock = Mockery::mock(PaymentGatewayInterface::class);
        $gatewayMock->shouldReceive('validateWebhookSignature')->twice()->andReturn(true);

        $subscriptionMock = Mockery::mock(SubscriptionRepositoryInterface::class);
        $subscriptionMock->shouldNotReceive('activatePlanForUser');

        $useCase = new ProcessWompiWebhookUseCase($gatewayMock, $subscriptionMock);

        $wrongCurrency = $this->approvedPayload($payment->reference, 3990000, 'USD');
        $wrongCurrency['data']['transaction']['id'] = 'tx-usd';
        $missingCurrency = $this->approvedPayload($payment->reference, 3990000, null);
        $missingCurrency['data']['transaction']['id'] = 'tx-no-currency';

        $resUsd = $useCase->execute(ProcessWebhookDTO::fromPayload($wrongCurrency));
        $resMissing = $useCase->execute(ProcessWebhookDTO::fromPayload($missingCurrency));

        $this->assertFalse($resUsd['ok']);
        $this->assertSame('currency_mismatch', $resUsd['msg']);
        $this->assertFalse($resMissing['ok']);
        $this->assertSame('currency_mismatch', $resMissing['msg']);
        $this->assertSame('pending', $payment->fresh()->status);
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

    private function approvedPayload(string $reference, int $amountInCents = 3990000, ?string $currency = 'COP'): array
    {
        $transaction = [
            'id' => 'tx-999',
            'status' => 'APPROVED',
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
        ];
    }
}
