<?php

declare(strict_types=1);

namespace App\DTOs\Pedido;

final class CrearPedidoDTO
{
    /**
     * @param array<int, array{producto_id: int, cantidad: int, producto_tamano_id: ?int, extra_ids: int[], notas: ?string}> $items
     */
    public function __construct(
        public readonly int $sucursalId,
        public readonly string $modalidad,
        public readonly ?string $notas,
        public readonly array $items,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $items = [];
        foreach ($data['items'] ?? [] as $item) {
            $items[] = [
                'producto_id' => (int) $item['producto_id'],
                'cantidad' => (int) ($item['cantidad'] ?? 1),
                'producto_tamano_id' => isset($item['producto_tamano_id']) ? (int) $item['producto_tamano_id'] : null,
                'extra_ids' => array_map('intval', $item['extra_ids'] ?? []),
                'notas' => isset($item['notas']) ? (string) $item['notas'] : null,
            ];
        }

        return new self(
            sucursalId: (int) $data['sucursal_id'],
            modalidad: (string) $data['modalidad'],
            notas: isset($data['notas']) ? (string) $data['notas'] : null,
            items: $items,
        );
    }

    public function toArray(): array
    {
        return [
            'sucursal_id' => $this->sucursalId,
            'modalidad' => $this->modalidad,
            'notas' => $this->notas,
            'items' => $this->items,
        ];
    }
}
