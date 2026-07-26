<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para analiticas del panel admin.
 * Recibe el array de datos ya calculado por el service.
 */
final class AnaliticasResource extends JsonResource
{
    /**
     * Disable wrapping for this resource (data comes as direct array).
     */
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        // El resource recibe un array, no un modelo
        $data = $this->resource;

        return [
            'ventas_mes' => $data['ventas_mes'],
            'pedidos_mes' => $data['pedidos_mes'],
            'ticket_promedio' => $data['ticket_promedio'],
            'ventas_por_dia' => $data['ventas_por_dia'],
            'horas_pico' => $data['horas_pico'],
            'top_productos' => $data['top_productos'],
            'modalidad' => $data['modalidad'],
            'comparacion_mes_anterior' => $data['comparacion_mes_anterior'],
            'ventas_por_categoria' => $data['ventas_por_categoria'],
        ];
    }
}
