<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PedidoHistorialEstado;

/**
 * Unica capa que consulta la tabla pedido_historial_estado via Eloquent.
 */
final class PedidoHistorialRepository
{
    public function crear(array $datos): PedidoHistorialEstado
    {
        return PedidoHistorialEstado::create($datos);
    }
}
