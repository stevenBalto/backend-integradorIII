<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Producto\ActualizarProductoDTO;
use App\DTOs\Producto\CrearProductoDTO;
use App\Models\Producto;
use App\Repositories\CategoriaRepository;
use App\Repositories\ProductoRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Logica de negocio del catalogo de productos.
 */
final class ProductoService
{
    public function __construct(
        private readonly ProductoRepository $productos,
        private readonly CategoriaRepository $categorias,
        private readonly NotificacionService $notificaciones,
    ) {
    }

    /** @return Collection<int, Producto> */
    public function listarTodos(): Collection
    {
        return $this->productos->listarTodos();
    }

    /**
     * Devuelve productos disponibles. Si se pasan parámetros de paginación,
     * devuelve un LengthAwarePaginator en lugar de la Collection completa.
     *
     * @return Collection<int, Producto>|\Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listarDisponibles(?int $porPagina = null, int $pagina = 1)
    {
        return $this->productos->listarDisponibles($porPagina, $pagina);
    }

    public function buscarPorId(int $id): Producto
    {
        $producto = $this->productos->buscarPorId($id);

        if ($producto === null) {
            throw ValidationException::withMessages([
                'id' => ['El producto no existe.'],
            ]);
        }

        return $producto;
    }

    /** $imagenUrl: si se subio una imagen nueva a Cloudinary, su secure_url. */
    public function crear(CrearProductoDTO $dto, ?string $imagenUrl = null): Producto
    {
        $this->validarCategoria($dto->categoriaId);

        $producto = DB::transaction(function () use ($dto, $imagenUrl): Producto {
            $datos = $dto->toArray();
            $datos['imagen_url'] = $imagenUrl;

            $producto = $this->productos->crear($datos);

            // Sincronizar tamanos si se enviaron
            if (! empty($dto->tamanos)) {
                $this->productos->sincronizarTamanos($producto, $dto->tamanos);
                $producto->load('tamanos');
            }

            return $producto;
        });

        // Avisar a los admins de la instancia que hay un producto nuevo.
        try {
            $this->notificaciones->notificarProductoNuevo($producto);
        } catch (\Throwable $e) {
            Log::warning('No se pudo crear la notificacion de producto nuevo', ['error' => $e->getMessage()]);
        }

        return $producto;
    }

    /** $imagenUrl: si es null, se conserva la imagen actual del producto (no se pisa). */
    public function actualizar(int $id, ActualizarProductoDTO $dto, ?string $imagenUrl = null): Producto
    {
        $this->validarCategoria($dto->categoriaId);

        $producto = $this->buscarPorId($id);

        return DB::transaction(function () use ($producto, $dto, $imagenUrl): Producto {
            $datos = $dto->toArray();
            if ($imagenUrl !== null) {
                $datos['imagen_url'] = $imagenUrl;
            }

            $producto = $this->productos->actualizar($producto, $datos);

            // Sincronizar tamanos (se envian siempre, aunque sea array vacio)
            $this->productos->sincronizarTamanos($producto, $dto->tamanos);
            $producto->load('todosLosTamanos');

            return $producto;
        });
    }

    public function eliminar(int $id): void
    {
        $producto = $this->buscarPorId($id);

        $this->productos->eliminar($producto);
    }

    private function validarCategoria(int $categoriaId): void
    {
        if (! $this->categorias->existe($categoriaId)) {
            throw ValidationException::withMessages([
                'categoria_id' => ['La categoría seleccionada no existe.'],
            ]);
        }
    }
}
