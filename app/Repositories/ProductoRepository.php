<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Extra;
use App\Models\Producto;
use App\Models\ProductoExtra;
use App\Models\ProductoTamano;
use Illuminate\Database\Eloquent\Collection;

/**
 * Unica capa que consulta la tabla productos via Eloquent.
 */
final class ProductoRepository
{
    /** @return Collection<int, Producto> */
    public function listarTodos(): Collection
    {
        $productos = Producto::query()
            ->with(['categoria', 'todosLosTamanos'])
            ->orderBy('nombre')
            ->get();

        return $this->cargarExtrasDeCategoria($productos);
    }

    /** @return Collection<int, Producto> */
    public function listarDisponibles(): Collection
    {
        $productos = Producto::query()
            ->with(['categoria', 'tamanos'])
            ->where('disponible', true)
            ->orderBy('nombre')
            ->get();

        return $this->cargarExtrasDeCategoria($productos);
    }

    public function buscarPorId(int $id): ?Producto
    {
        $producto = Producto::query()
            ->with(['categoria', 'todosLosTamanos'])
            ->find($id);

        if ($producto !== null) {
            $this->cargarExtrasDeProducto($producto);
        }

        return $producto;
    }

    public function crear(array $datos): Producto
    {
        $producto = Producto::create($datos);
        $producto->load(['categoria', 'tamanos']);
        $this->cargarExtrasDeProducto($producto);

        return $producto;
    }

    public function actualizar(Producto $producto, array $datos): Producto
    {
        $producto->update($datos);
        $producto->load(['categoria', 'todosLosTamanos']);
        $this->cargarExtrasDeProducto($producto);

        return $producto;
    }

    public function eliminar(Producto $producto): void
    {
        $producto->delete();
    }

    /**
     * Sincroniza los tamanos de un producto.
     * Estrategia: soft-delete todos los existentes y crear los nuevos.
     * Es seguro porque producto_tamano_id en detalle_pedido es ON DELETE SET NULL.
     *
     * @param array<int, array{nombre: string, precio: float, descripcion?: ?string}> $tamanos
     */
    public function sincronizarTamanos(Producto $producto, array $tamanos): void
    {
        // Soft-delete todos los tamanos existentes
        ProductoTamano::where('producto_id', $producto->id)->delete();

        // Crear los nuevos tamanos
        foreach ($tamanos as $orden => $tamano) {
            ProductoTamano::create([
                'producto_id' => $producto->id,
                'nombre' => $tamano['nombre'],
                'precio' => $tamano['precio'],
                'descripcion' => $tamano['descripcion'] ?? null,
                'orden' => $orden,
                'activo' => true,
            ]);
        }
    }

    /**
     * Carga, para cada producto de la coleccion, sus extras disponibles resolviendo las
     * 3 condiciones: es_general OR categoria_id = producto.categoria_id OR asignacion
     * puntual en producto_extras. Sin duplicados (una misma extra en varias condiciones
     * aparece una sola vez). Se carga como relacion no persistida 'extrasCategoria' para
     * que el Resource la renderice.
     *
     * Sin N+1: 1 query de generales, 1 query agrupada de categorias, 1 query del pivote
     * y 1 query para hidratar las extras del pivote; el merge/dedupe se hace en PHP.
     *
     * @param Collection<int, Producto> $productos
     * @return Collection<int, Producto>
     */
    private function cargarExtrasDeCategoria(Collection $productos): Collection
    {
        if ($productos->isEmpty()) {
            return $productos;
        }

        // 1. Extras generales (aplican a todos los productos).
        $generales = Extra::query()
            ->where('es_general', true)
            ->where('disponible', true)
            ->orderBy('nombre')
            ->get();

        // 2. Extras por categoria, agrupadas.
        $categoriaIds = $productos->pluck('categoria_id')->unique()->filter()->values();
        $extrasPorCategoria = $categoriaIds->isEmpty()
            ? collect()
            : Extra::query()
                ->whereIn('categoria_id', $categoriaIds)
                ->where('disponible', true)
                ->orderBy('nombre')
                ->get()
                ->groupBy('categoria_id');

        // 3. Asignaciones puntuales (pivote) de todos los productos involucrados.
        $productoIds = $productos->pluck('id')->values();
        $asignadosPorProducto = $this->extrasAsignadasPorProducto($productoIds);

        // 4. Mergear + deduplicar por id para cada producto.
        foreach ($productos as $producto) {
            $extras = $generales
                ->concat($extrasPorCategoria->get($producto->categoria_id, collect()))
                ->concat($asignadosPorProducto->get($producto->id, collect()))
                ->unique('id')
                ->sortBy('nombre')
                ->values();

            $producto->setRelation('extrasCategoria', $extras);
        }

        return $productos;
    }

    /** Carga las extras disponibles de un producto (general OR categoria OR pivote). */
    private function cargarExtrasDeProducto(Producto $producto): void
    {
        $generales = Extra::query()
            ->where('es_general', true)
            ->where('disponible', true)
            ->get();

        $deCategoria = Extra::query()
            ->where('categoria_id', $producto->categoria_id)
            ->where('disponible', true)
            ->get();

        $asignados = $this->extrasAsignadasPorProducto(collect([$producto->id]))
            ->get($producto->id, collect());

        $extras = $generales
            ->concat($deCategoria)
            ->concat($asignados)
            ->unique('id')
            ->sortBy('nombre')
            ->values();

        $producto->setRelation('extrasCategoria', $extras);
    }

    /**
     * Devuelve las extras disponibles asignadas puntualmente, agrupadas por producto_id.
     * 2 queries fijas (filas del pivote + hidratar extras), sin N+1.
     *
     * @param \Illuminate\Support\Collection<int, int> $productoIds
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, Extra>>
     */
    private function extrasAsignadasPorProducto(\Illuminate\Support\Collection $productoIds): \Illuminate\Support\Collection
    {
        if ($productoIds->isEmpty()) {
            return collect();
        }

        $filas = ProductoExtra::query()
            ->whereIn('producto_id', $productoIds)
            ->get(['producto_id', 'extra_id']);

        if ($filas->isEmpty()) {
            return collect();
        }

        $extras = Extra::query()
            ->whereIn('id', $filas->pluck('extra_id')->unique()->values())
            ->where('disponible', true)
            ->get()
            ->keyBy('id');

        return $filas
            ->groupBy('producto_id')
            ->map(fn ($grupo) => $grupo
                ->map(fn ($fila) => $extras->get($fila->extra_id))
                ->filter()
                ->values());
    }
}
