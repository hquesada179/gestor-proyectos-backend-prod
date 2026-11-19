<?php

namespace App\Contexts\ScrumCore\Application\UseCases;

use App\Contexts\ScrumCore\Domain\Entities\ProjectEntity;
use App\Contexts\ScrumCore\Domain\Ports\ProjectRepositoryInterface;

/**
 * Caso de uso: Consultar los proyectos a los que el usuario tiene acceso legítimo.
 */
class ListUserProjectsUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository
    ) {}

    /**
     * Ejecuta la consulta aplicando el filtro de pertenencia o autoría.
     *
     * @return list<ProjectEntity>
     */
    public function execute(int $userId): array
    {
        // Se delega al repositorio que garantiza el scope de autorización accesibleBy
        return $this->projectRepository->listAccessibleByUser($userId);
    }
}
