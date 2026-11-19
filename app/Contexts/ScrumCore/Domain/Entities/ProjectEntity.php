<?php

namespace App\Contexts\ScrumCore\Domain\Entities;

use DateTimeImmutable;

/**
 * Entidad de dominio pura del agregado Proyecto, independiente de Eloquent.
 */
final class ProjectEntity
{
    /**
     * @param  list<array<string, mixed>>  $sprints
     * @param  list<array<string, mixed>>  $tasks
     */
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $nombre,
        public readonly ?string $descripcion,
        public readonly string $estado,
        public readonly ?DateTimeImmutable $fechaInicio = null,
        public readonly ?DateTimeImmutable $fechaFinEstimada = null,
        public readonly ?string $coverImage = null,
        public readonly int $tasksCount = 0,
        public readonly int $sprintsCount = 0,
        public readonly int $requirementsCount = 0,
        public readonly array $sprints = [],
        public readonly array $tasks = [],
        public readonly ?DateTimeImmutable $createdAt = null
    ) {}

    public function isOwnedBy(int $userId): bool
    {
        return $this->userId === $userId;
    }

    /**
     * Extrae los campos auditables del proyecto para comparación en logs de actividad.
     *
     * @return array<string, mixed>
     */
    public function auditableSnapshot(): array
    {
        return [
            'nombre' => $this->nombre,
            'estado' => $this->estado,
            'descripcion' => $this->descripcion,
            'fecha_inicio' => $this->fechaInicio?->format('Y-m-d'),
            'fecha_fin_estimada' => $this->fechaFinEstimada?->format('Y-m-d'),
        ];
    }
}
