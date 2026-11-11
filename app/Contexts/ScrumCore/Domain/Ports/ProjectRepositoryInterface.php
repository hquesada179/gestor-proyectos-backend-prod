<?php

namespace App\Contexts\ScrumCore\Domain\Ports;

use App\Models\Proyecto;
use Illuminate\Support\Collection;

/**
 * Puerto de salida para persistencia y consulta del agregado Proyecto.
 */
interface ProjectRepositoryInterface
{
    /**
     * Obtiene un proyecto por su identificador primario.
     */
    public function findById(int $id): ?Proyecto;

    /**
     * Lista los proyectos accesibles para el usuario según el scope de seguridad.
     */
    public function listAccessibleByUser(int $userId): Collection;

    /**
     * Persiste un nuevo proyecto asociado al usuario creador.
     */
    public function create(int $userId, array $data): Proyecto;

    /**
     * Actualiza la información de un proyecto existente.
     */
    public function update(Proyecto $proyecto, array $data): bool;

    /**
     * Elimina un proyecto de la persistencia.
     */
    public function delete(Proyecto $proyecto): bool;
}
