<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PuntosMovimiento;

/**
 * Unica capa que consulta la tabla puntos_movimientos via Eloquent.
 */
final class PuntosMovimientoRepository
{
    public function crear(array $datos): PuntosMovimiento
    {
        return PuntosMovimiento::create($datos);
    }
}
