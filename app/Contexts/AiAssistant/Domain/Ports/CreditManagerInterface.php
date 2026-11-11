<?php

namespace App\Contexts\AiAssistant\Domain\Ports;

/**
 * Puerto de salida para validación y consumo de créditos de IA.
 */
interface CreditManagerInterface
{
    /**
     * Comprueba si el usuario dispone de saldo suficiente para la acción.
     */
    public function hasEnoughCredits(int $userId, int $requiredCredits = 1): bool;

    /**
     * Descuenta los créditos tras una ejecución exitosa de la IA.
     */
    public function deductCredits(int $userId, int $credits, string $action): bool;
}
