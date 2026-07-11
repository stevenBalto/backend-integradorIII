<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Producto\ActualizarProductoDTO;
use App\DTOs\Producto\CrearProductoDTO;
use App\Http\Requests\Producto\StoreProductoRequest;
use App\Http\Requests\Producto\UpdateProductoRequest;
use App\Http\Resources\ProductoResource;
use App\Services\CloudinaryService;
use App\Services\ProductoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints del catalogo de productos (publico y administracion).
 */
final class ProductoController extends Controller
{
    public function __construct(
        private readonly ProductoService $productos,
        private readonly CloudinaryService $cloudinary,
    ) {
    }

    /** GET /api/productos — catalogo publico, solo disponibles. */
    public function index(): JsonResponse
    {
        return ProductoResource::collection($this->productos->listarDisponibles())
            ->response();
    }

    /** GET /api/admin/productos — listado completo para administracion. */
    public function indexAdmin(): JsonResponse
    {
        return ProductoResource::collection($this->productos->listarTodos())
            ->response();
    }

    /** GET /api/admin/productos/{id} */
    public function show(int $id): ProductoResource
    {
        return new ProductoResource($this->productos->buscarPorId($id));
    }

    /** POST /api/admin/productos */
    public function store(StoreProductoRequest $request): JsonResponse
    {
        $imagenUrl = $request->hasFile('imagen')
            ? $this->cloudinary->subirImagenProducto($request->file('imagen'))
            : null;

        $producto = $this->productos->crear(CrearProductoDTO::fromArray($request->validated()), $imagenUrl);

        return (new ProductoResource($producto))->response()->setStatusCode(201);
    }

    /** PUT/PATCH /api/admin/productos/{id} (via POST + _method para poder mandar el archivo) */
    public function update(UpdateProductoRequest $request, int $id): ProductoResource
    {
        $imagenUrl = $request->hasFile('imagen')
            ? $this->cloudinary->subirImagenProducto($request->file('imagen'))
            : null;

        $producto = $this->productos->actualizar($id, ActualizarProductoDTO::fromArray($request->validated()), $imagenUrl);

        return new ProductoResource($producto);
    }

    /** DELETE /api/admin/productos/{id} */
    public function destroy(int $id): JsonResponse
    {
        $this->productos->eliminar($id);

        return response()->json(['message' => 'Producto eliminado correctamente.']);
    }
}
