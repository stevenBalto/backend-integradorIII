<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Cupon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Unica capa que consulta la tabla cupones via Eloquent.
 */
final class CuponRepository
{
    /** @return Collection<int, Cupon> */
    public function listarTodos(): Collection
    {
        return Cupon::query()
            ->orderBy('codigo')
            ->get();
    }

    /** @return Collection<int, Cupon> */
    public function listarActivos(): Collection
    {
        $hoy = now()->toDateString();

        return Cupon::query()
            ->where('activo', true)
            ->where(function ($query) use ($hoy): void {
                $query->whereNull('fecha_inicio')
                    ->orWhereDate('fecha_inicio', '<=', $hoy);
            })
            ->where(function ($query) use ($hoy): void {
                $query->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', $hoy);
            })
            ->where(function ($query): void {
                $query->whereNull('usos_max')
                    ->orWhereColumn('usos_actuales', '<', 'usos_max');
            })
            ->orderBy('codigo')
            ->get();
    }

    public function buscarPorId(int $id): ?Cupon
    {
        return Cupon::query()->find($id);
    }

    public function buscarPorCodigo(string $codigo): ?Cupon
    {
        return Cupon::query()->where('codigo', strtoupper($codigo))->first();
    }

    /** Busca un cupon por codigo SOLO si esta activo, vigente y con usos disponibles. */
    public function buscarActivoPorCodigo(string $codigo): ?Cupon
    {
        $hoy = now()->toDateString();

        return Cupon::query()
            ->where('codigo', strtoupper($codigo))
            ->where('activo', true)
            ->where(function ($query) use ($hoy): void {
                $query->whereNull('fecha_inicio')
                    ->orWhereDate('fecha_inicio', '<=', $hoy);
            })
            ->where(function ($query) use ($hoy): void {
                $query->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', $hoy);
            })
            ->where(function ($query): void {
                $query->whereNull('usos_max')
                    ->orWhereColumn('usos_actuales', '<', 'usos_max');
            })
            ->first();
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function crear(array $datos): Cupon
    {
        return Cupon::create($datos);
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function actualizar(Cupon $cupon, array $datos): Cupon
    {
        $cupon->update($datos);

        return $cupon;
    }

    /** Borrado fisico (DELETE real, no soft delete). */
    public function eliminar(Cupon $cupon): void
    {
        $cupon->delete();
    }

    /** Incrementa el contador de usos (canje via QR/pedido de mostrador). */
    public function incrementarUso(Cupon $cupon): void
    {
        $cupon->increment('usos_actuales');
    }
}
