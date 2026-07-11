<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Producto */
final class ProductoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'categoria_id' => $this->categoria_id,
            'categoria' => $this->whenLoaded('categoria', fn () => $this->categoria->nombre),
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio_base' => (float) $this->precio_base,
            'imagen_url' => $this->imagen_url,
            'destacado' => (bool) $this->destacado,
            'disponible' => (bool) $this->disponible,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
