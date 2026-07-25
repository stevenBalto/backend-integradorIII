<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Pedido;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Consultas de agregacion para el dashboard admin. Solo lectura.
 */
final class DashboardRepository
{
    /** @return Collection<int, Pedido> Pedidos creados entre las 2 fechas (inclusive), sin cancelados. */
    public function pedidosEntre(Carbon $desde, Carbon $hasta): Collection
    {
        return Pedido::query()
            ->whereBetween('created_at', [$desde, $hasta])
            ->get();
    }

    /** Cuenta pedidos activos (no entregados ni cancelados). */
    public function contarActivos(): int
    {
        return Pedido::query()
            ->whereIn('estado', ['pendiente', 'en_proceso', 'listo'])
            ->count();
    }

    /** @return Collection<int, Pedido> Ultimos N pedidos con estado pendiente (los mas nuevos). */
    public function ultimosPendientes(int $limite): Collection
    {
        return Pedido::query()
            ->where('estado', 'pendiente')
            ->orderByDesc('created_at')
            ->limit($limite)
            ->get();
    }

    /** @return Collection<int, Pedido> Ultimos N pedidos, sin importar estado. */
    public function ultimos(int $limite): Collection
    {
        return Pedido::query()
            ->with(['cliente'])
            ->withSum('detalles as detalles_sum_cantidad', 'cantidad')
            ->orderByDesc('created_at')
            ->limit($limite)
            ->get();
    }
}
