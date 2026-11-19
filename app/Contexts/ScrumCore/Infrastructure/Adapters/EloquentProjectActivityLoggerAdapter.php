<?php

namespace App\Contexts\ScrumCore\Infrastructure\Adapters;

use App\Contexts\ScrumCore\Domain\Ports\ProjectAuditLoggerInterface;
use App\Models\Proyecto;
use App\Services\ProjectActivityLogger;

/**
 * Adaptador de infraestructura que registra auditoría usando ProjectActivityLogger.
 */
class EloquentProjectActivityLoggerAdapter implements ProjectAuditLoggerInterface
{
    public function logProjectAction(
        int $projectId,
        string $action,
        string $title,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $proyecto = Proyecto::find($projectId);
        if (! $proyecto) {
            return;
        }

        // En eliminaciones pasamos null como entidad secundaria porque el registro está por borrarse
        $entity = $action === 'deleted' ? null : $proyecto;

        ProjectActivityLogger::log(
            $proyecto,
            $action,
            'proyectos',
            $title,
            null,
            $entity,
            $oldValues,
            $newValues
        );
    }
}
