<?php

namespace Tests\Unit\Contexts\Billing;

use App\Contexts\Billing\Application\DTOs\ProcessWebhookDTO;
use App\Contexts\Billing\Application\UseCases\ProcessWompiWebhookUseCase;
use App\Contexts\Billing\Domain\Ports\PaymentGatewayInterface;
use App\Contexts\Billing\Domain\Ports\SubscriptionRepositoryInterface;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ProcessWompiWebhookUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_throws_exception_on_invalid_signature(): void
    {
        $gatewayMock = Mockery::mock(PaymentGatewayInterface::class);
        $subscriptionMock = Mockery::mock(SubscriptionRepositoryInterface::class);

        $gatewayMock->shouldReceive('validateWebhookSignature')
            ->once()
            ->andReturn(false);

        $useCase = new ProcessWompiWebhookUseCase($gatewayMock, $subscriptionMock);
        $dto = new ProcessWebhookDTO(
            rawPayload: ['event' => 'transaction.updated'],
            event: 'transaction.updated',
            transactionId: 'tx-123',
            reference: 'GP-1-2-12345678',
            status: 'APPROVED',
            amountInCents: 3990000
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Firma de webhook inválida');

        $useCase->execute($dto);
    }

    public function test_it_activates_subscription_on_approved_transaction(): void
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

        $gatewayMock = Mockery::mock(PaymentGatewayInterface::class);
        $subscriptionMock = Mockery::mock(SubscriptionRepositoryInterface::class);

        $reference = "GP-{$user->id}-{$plan->id}-1731000000";

        $payload = [
            'event' => 'transaction.updated',
            'data' => [
                'transaction' => [
                    'id' => 'tx-999',
                    'status' => 'APPROVED',
                    'reference' => $reference,
                    'amount_in_cents' => 3990000,
                ],
            ],
        ];

        $gatewayMock->shouldReceive('validateWebhookSignature')
            ->once()
            ->with($payload)
            ->andReturn(true);

        $mockSubscription = new Subscription([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $subscriptionMock->shouldReceive('activatePlanForUser')
            ->once()
            ->with($user->id, $plan->id, $reference)
            ->andReturn($mockSubscription);

        $useCase = new ProcessWompiWebhookUseCase($gatewayMock, $subscriptionMock);
        $dto = ProcessWebhookDTO::fromPayload($payload);

        $result = $useCase->execute($dto);
        $this->assertTrue($result);
    }
}
