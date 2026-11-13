<?php

namespace App\Contexts\ScrumCore\Infrastructure\Adapters;

use App\Contexts\ScrumCore\Domain\Ports\ProjectRepositoryInterface;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Adaptador de infraestructura que implementa ProjectRepositoryInterface usando Eloquent ORM.
 */
class EloquentProjectRepository implements ProjectRepositoryInterface
{
    /**
     * Localiza un proyecto por su clave primaria.
     */
    public function findById(int $id): ?Proyecto
    {
        return Proyecto::find($id);
    }

    /**
     * Retorna proyectos donde el usuario es propietario o miembro activo.
     */
    public function listAccessibleByUser(int $userId): Collection
    {
        // Se aplica el scope de seguridad canónico del proyecto
        return Proyecto::accessibleBy($userId)
            ->withCount(['tasks', 'sprints', 'requirements'])
            ->latest()
            ->get();
    }

    /**
     * Crea un proyecto vinculado al usuario dueño.
     */
    public function create(int $userId, array $data): Proyecto
    {
        /** @var User $user */
        $user = User::findOrFail($userId);

        // Se usa la relación Eloquent para forzar la asignación del user_id propietario
        return $user->proyectos()->create($data);
    }

    /**
     * Actualiza atributos del proyecto.
     */
    public function update(Proyecto $proyecto, array $data): bool
    {
        return $proyecto->update($data);
    }

    /**
     * Elimina el proyecto y sus recursos subordinados.
     */
    public function delete(Proyecto $proyecto): bool
    {
        return (bool) $proyecto->delete();
    }
}
