<?php

namespace App\Contexts\ScrumCore\Application\DTOs;

/**
 * Objeto de transferencia inmutable para la actualización de proyectos.
 */
class UpdateProjectDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly int $projectId,
        public readonly string $nombre,
        public readonly ?string $descripcion,
        public readonly string $estado,
        public readonly ?string $fechaInicio,
        public readonly ?string $fechaFinEstimada,
        public readonly bool $hasCoverImageUpdate = false,
        public readonly ?string $coverImagePath = null
    ) {}

    /**
     * Construye el DTO preservando actualizaciones explícitas de portada (incluida limpieza a null).
     */
    public static function fromArray(int $userId, int $projectId, array $data): self
    {
        return new self(
            userId: $userId,
            projectId: $projectId,
            nombre: $data['nombre'],
            descripcion: $data['descripcion'] ?? null,
            estado: $data['estado'],
            fechaInicio: $data['fecha_inicio'] ?? null,
            fechaFinEstimada: $data['fecha_fin_estimada'] ?? null,
            hasCoverImageUpdate: array_key_exists('cover_image', $data),
            coverImagePath: $data['cover_image'] ?? null
        );
    }

    /**
     * Convierte los datos editables a formato array para persistencia.
     */
    public function toArray(): array
    {
        $payload = [
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'estado' => $this->estado,
            'fecha_inicio' => $this->fechaInicio,
            'fecha_fin_estimada' => $this->fechaFinEstimada,
        ];

        // Solo sobrescribimos cover_image si hubo carga nueva o eliminación explícita
        if ($this->hasCoverImageUpdate) {
            $payload['cover_image'] = $this->coverImagePath;
        }

        return $payload;
    }
}
