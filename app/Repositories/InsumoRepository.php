<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Insumo;
use Illuminate\Database\Eloquent\Collection;

/**
 * Unica capa que consulta la tabla insumos via Eloquent.
 */
final class InsumoRepository
{
    /** @return Collection<int, Insumo> */
    public function listarTodos(): Collection
    {
        return Insumo::query()
            ->withCount('movimientos')
            ->orderBy('nombre')
            ->get();
    }

    public function buscarPorId(int $id): ?Insumo
    {
        return Insumo::query()->find($id);
    }

    public function crear(array $datos): Insumo
    {
        return Insumo::create($datos);
    }

    /** Solo actualiza nombre / unidad_medida / stock_minimo (nunca cantidad_actual). */
    public function actualizar(Insumo $insumo, array $datos): Insumo
    {
        $insumo->update($datos);

        return $insumo;
    }

    /** Ajusta la cantidad en stock. Solo lo invoca el service de toma fisica. */
    public function actualizarCantidad(Insumo $insumo, float $nuevaCantidad): Insumo
    {
        $insumo->update(['cantidad_actual' => $nuevaCantidad]);

        return $insumo;
    }

    public function eliminar(Insumo $insumo): void
    {
        $insumo->delete();
    }
}
