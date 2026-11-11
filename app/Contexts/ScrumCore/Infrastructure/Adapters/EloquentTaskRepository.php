<?php

namespace App\Contexts\ScrumCore\Infrastructure\Adapters;

use App\Contexts\ScrumCore\Domain\Ports\TaskRepositoryInterface;
use App\Models\Task;
use Illuminate\Support\Collection;

/**
 * Adaptador de infraestructura para tareas Scrum usando Eloquent.
 */
class EloquentTaskRepository implements TaskRepositoryInterface
{
    public function findById(int $taskId): ?Task
    {
        return Task::find($taskId);
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

    public function listByProject(int $projectId): Collection
    {
        return Task::where('proyecto_id', $projectId)
            ->with(['status', 'assignedTo', 'userStory'])
            ->get();
    }
}
