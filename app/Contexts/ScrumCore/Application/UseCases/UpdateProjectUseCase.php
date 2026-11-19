<?php

namespace App\Contexts\ScrumCore\Application\UseCases;

use App\Contexts\ScrumCore\Application\DTOs\UpdateProjectDTO;
use App\Contexts\ScrumCore\Domain\Entities\ProjectEntity;
use App\Contexts\ScrumCore\Domain\Ports\ProjectAuditLoggerInterface;
use App\Contexts\ScrumCore\Domain\Ports\ProjectRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use RuntimeException;

/**
 * Caso de uso: Actualizar un proyecto verificando propiedad y registrando auditoría.
 */
class UpdateProjectUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly ProjectAuditLoggerInterface $auditLogger
    ) {}

    /**
     * Valida propiedad del proyecto, persiste cambios y registra la actividad.
     */
    public function execute(UpdateProjectDTO $dto): ProjectEntity
    {
        $proyecto = $this->projectRepository->findById($dto->projectId);
        if (! $proyecto) {
            throw new RuntimeException('Proyecto no encontrado.');
        }

        // Solo el propietario del proyecto puede modificar sus metadatos principales
        if (! $proyecto->isOwnedBy($dto->userId)) {
            throw new AuthorizationException('No tienes permiso para editar este proyecto.');
        }

        $oldValues = $proyecto->auditableSnapshot();
        $updated = $this->projectRepository->update($proyecto->id, $dto->toArray());

        $this->auditLogger->logProjectAction(
            $updated->id,
            'updated',
            "Proyecto editado: «{$updated->nombre}»",
            $oldValues,
            $updated->auditableSnapshot()
        );

        return $updated;
    }
}
