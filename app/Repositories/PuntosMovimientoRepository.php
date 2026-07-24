<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PuntosMovimiento;
use Illuminate\Database\Eloquent\Collection;

/**
 * Unica capa que consulta la tabla puntos_movimientos via Eloquent.
 */
final class PuntosMovimientoRepository
{
    public function crear(array $datos): PuntosMovimiento
    {
        return PuntosMovimiento::create($datos);
    }

    /**
     * Movimientos de Roosters de un usuario (mas recientes primero), con el codigo
     * del pedido asociado. El global scope multi-tenant ya limita a la instancia.
     *
     * @return Collection<int, PuntosMovimiento>
     */
    public function listarDeUsuario(int $userId): Collection
    {
        return PuntosMovimiento::query()
            ->where('user_id', $userId)
            ->with('pedido:id,codigo')
            ->orderByDesc('creado_en')
            ->orderByDesc('id')
            ->get();
    }
}
