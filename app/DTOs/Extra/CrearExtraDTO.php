<?php

declare(strict_types=1);

namespace App\DTOs\Extra;

final class CrearExtraDTO
{
    public function __construct(
        public readonly ?int $categoriaId,
        public readonly string $nombre,
        public readonly float $precio,
        public readonly bool $disponible,
        public readonly bool $esGeneral,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $esGeneral = (bool) ($data['es_general'] ?? false);

        return new self(
            categoriaId: $esGeneral
                ? null
                : (isset($data['categoria_id']) ? (int) $data['categoria_id'] : null),
            nombre: (string) $data['nombre'],
            precio: (float) $data['precio'],
            disponible: (bool) ($data['disponible'] ?? true),
            esGeneral: $esGeneral,
        );
    }

    public function toArray(): array
    {
        return [
            'categoria_id' => $this->categoriaId,
            'nombre' => $this->nombre,
            'precio' => $this->precio,
            'disponible' => $this->disponible,
            'es_general' => $this->esGeneral,
        ];
    }
}
