<?php

declare(strict_types=1);

namespace App\DTOs\Producto;

final class CrearProductoDTO
{
    /**
     * @param array<int, array{nombre: string, precio: float, descripcion: ?string}> $tamanos
     */
    public function __construct(
        public readonly int $categoriaId,
        public readonly string $nombre,
        public readonly ?string $descripcion,
        public readonly float $precioBase,
        public readonly bool $destacado,
        public readonly bool $popular,
        public readonly bool $nuevo,
        public readonly bool $disponible,
        public readonly array $tamanos = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $tamanos = [];
        foreach ($data['tamanos'] ?? [] as $tamano) {
            $tamanos[] = [
                'nombre' => (string) $tamano['nombre'],
                'precio' => (float) $tamano['precio'],
                'descripcion' => isset($tamano['descripcion']) ? (string) $tamano['descripcion'] : null,
            ];
        }

        return new self(
            categoriaId: (int) $data['categoria_id'],
            nombre: (string) $data['nombre'],
            descripcion: isset($data['descripcion']) ? (string) $data['descripcion'] : null,
            precioBase: (float) $data['precio_base'],
            destacado: (bool) ($data['destacado'] ?? false),
            popular: (bool) ($data['popular'] ?? false),
            nuevo: (bool) ($data['nuevo'] ?? false),
            disponible: (bool) ($data['disponible'] ?? true),
            tamanos: $tamanos,
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
            'popular' => $this->popular,
            'nuevo' => $this->nuevo,
            'disponible' => $this->disponible,
        ];
    }
}
