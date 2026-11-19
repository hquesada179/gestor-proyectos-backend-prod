<?php

namespace App\Contexts\ScrumCore\Domain\Entities;

use DateTimeImmutable;

/**
 * Entidad de dominio pura que representa una tarea Scrum.
 */
final class TaskEntity
{
    public function __construct(
        public readonly int $id,
        public readonly int $projectId,
        public readonly string $titulo,
        public readonly ?string $descripcion,
        public readonly ?int $taskStatusId,
        public readonly ?string $statusName = null,
        public readonly ?int $sprintId = null,
        public readonly ?int $assignedToId = null,
        public readonly ?DateTimeImmutable $fechaLimite = null
    ) {}
}
