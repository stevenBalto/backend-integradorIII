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

    public function buscarPorId(int $id): ?Cupon
    {
        return Cupon::query()->find($id);
    }

    public function buscarPorCodigo(string $codigo): ?Cupon
    {
        return Cupon::query()->where('codigo', strtoupper($codigo))->first();
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
}
