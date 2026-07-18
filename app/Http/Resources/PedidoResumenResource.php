<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resumen de pedido para el historial de un cliente (modulo Clientes).
 *
 * @mixin \App\Models\Pedido
 */
final class PedidoResumenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'total' => (float) $this->total,
            'estado' => $this->estado,
            'modalidad' => $this->modalidad,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
