<?php

namespace App\Contexts\ScrumCore\Application\UseCases;

use App\Contexts\ScrumCore\Application\DTOs\CreateProjectDTO;
use App\Contexts\ScrumCore\Domain\Ports\ProjectRepositoryInterface;
use App\Models\Proyecto;
use App\Services\ProjectActivityLogger;

/**
 * Caso de uso: Registrar y auditar un nuevo proyecto en el sistema.
 */
class CreateProjectUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository
    ) {}

    /**
     * Orquesta la creación del proyecto y su auditoría.
     */
    public function execute(CreateProjectDTO $dto): Proyecto
    {
        // Se persiste el proyecto asociándolo a su creador mediante el repositorio
        $proyecto = $this->projectRepository->create($dto->userId, $dto->toArray());

        // Se registra la traza de actividad de negocio en el log de auditoría
        ProjectActivityLogger::log(
            $proyecto,
            'created',
            'proyectos',
            "Proyecto creado: «{$proyecto->nombre}»",
            null,
            $proyecto
        );

        return $proyecto;
    }
}
