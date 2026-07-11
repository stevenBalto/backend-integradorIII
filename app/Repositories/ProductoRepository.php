<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Collection;

/**
 * Unica capa que consulta la tabla productos via Eloquent.
 */
final class ProductoRepository
{
    /** @return Collection<int, Producto> */
    public function listarTodos(): Collection
    {
        return Producto::query()
            ->with('categoria')
            ->orderBy('nombre')
            ->get();
    }

    /** @return Collection<int, Producto> */
    public function listarDisponibles(): Collection
    {
        return Producto::query()
            ->with('categoria')
            ->where('disponible', true)
            ->orderBy('nombre')
            ->get();
    }

    public function buscarPorId(int $id): ?Producto
    {
        return Producto::query()->with('categoria')->find($id);
    }

    public function crear(array $datos): Producto
    {
        $producto = Producto::create($datos);
        $producto->load('categoria');

        return $producto;
    }

    public function actualizar(Producto $producto, array $datos): Producto
    {
        $producto->update($datos);
        $producto->load('categoria');

        return $producto;
    }

    public function eliminar(Producto $producto): void
    {
        $producto->delete();
    }
}
