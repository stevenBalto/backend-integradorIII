<?php

declare(strict_types=1);

namespace App\DTOs\Categoria;

final class CrearCategoriaDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly ?string $descripcion,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            nombre: (string) $data['nombre'],
            descripcion: isset($data['descripcion']) && $data['descripcion'] !== ''
                ? (string) $data['descripcion']
                : null,
        );
    }

    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
        ];
    }
}
