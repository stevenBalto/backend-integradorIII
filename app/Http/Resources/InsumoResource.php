<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Insumo */
final class InsumoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'unidad_medida' => $this->unidad_medida,
            'cantidad_actual' => (float) $this->cantidad_actual,
            'stock_minimo' => $this->stock_minimo !== null ? (float) $this->stock_minimo : null,
            'bajo_stock' => $this->bajoStock(),
            'tiene_movimientos' => ($this->movimientos_count ?? 0) > 0,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
