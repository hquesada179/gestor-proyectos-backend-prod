<?php

namespace App\Contexts\ScrumCore\Application\DTOs;

/**
 * Objeto de transferencia inmutable para la creación de proyectos.
 */
class CreateProjectDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $nombre,
        public readonly ?string $descripcion,
        public readonly string $estado,
        public readonly ?string $fechaInicio,
        public readonly ?string $fechaFinEstimada,
        public readonly ?string $coverImagePath = null
    ) {}

    /**
     * Construye el DTO a partir de un arreglo validado y el identificador de usuario.
     */
    public static function fromArray(int $userId, array $data): self
    {
        return new self(
            userId: $userId,
            nombre: $data['nombre'],
            descripcion: $data['descripcion'] ?? null,
            estado: $data['estado'] ?? 'borrador',
            fechaInicio: $data['fecha_inicio'] ?? null,
            fechaFinEstimada: $data['fecha_fin_estimada'] ?? null,
            coverImagePath: $data['cover_image'] ?? null
        );
    }

    /**
     * Convierte los datos a formato array apto para la persistencia.
     */
    public function toArray(): array
    {
        return array_filter([
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'estado' => $this->estado,
            'fecha_inicio' => $this->fechaInicio,
            'fecha_fin_estimada' => $this->fechaFinEstimada,
            'cover_image' => $this->coverImagePath,
        ], fn ($val) => ! is_null($val));
    }
}
