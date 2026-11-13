<?php

namespace App\Contexts\AiAssistant\Domain\Ports;

/**
 * Puerto de salida para almacenamiento volátil o semiestructurado de borradores de IA.
 */
interface DraftStorageInterface
{
    /**
     * Persiste temporalmente un borrador generado por la IA.
     */
    public function saveDraft(int $projectId, int $userId, array $content): int;

    /**
     * Recupera un borrador por su identificador.
     */
    public function findDraft(int $draftId): ?array;

    /**
     * Marca o elimina el borrador una vez confirmado en el proyecto.
     */
    public function markAsApplied(int $draftId): bool;
}
