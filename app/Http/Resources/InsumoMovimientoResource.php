<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\InsumoMovimiento */
final class InsumoMovimientoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'cantidad_anterior' => (float) $this->cantidad_anterior,
            'cantidad_nueva' => (float) $this->cantidad_nueva,
            'diferencia' => (float) $this->diferencia,
            'nota' => $this->nota,
            'usuario' => $this->whenLoaded('usuario', fn () => $this->usuario === null ? null : [
                'id' => $this->usuario->id,
                'nombre' => $this->usuario->nombre,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
