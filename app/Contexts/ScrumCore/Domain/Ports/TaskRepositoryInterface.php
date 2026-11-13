<?php

namespace App\Contexts\ScrumCore\Domain\Ports;

use App\Models\Task;
use Illuminate\Support\Collection;

/**
 * Puerto de salida para persistencia y operaciones sobre tareas Scrum.
 */
interface TaskRepositoryInterface
{
    /**
     * Obtiene una tarea por identificador.
     */
    public function findById(int $taskId): ?Task;

    /**
     * Actualiza el estado Kanban de una tarea específica.
     */
    public function updateStatus(int $taskId, int $statusId): bool;

    /**
     * Lista tareas asociadas a un proyecto.
     */
    public function listByProject(int $projectId): Collection;
}
