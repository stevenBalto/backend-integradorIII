<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Extra;
use App\Models\Producto;
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
     * @param array<int, array{nombre: string, precio: float}> $tamanos
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
                'orden' => $orden,
                'activo' => true,
            ]);
        }
    }

    /**
     * Carga los extras disponibles de la categoria de cada producto.
     * Se cargan como una propiedad no persistida para que el Resource los pueda renderizar.
     *
     * @param Collection<int, Producto> $productos
     * @return Collection<int, Producto>
     */
    private function cargarExtrasDeCategoria(Collection $productos): Collection
    {
        // Obtener los IDs de categorias unicas
        $categoriaIds = $productos->pluck('categoria_id')->unique()->filter()->values();

        if ($categoriaIds->isEmpty()) {
            return $productos;
        }

        // Cargar todos los extras disponibles de esas categorias de una sola vez (evita N+1)
        $extrasPorCategoria = Extra::query()
            ->whereIn('categoria_id', $categoriaIds)
            ->where('disponible', true)
            ->orderBy('nombre')
            ->get()
            ->groupBy('categoria_id');

        // Asignar los extras a cada producto
        foreach ($productos as $producto) {
            $producto->setRelation(
                'extrasCategoria',
                $extrasPorCategoria->get($producto->categoria_id, collect())
            );
        }

        return $productos;
    }

    /** Carga los extras disponibles de la categoria de un producto. */
    private function cargarExtrasDeProducto(Producto $producto): void
    {
        $extras = Extra::query()
            ->where('categoria_id', $producto->categoria_id)
            ->where('disponible', true)
            ->orderBy('nombre')
            ->get();

        $producto->setRelation('extrasCategoria', $extras);
    }
}
