<?php

namespace App\Contexts\ScrumCore\Application\UseCases;

use App\Contexts\ScrumCore\Application\DTOs\CreateProjectDTO;
use App\Contexts\ScrumCore\Domain\Entities\ProjectEntity;
use App\Contexts\ScrumCore\Domain\Ports\ProjectAuditLoggerInterface;
use App\Contexts\ScrumCore\Domain\Ports\ProjectRepositoryInterface;

/**
 * Caso de uso: Registrar y auditar un nuevo proyecto en el sistema.
 */
class CreateProjectUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly ProjectAuditLoggerInterface $auditLogger
    ) {}

    /**
     * Orquesta la creación del proyecto y su auditoría.
     */
    public function execute(CreateProjectDTO $dto): ProjectEntity
    {
        // Se persiste el proyecto asociándolo a su creador mediante el repositorio
        $proyecto = $this->projectRepository->create($dto->userId, $dto->toArray());

        // Se registra la traza de actividad de negocio mediante el puerto de auditoría
        $this->auditLogger->logProjectAction(
            $proyecto->id,
            'created',
            "Proyecto creado: «{$proyecto->nombre}»"
        );

        return $proyecto;
    }
}
