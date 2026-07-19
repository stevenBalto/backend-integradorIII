<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resumen de cliente con estadisticas de compra (modulo Clientes).
 *
 * @mixin \App\Models\User
 */
final class ClienteResumenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalGastado = (float) ($this->total_gastado ?? 0);
        $cantidadPedidos = (int) ($this->cantidad_pedidos ?? 0);
        $ticketPromedio = $cantidadPedidos > 0 ? $totalGastado / $cantidadPedidos : 0.0;

        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'puntos_balance' => (int) $this->puntos_balance,
            'activo' => (bool) $this->activo,
            'total_gastado' => round($totalGastado, 2),
            'cantidad_pedidos' => $cantidadPedidos,
            'ticket_promedio' => round($ticketPromedio, 2),
            'ultimo_pedido_en' => $this->ultimo_pedido_en !== null
                ? (new \DateTimeImmutable($this->ultimo_pedido_en))->format('c')
                : null,
        ];
    }
}
