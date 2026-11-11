<?php

namespace App\Contexts\Billing\Application\UseCases;

use App\Contexts\Billing\Application\DTOs\ProcessWebhookDTO;
use App\Contexts\Billing\Domain\Ports\PaymentGatewayInterface;
use App\Contexts\Billing\Domain\Ports\SubscriptionRepositoryInterface;
use App\Models\Payment;
use App\Models\PaymentEvent;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Caso de uso: Validar y aplicar los eventos de transacción emitidos por Wompi.
 */
class ProcessWompiWebhookUseCase
{
    public function __construct(
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly SubscriptionRepositoryInterface $subscriptionRepo
    ) {}

    /**
     * Valida firma y actualiza estado de pago y suscripción.
     */
    public function execute(ProcessWebhookDTO $dto): bool
    {
        // 1. Verificación obligatoria de autenticidad criptográfica mediante la firma SHA-256
        if (! $this->paymentGateway->validateWebhookSignature($dto->rawPayload)) {
            Log::warning('ProcessWompiWebhookUseCase: Firma inválida en webhook de Wompi.');
            throw new RuntimeException('Firma de webhook inválida.');
        }

        // 2. Registro del evento para trazabilidad y auditoría
        PaymentEvent::create([
            'event_type' => $dto->event,
            'payload' => $dto->rawPayload,
            'status' => $dto->status,
        ]);

        // 3. Procesar activación si el estado de la transacción es APPROVED
        if ($dto->status === 'APPROVED' && $dto->reference) {
            // Estructura de referencia generada: GP-{userId}-{planId}-{timestamp}
            $parts = explode('-', $dto->reference);

            // Validar que la referencia contenga al menos los 4 segmentos esperados
            if (count($parts) >= 4 && $parts[0] === 'GP') {
                $userId = (int) $parts[1];
                $planId = (int) $parts[2];

                // Activar el plan y recargar créditos de IA asociados
                $this->subscriptionRepo->activatePlanForUser($userId, $planId, $dto->reference);

                // Registrar o actualizar registro de pago
                Payment::updateOrCreate(
                    ['reference' => $dto->reference],
                    [
                        'user_id' => $userId,
                        'plan_id' => $planId,
                        'amount' => $dto->amountInCents,
                        'status' => 'approved',
                        'provider_payment_id' => $dto->transactionId,
                    ]
                );

                return true;
            }
        }

        return false;
    }
}
