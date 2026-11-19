<?php

namespace App\Contexts\ScrumCore\Domain\Ports;

use App\Contexts\ScrumCore\Domain\Entities\TaskEntity;

/**
 * Puerto de salida para persistencia y operaciones sobre tareas Scrum.
 */
interface TaskRepositoryInterface
{
    /**
     * Obtiene una tarea por identificador.
     */
    public function findById(int $taskId): ?TaskEntity;

    /**
     * Actualiza el estado Kanban de una tarea específica.
     */
    public function updateStatus(int $taskId, int $statusId): bool;

    /**
     * Lista tareas asociadas a un proyecto.
     *
     * @return list<TaskEntity>
     */
    public function listByProject(int $projectId): array;
}
