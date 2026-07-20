<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ProductoTamano */
final class ProductoTamanoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'precio' => (float) $this->precio,
            'descripcion' => $this->descripcion,
            'orden' => (int) $this->orden,
        ];
    }
}
