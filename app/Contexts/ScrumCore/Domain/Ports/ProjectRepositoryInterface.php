<?php

namespace App\Contexts\ScrumCore\Domain\Ports;

use App\Contexts\ScrumCore\Domain\Entities\ProjectEntity;

/**
 * Puerto de salida para persistencia y consulta del agregado Proyecto.
 */
interface ProjectRepositoryInterface
{
    /**
     * Obtiene un proyecto por su identificador primario.
     */
    public function findById(int $id): ?ProjectEntity;

    /**
     * Obtiene un proyecto accesible por el usuario con sus relaciones principales cargadas.
     */
    public function findAccessibleByIdWithDetails(int $id, int $userId): ?ProjectEntity;

    /**
     * Lista los proyectos accesibles para el usuario según el scope de seguridad.
     *
     * @return list<ProjectEntity>
     */
    public function listAccessibleByUser(int $userId): array;

    /**
     * Persiste un nuevo proyecto asociado al usuario creador.
     */
    public function create(int $userId, array $data): ProjectEntity;

    /**
     * Actualiza la información de un proyecto existente y retorna su estado actualizado.
     */
    public function update(int $projectId, array $data): ProjectEntity;

    /**
     * Elimina un proyecto de la persistencia.
     */
    public function delete(int $projectId): bool;
}
