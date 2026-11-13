<?php

namespace App\Contexts\Billing\Application\UseCases;

use App\Contexts\Billing\Application\DTOs\ProcessWebhookDTO;
use App\Contexts\Billing\Domain\Ports\PaymentGatewayInterface;
use App\Contexts\Billing\Domain\Ports\SubscriptionRepositoryInterface;
use App\Models\Payment;
use App\Models\PaymentEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Caso de uso: validar y aplicar los eventos de transacción emitidos por Wompi.
 */
class ProcessWompiWebhookUseCase
{
    public function __construct(
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly SubscriptionRepositoryInterface $subscriptionRepo
    ) {}

    /**
     * Valida el evento, lo registra y aplica la transición de estado del pago.
     *
     * @return array{ok: bool, msg?: string}
     */
    public function execute(ProcessWebhookDTO $dto): array
    {
        // 1. Idempotencia: un evento ya procesado no se vuelve a aplicar.
        if ($dto->providerEventId !== null) {
            $existing = PaymentEvent::where('provider_event_id', $dto->providerEventId)->first();

            if ($existing?->isProcessed()) {
                Log::info('ProcessWompiWebhookUseCase: evento duplicado ignorado.', [
                    'provider_event_id' => $dto->providerEventId,
                ]);

                return ['ok' => true, 'msg' => 'duplicate'];
            }
        }

        // 2. Se registra el evento antes de validar la firma para conservar la auditoría.
        $event = $this->storeEvent($dto);

        // 3. La firma es obligatoria; un evento inválido no modifica pagos.
        if (! $this->paymentGateway->validateWebhookSignature($dto->rawPayload)) {
            Log::warning('ProcessWompiWebhookUseCase: firma inválida en webhook de Wompi.');

            return ['ok' => false, 'msg' => 'invalid_signature'];
        }

        // 4. Bloqueamos evento y pago dentro de una transacción para evitar condiciones de carrera.
        try {
            return DB::transaction(function () use ($dto, $event) {
                $lockedEvent = PaymentEvent::whereKey($event->id)->lockForUpdate()->first();

                if (! $lockedEvent || $lockedEvent->isProcessed()) {
                    return ['ok' => true, 'msg' => 'duplicate'];
                }

                // 5. Solo los eventos de transacción actualizan el estado del pago.
                if ($dto->event !== 'transaction.updated') {
                    $lockedEvent->update(['processed_at' => now()]);

                    return ['ok' => true, 'msg' => 'event_ignored'];
                }

                $lockedPayment = $dto->reference
                    ? Payment::where('reference', $dto->reference)->lockForUpdate()->first()
                    : null;

                if (! $lockedPayment) {
                    $lockedEvent->update(['processed_at' => now()]);

                    return ['ok' => false, 'msg' => 'payment_not_found'];
                }

                // Revalidamos dentro del lock por si otro webhook concurrente ya aprobó el pago.
                if ($lockedPayment->isApproved()) {
                    $lockedEvent->update(['processed_at' => now()]);

                    return ['ok' => true, 'msg' => 'already_approved'];
                }

                // Validamos monto y moneda antes de aprobar o activar beneficios del plan.
                if ($mismatchReason = $this->validateApprovedAmounts($lockedPayment, $dto)) {
                    $lockedEvent->update(['processed_at' => now()]);

                    return ['ok' => false, 'msg' => $mismatchReason];
                }

                $this->applyTransition($lockedPayment, $dto);
                $lockedEvent->update(['processed_at' => now()]);

                return ['ok' => true];
            });
        } catch (\Throwable $e) {
            // Wompi no debe reintentar por errores de lógica; se registra y se responde 200.
            Log::error('ProcessWompiWebhookUseCase: error al procesar el evento.', [
                'reference' => $dto->reference,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'msg' => 'processing_error'];
        }
    }

    /**
     * Guarda el evento una sola vez por provider_event_id.
     */
    private function storeEvent(ProcessWebhookDTO $dto): PaymentEvent
    {
        $attributes = [
            'provider' => 'wompi',
            'event_type' => $dto->event,
            'payload' => $dto->rawPayload,
        ];

        if ($dto->providerEventId === null) {
            return PaymentEvent::create($attributes);
        }

        try {
            return PaymentEvent::firstOrCreate(
                ['provider_event_id' => $dto->providerEventId],
                $attributes
            );
        } catch (QueryException $e) {
            // Si dos peticiones concurrentes insertan el mismo provider_event_id, recuperamos el ganador.
            $existing = PaymentEvent::where('provider_event_id', $dto->providerEventId)->first();
            if ($existing) {
                return $existing;
            }

            throw $e;
        }
    }

    /**
     * Verifica que el monto en centavos y la moneda coincidan con el pago esperado antes de aprobar.
     */
    private function validateApprovedAmounts(Payment $payment, ProcessWebhookDTO $dto): ?string
    {
        if ($dto->status !== 'APPROVED') {
            return null;
        }

        $expectedAmount = (int) $payment->amount;
        if ($dto->amountInCents <= 0 || $dto->amountInCents !== $expectedAmount) {
            Log::warning('ProcessWompiWebhookUseCase: monto inconsistente en webhook aprobado.', [
                'payment_id' => $payment->id,
                'reference' => $payment->reference,
                'expected_amount' => $expectedAmount,
                'received_amount' => $dto->amountInCents,
            ]);

            return 'amount_mismatch';
        }

        // Rechazamos también moneda ausente o distinta en aprobaciones para evitar cobros en otra divisa.
        $expectedCurrency = strtoupper((string) ($payment->currency ?: 'COP'));
        if ($dto->currency === null || strtoupper(trim($dto->currency)) !== $expectedCurrency) {
            Log::warning('ProcessWompiWebhookUseCase: moneda inconsistente en webhook aprobado.', [
                'payment_id' => $payment->id,
                'reference' => $payment->reference,
                'expected_currency' => $expectedCurrency,
                'received_currency' => $dto->currency,
            ]);

            return 'currency_mismatch';
        }

        return null;
    }

    /**
     * Aplica el estado de la transacción al pago y, si corresponde, activa el plan.
     */
    private function applyTransition(Payment $payment, ProcessWebhookDTO $dto): void
    {
        if ($dto->status === 'APPROVED') {
            $subscription = $this->subscriptionRepo->activatePlanForUser(
                (int) $payment->user_id,
                (int) $payment->plan_id,
                (string) ($dto->transactionId ?? $dto->reference)
            );

            $payment->update([
                'status' => 'approved',
                'provider_payment_id' => $dto->transactionId,
                'raw_payload' => $dto->rawPayload['data']['transaction'] ?? $dto->rawPayload,
                'paid_at' => now(),
                'subscription_id' => $subscription->id,
            ]);

            return;
        }

        if (in_array($dto->status, ['DECLINED', 'VOIDED', 'ERROR'], true)) {
            $payment->update([
                'status' => 'rejected',
                'raw_payload' => $dto->rawPayload['data']['transaction'] ?? $dto->rawPayload,
            ]);
        }
        // PENDING: se espera el siguiente evento sin tocar el pago.
    }
}
