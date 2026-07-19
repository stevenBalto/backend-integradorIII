<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Collection;

/**
 * Unica capa que consulta la tabla sucursales via Eloquent.
 */
final class SucursalRepository
{
    /** @return Collection<int, Sucursal> */
    public function listarActivas(): Collection
    {
        return Sucursal::query()
            ->where('activa', true)
            ->orderBy('nombre')
            ->get();
    }

    /** @return Collection<int, Sucursal> */
    public function listarTodas(): Collection
    {
        return Sucursal::query()
            ->orderBy('nombre')
            ->get();
    }

    public function buscarPorId(int $id): ?Sucursal
    {
        return Sucursal::query()->find($id);
    }

    public function existeYActiva(int $id): bool
    {
        return Sucursal::query()
            ->whereKey($id)
            ->where('activa', true)
            ->exists();
    }

    public function crear(array $datos): Sucursal
    {
        return Sucursal::create($datos);
    }

    public function actualizar(Sucursal $sucursal, array $datos): Sucursal
    {
        $sucursal->update($datos);

        return $sucursal;
    }
}
