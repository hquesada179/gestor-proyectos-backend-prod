<?php

namespace App\Contexts\ScrumCore\Infrastructure\Adapters;

use App\Contexts\ScrumCore\Domain\Entities\ProjectEntity;
use App\Contexts\ScrumCore\Domain\Ports\ProjectRepositoryInterface;
use App\Models\Proyecto;
use App\Models\User;
use DateTimeImmutable;

/**
 * Adaptador de infraestructura que implementa ProjectRepositoryInterface usando Eloquent ORM.
 */
class EloquentProjectRepository implements ProjectRepositoryInterface
{
    /**
     * Localiza un proyecto por su clave primaria.
     */
    public function findById(int $id): ?ProjectEntity
    {
        $proyecto = Proyecto::find($id);

        return $proyecto ? $this->toEntity($proyecto) : null;
    }

    /**
     * Obtiene un proyecto accesible para el usuario cargando conteos y detalles de sprints/tareas.
     */
    public function findAccessibleByIdWithDetails(int $id, int $userId): ?ProjectEntity
    {
        // El scope accessibleBy asegura que solo dueño o miembros activos accedan al proyecto
        $proyecto = Proyecto::accessibleBy($userId)
            ->withCount(['tasks', 'sprints', 'requirements'])
            ->with([
                'sprints' => fn ($q) => $q->withCount('tasks')->orderBy('nombre'),
                'tasks.status',
            ])
            ->find($id);

        return $proyecto ? $this->toEntity($proyecto) : null;
    }

    /**
     * Retorna proyectos donde el usuario es propietario o miembro activo.
     *
     * @return list<ProjectEntity>
     */
    public function listAccessibleByUser(int $userId): array
    {
        // Se aplica el scope de seguridad canónico del proyecto
        return Proyecto::accessibleBy($userId)
            ->withCount(['tasks', 'sprints', 'requirements'])
            ->latest()
            ->get()
            ->map(fn (Proyecto $proyecto) => $this->toEntity($proyecto))
            ->values()
            ->all();
    }

    /**
     * Crea un proyecto vinculado al usuario dueño.
     */
    public function create(int $userId, array $data): ProjectEntity
    {
        /** @var User $user */
        $user = User::findOrFail($userId);

        // Se usa la relación Eloquent para forzar la asignación del user_id propietario
        $proyecto = $user->proyectos()->create($data);

        return $this->toEntity($proyecto);
    }

    /**
     * Actualiza atributos del proyecto y devuelve su entidad fresca.
     */
    public function update(int $projectId, array $data): ProjectEntity
    {
        $proyecto = Proyecto::findOrFail($projectId);
        $proyecto->update($data);

        return $this->toEntity($proyecto->fresh());
    }

    /**
     * Elimina el proyecto y sus recursos subordinados.
     */
    public function delete(int $projectId): bool
    {
        $proyecto = Proyecto::find($projectId);
        if (! $proyecto) {
            return false;
        }

        return (bool) $proyecto->delete();
    }

    private function toEntity(Proyecto $proyecto): ProjectEntity
    {
        // Solo mapeamos relaciones sprints y tasks si fueron precargadas para evitar consultas N+1
        $sprints = $proyecto->relationLoaded('sprints')
            ? $proyecto->sprints->map(fn ($s) => [
                'id' => (int) $s->id,
                'nombre' => $s->nombre,
                'objetivo' => $s->objetivo,
                'estado' => $s->estado,
                'fecha_inicio' => $s->fecha_inicio?->toDateString(),
                'fecha_fin' => $s->fecha_fin?->toDateString(),
                'tasks_count' => (int) ($s->tasks_count ?? 0),
            ])->values()->all()
            : [];

        $tasks = $proyecto->relationLoaded('tasks')
            ? $proyecto->tasks->map(fn ($t) => [
                'id' => (int) $t->id,
                'titulo' => $t->titulo,
                'descripcion' => $t->descripcion,
                'estado' => $t->status?->nombre,
                'fecha_limite' => $t->fecha_limite?->toDateString(),
                'sprint_id' => $t->sprint_id,
            ])->values()->all()
            : [];

        return new ProjectEntity(
            id: (int) $proyecto->id,
            userId: (int) $proyecto->user_id,
            nombre: (string) $proyecto->nombre,
            descripcion: $proyecto->descripcion,
            estado: (string) ($proyecto->estado ?? 'activo'),
            fechaInicio: $proyecto->fecha_inicio
                ? DateTimeImmutable::createFromInterface($proyecto->fecha_inicio)
                : null,
            fechaFinEstimada: $proyecto->fecha_fin_estimada
                ? DateTimeImmutable::createFromInterface($proyecto->fecha_fin_estimada)
                : null,
            coverImage: $proyecto->cover_image,
            tasksCount: (int) ($proyecto->tasks_count ?? 0),
            sprintsCount: (int) ($proyecto->sprints_count ?? 0),
            requirementsCount: (int) ($proyecto->requirements_count ?? 0),
            sprints: $sprints,
            tasks: $tasks,
            createdAt: $proyecto->created_at
                ? DateTimeImmutable::createFromInterface($proyecto->created_at)
                : null
        );
    }
}
