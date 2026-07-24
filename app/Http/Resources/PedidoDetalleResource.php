<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\DetallePedido */
final class PedidoDetalleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'producto_nombre' => $this->whenLoaded('producto', fn () => $this->producto?->nombre),
            'cantidad' => (int) $this->cantidad,
            'tamano_nombre' => $this->tamano_nombre,
            'precio_unitario' => (float) $this->precio_unitario,
            'subtotal' => (float) $this->subtotal,
            'notas' => $this->notas,
            'extras' => PedidoDetalleExtraResource::collection($this->whenLoaded('extras')),
        ];
    }
}
