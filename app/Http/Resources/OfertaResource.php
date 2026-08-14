<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Oferta;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Oferta */
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
            'imagen_url' => $this->imagen_url,
            'alcance' => $this->alcance,
            'productos' => $this->whenLoaded('productos', fn () => $this->productos->map(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
            ])),
            'productos_count' => $this->productos->count(),
            'clientes' => $this->whenLoaded('clientes', fn () => $this->clientes->map(fn ($c) => [
                'id' => $c->id,
                'nombre' => $c->nombre,
            ])),
            'alcance_sedes' => $this->alcance_sedes,
            'sucursales' => $this->whenLoaded('sucursales', fn () => $this->sucursales->map(fn ($s) => [
                'id' => $s->id,
                'nombre' => $s->nombre,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
