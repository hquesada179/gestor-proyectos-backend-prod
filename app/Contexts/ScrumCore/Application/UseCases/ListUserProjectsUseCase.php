<?php

namespace App\Contexts\ScrumCore\Application\UseCases;

use App\Contexts\ScrumCore\Domain\Ports\ProjectRepositoryInterface;
use Illuminate\Support\Collection;

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
     */
    public function execute(int $userId): Collection
    {
        // Se delega al repositorio que garantiza el scope de autorización accesibleBy
        return $this->projectRepository->listAccessibleByUser($userId);
    }
}
