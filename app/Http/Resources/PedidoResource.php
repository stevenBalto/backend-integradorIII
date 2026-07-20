<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para pedidos (vista cliente).
 *
 * @mixin \App\Models\Pedido
 */
final class PedidoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'modalidad' => $this->modalidad,
            'estado' => $this->estado,
            'subtotal' => (float) $this->subtotal,
            'descuento' => (float) $this->descuento,
            'total' => (float) $this->total,
            'puntos_ganados' => (int) $this->puntos_ganados,
            'notas' => $this->notas,
            'nombre_cliente' => $this->nombre_cliente,
            'sucursal' => $this->whenLoaded('sucursal', fn () => [
                'id' => $this->sucursal->id,
                'nombre' => $this->sucursal->nombre,
            ]),
            'items' => PedidoDetalleResource::collection($this->whenLoaded('detalles')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
