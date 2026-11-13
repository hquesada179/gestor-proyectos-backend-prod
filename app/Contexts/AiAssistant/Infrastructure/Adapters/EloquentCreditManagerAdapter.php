<?php

namespace App\Contexts\AiAssistant\Infrastructure\Adapters;

use App\Contexts\AiAssistant\Domain\Ports\CreditManagerInterface;
use App\Models\UserAiCredit;
use App\Services\Ai\AiCreditService;
use Illuminate\Support\Facades\DB;

/**
 * Adaptador de infraestructura para gestión de créditos de IA.
 */
class EloquentCreditManagerAdapter implements CreditManagerInterface
{
    public function __construct(
        private readonly AiCreditService $creditService
    ) {}

    public function hasEnoughCredits(int $userId, int $requiredCredits = 1): bool
    {
        $check = $this->creditService->canUseAi($userId, 'generar_proyecto');

        return (bool) ($check['allowed'] ?? false);
    }

    public function deductCredits(int $userId, int $credits, string $action): bool
    {
        // Se aplica transacción con bloqueo pesimista para evitar condiciones de carrera
        return DB::transaction(function () use ($userId, $credits) {
            $record = UserAiCredit::where('user_id', $userId)->lockForUpdate()->first();

            // Verificación defensiva de saldo disponible
            if (! $record || $record->credits_available < $credits) {
                return false;
            }

            $record->decrement('credits_available', $credits);
            $record->increment('credits_used', $credits);

            return true;
        });
    }
}
