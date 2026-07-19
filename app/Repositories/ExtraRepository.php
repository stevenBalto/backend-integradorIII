<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\DetallePedidoExtra;
use App\Models\Extra;
use App\Models\ProductoExtra;
use Illuminate\Database\Eloquent\Collection;

/**
 * Unica capa que consulta la tabla extras via Eloquent.
 */
final class ExtraRepository
{
    /** @return Collection<int, Extra> */
    public function listarTodos(): Collection
    {
        return Extra::query()
            ->with('categoria')
            ->orderBy('nombre')
            ->get();
    }

    /** @return Collection<int, Extra> Extras disponibles de una categoria. */
    public function listarPorCategoria(int $categoriaId): Collection
    {
        return Extra::query()
            ->where('categoria_id', $categoriaId)
            ->where('disponible', true)
            ->orderBy('nombre')
            ->get();
    }

    public function buscarPorId(int $id): ?Extra
    {
        return Extra::query()->with('categoria')->find($id);
    }

    /** Igual que buscarPorId pero carga los productos asignados puntualmente (para el show). */
    public function buscarConAsignados(int $id): ?Extra
    {
        return Extra::query()->with(['categoria', 'productosAsignados'])->find($id);
    }

    /** Asigna puntualmente una extra a un producto. Idempotente. */
    public function asignarAProducto(int $extraId, int $productoId): void
    {
        ProductoExtra::firstOrCreate([
            'extra_id' => $extraId,
            'producto_id' => $productoId,
        ]);
    }

    /** Quita la asignacion puntual. Borrar una fila inexistente es un no-op. */
    public function desasignarDeProducto(int $extraId, int $productoId): void
    {
        ProductoExtra::query()
            ->where('extra_id', $extraId)
            ->where('producto_id', $productoId)
            ->delete();
    }

    public function crear(array $datos): Extra
    {
        $extra = Extra::create($datos);
        $extra->load('categoria');

        return $extra;
    }

    public function actualizar(Extra $extra, array $datos): Extra
    {
        $extra->update($datos);
        $extra->load('categoria');

        return $extra;
    }

    /** Verifica si el extra esta referenciado en algun detalle_pedido_extras. */
    public function estaReferenciado(int $id): bool
    {
        return DetallePedidoExtra::query()->where('extra_id', $id)->exists();
    }

    public function eliminar(Extra $extra): void
    {
        $extra->delete();
    }
}
