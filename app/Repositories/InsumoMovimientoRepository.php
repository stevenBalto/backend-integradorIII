<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\InsumoMovimiento;
use Illuminate\Database\Eloquent\Collection;

/**
 * Unica capa que consulta la tabla insumo_movimientos via Eloquent.
 * Solo inserta y lista: el historial es inmutable (no se edita ni borra).
 */
final class InsumoMovimientoRepository
{
    public function crear(array $datos): InsumoMovimiento
    {
        $movimiento = InsumoMovimiento::create($datos);
        $movimiento->load('usuario');

        return $movimiento;
    }

    /** @return Collection<int, InsumoMovimiento> */
    public function listarPorInsumo(int $insumoId): Collection
    {
        return InsumoMovimiento::query()
            ->with('usuario')
            ->where('insumo_id', $insumoId)
            ->orderByDesc('created_at')
            ->get();
    }
}
