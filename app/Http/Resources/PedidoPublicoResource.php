<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para pedidos (vista publica sin autenticacion).
 * Solo muestra datos minimos, sin PII ni precios detallados.
 *
 * @mixin \App\Models\Pedido
 */
final class PedidoPublicoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'codigo' => $this->codigo,
            'estado' => $this->estado,
            'modalidad' => $this->modalidad,
            'sucursal' => $this->whenLoaded('sucursal', fn () => $this->sucursal->nombre),
            'creado_en' => $this->created_at?->toIso8601String(),
        ];
    }
}
