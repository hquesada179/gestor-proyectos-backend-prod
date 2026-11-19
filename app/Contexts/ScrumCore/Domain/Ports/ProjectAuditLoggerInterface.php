<?php

namespace App\Contexts\ScrumCore\Domain\Ports;

/**
 * Puerto de salida para registrar auditoría de operaciones sobre proyectos.
 */
interface ProjectAuditLoggerInterface
{
    /**
     * Registra un evento de auditoría de negocio asociado a un proyecto.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function logProjectAction(
        int $projectId,
        string $action,
        string $title,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void;
}
