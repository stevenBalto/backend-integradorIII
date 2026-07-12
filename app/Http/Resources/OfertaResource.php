<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Oferta */
final class OfertaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'tipo_descuento' => $this->tipo_descuento,
            'valor' => (float) $this->valor,
            'fecha_inicio' => $this->fecha_inicio?->toDateString(),
            'fecha_fin' => $this->fecha_fin?->toDateString(),
            'activa' => (bool) $this->activa,
            'productos' => $this->whenLoaded('productos', fn () => $this->productos->map(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
            ])),
            'productos_count' => $this->productos->count(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
