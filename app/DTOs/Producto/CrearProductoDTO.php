<?php

declare(strict_types=1);

namespace App\DTOs\Producto;

final class CrearProductoDTO
{
    public function __construct(
        public readonly int $categoriaId,
        public readonly string $nombre,
        public readonly ?string $descripcion,
        public readonly float $precioBase,
        public readonly bool $destacado,
        public readonly bool $disponible,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            categoriaId: (int) $data['categoria_id'],
            nombre: (string) $data['nombre'],
            descripcion: isset($data['descripcion']) ? (string) $data['descripcion'] : null,
            precioBase: (float) $data['precio_base'],
            destacado: (bool) ($data['destacado'] ?? false),
            disponible: (bool) ($data['disponible'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'categoria_id' => $this->categoriaId,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio_base' => $this->precioBase,
            'destacado' => $this->destacado,
            'disponible' => $this->disponible,
        ];
    }
}
