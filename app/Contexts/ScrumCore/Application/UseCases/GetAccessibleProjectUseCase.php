<?php

namespace App\Contexts\ScrumCore\Application\UseCases;

use App\Contexts\ScrumCore\Domain\Entities\ProjectEntity;
use App\Contexts\ScrumCore\Domain\Ports\ProjectRepositoryInterface;

/**
 * Caso de uso: Obtener el detalle completo de un proyecto accesible por el usuario.
 */
class GetAccessibleProjectUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository
    ) {}

    /**
     * Retorna el proyecto con sus relaciones si es accesible por el usuario, o null en caso contrario.
     */
    public function execute(int $projectId, int $userId): ?ProjectEntity
    {
        return $this->projectRepository->findAccessibleByIdWithDetails($projectId, $userId);
    }
}
