<?php

namespace App\Contexts\ScrumCore\Infrastructure\Adapters;

use App\Contexts\ScrumCore\Domain\Entities\TaskEntity;
use App\Contexts\ScrumCore\Domain\Ports\TaskRepositoryInterface;
use App\Models\Task;
use DateTimeImmutable;

/**
 * Adaptador de infraestructura para tareas Scrum usando Eloquent.
 */
class EloquentTaskRepository implements TaskRepositoryInterface
{
    public function findById(int $taskId): ?TaskEntity
    {
        $task = Task::with('status')->find($taskId);

        return $task ? $this->toEntity($task) : null;
    }

    public function updateStatus(int $taskId, int $statusId): bool
    {
        $task = Task::find($taskId);
        if (! $task) {
            // Early return si la tarea no existe en el almacenamiento
            return false;
        }

        return $task->update(['task_status_id' => $statusId]);
    }

    /**
     * @return list<TaskEntity>
     */
    public function listByProject(int $projectId): array
    {
        return Task::where('proyecto_id', $projectId)
            ->with(['status', 'assignedTo', 'userStory'])
            ->get()
            ->map(fn (Task $task) => $this->toEntity($task))
            ->values()
            ->all();
    }

    private function toEntity(Task $task): TaskEntity
    {
        return new TaskEntity(
            id: (int) $task->id,
            projectId: (int) $task->proyecto_id,
            titulo: (string) $task->titulo,
            descripcion: $task->descripcion,
            taskStatusId: $task->task_status_id !== null ? (int) $task->task_status_id : null,
            statusName: $task->relationLoaded('status') ? $task->status?->nombre : null,
            sprintId: $task->sprint_id !== null ? (int) $task->sprint_id : null,
            assignedToId: $task->assigned_to !== null ? (int) $task->assigned_to : null,
            fechaLimite: $task->fecha_limite
                ? DateTimeImmutable::createFromInterface($task->fecha_limite)
                : null
        );
    }
}
