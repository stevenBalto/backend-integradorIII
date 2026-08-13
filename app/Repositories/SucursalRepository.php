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

    /**
     * Crea la sede de una instancia indicada a mano. Se usa desde el panel de
     * superadmin, donde el actor NO tiene instancia (el trait multi-tenant no
     * puede deducirla) y `instancia_id` esta fuera de $fillable a proposito.
     *
     * @param  array<string, mixed>  $datos
     */
    public function crearParaInstancia(int $instanciaId, array $datos): Sucursal
    {
        $sucursal = new Sucursal($datos);
        $sucursal->instancia_id = $instanciaId;
        $sucursal->save();

        return $sucursal;
    }

    public function actualizar(Sucursal $sucursal, array $datos): Sucursal
    {
        $sucursal->update($datos);

        return $sucursal;
    }

    /**
     * Abre o cierra la sede. `activa` esta fuera de $fillable a proposito (no se
     * edita junto con nombre/direccion), asi que se asigna directo.
     */
    public function cambiarEstado(Sucursal $sucursal, bool $abierta): Sucursal
    {
        $sucursal->activa = $abierta;
        $sucursal->save();

        return $sucursal;
    }
}
