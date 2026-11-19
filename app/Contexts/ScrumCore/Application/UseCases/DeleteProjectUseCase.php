<?php

namespace App\Contexts\ScrumCore\Application\UseCases;

use App\Contexts\ScrumCore\Domain\Ports\ProjectAuditLoggerInterface;
use App\Contexts\ScrumCore\Domain\Ports\ProjectRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use RuntimeException;

/**
 * Caso de uso: Eliminar un proyecto verificando propiedad y registrando auditoría previa.
 */
class DeleteProjectUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly ProjectAuditLoggerInterface $auditLogger
    ) {}

    /**
     * Audita la eliminación y remueve el proyecto del repositorio.
     */
    public function execute(int $projectId, int $userId): bool
    {
        $proyecto = $this->projectRepository->findById($projectId);
        if (! $proyecto) {
            throw new RuntimeException('Proyecto no encontrado.');
        }

        // Solo el usuario dueño puede eliminar permanentemente el proyecto
        if (! $proyecto->isOwnedBy($userId)) {
            throw new AuthorizationException('No tienes permiso para eliminar este proyecto.');
        }

        // La traza se registra antes de eliminar para conservar la referencia válida del registro
        $this->auditLogger->logProjectAction(
            $proyecto->id,
            'deleted',
            "Proyecto eliminado: «{$proyecto->nombre}»"
        );

        return $this->projectRepository->delete($proyecto->id);
    }
}
