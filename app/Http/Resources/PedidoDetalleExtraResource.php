<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\DetallePedidoExtra */
final class PedidoDetalleExtraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'nombre' => $this->whenLoaded('extra', fn () => $this->extra?->nombre),
            'precio' => (float) $this->precio,
        ];
    }
}
