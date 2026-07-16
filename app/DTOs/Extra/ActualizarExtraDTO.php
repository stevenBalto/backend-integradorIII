<?php

declare(strict_types=1);

namespace App\DTOs\Extra;

final class ActualizarExtraDTO
{
    public function __construct(
        public readonly int $categoriaId,
        public readonly string $nombre,
        public readonly float $precio,
        public readonly bool $disponible,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            categoriaId: (int) $data['categoria_id'],
            nombre: (string) $data['nombre'],
            precio: (float) $data['precio'],
            disponible: (bool) ($data['disponible'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'categoria_id' => $this->categoriaId,
            'nombre' => $this->nombre,
            'precio' => $this->precio,
            'disponible' => $this->disponible,
        ];
    }
}
