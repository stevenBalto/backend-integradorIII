<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un movimiento de Roosters (puntos) del cliente. `puntos` positivo = ganado,
 * negativo = canjeado.
 *
 * @mixin \App\Models\PuntosMovimiento
 */
final class PuntosResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'puntos' => (int) $this->puntos,
            'descripcion' => $this->descripcion,
            'pedido_codigo' => $this->whenLoaded('pedido', fn () => $this->pedido?->codigo),
            'creado_en' => $this->creado_en?->toIso8601String(),
        ];
    }
}
