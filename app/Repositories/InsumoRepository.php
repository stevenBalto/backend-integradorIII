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
            ->with('sucursal')
            ->orderBy('nombre')
            ->get();
    }

    public function buscarPorId(int $id): ?Insumo
    {
        return Insumo::query()->find($id);
    }

    /** sucursal_id NO esta en $fillable a proposito (anti sede-hopping, mismo
     *  criterio que instancia_id) — se fija a mano, ya resuelto por el service. */
    public function crear(array $datos, int $sucursalId): Insumo
    {
        $insumo = new Insumo($datos);
        $insumo->sucursal_id = $sucursalId;
        $insumo->save();

        return $insumo;
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
