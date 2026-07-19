<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Extra\ActualizarExtraDTO;
use App\DTOs\Extra\CrearExtraDTO;
use App\Http\Requests\Extra\StoreExtraRequest;
use App\Http\Requests\Extra\UpdateExtraRequest;
use App\Http\Resources\ExtraResource;
use App\Services\ExtraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints de extras / acompañamientos (100% administracion, sin acceso publico).
 */
final class ExtraController extends Controller
{
    public function __construct(
        private readonly ExtraService $extras,
    ) {
    }

    /** GET /api/admin/extras */
    public function index(): JsonResponse
    {
        return ExtraResource::collection($this->extras->listarTodos())
            ->response();
    }

    /** GET /api/admin/extras/{id} */
    public function show(int $id): ExtraResource
    {
        return new ExtraResource($this->extras->buscarConAsignados($id));
    }

    /** POST /api/admin/extras */
    public function store(StoreExtraRequest $request): JsonResponse
    {
        $extra = $this->extras->crear(CrearExtraDTO::fromArray($request->validated()));

        return (new ExtraResource($extra))->response()->setStatusCode(201);
    }

    /** PUT/PATCH /api/admin/extras/{id} */
    public function update(UpdateExtraRequest $request, int $id): ExtraResource
    {
        $extra = $this->extras->actualizar($id, ActualizarExtraDTO::fromArray($request->validated()));

        return new ExtraResource($extra);
    }

    /** DELETE /api/admin/extras/{id} */
    public function destroy(int $id): JsonResponse
    {
        $this->extras->eliminar($id);

        return response()->json(['message' => 'Extra eliminado correctamente.']);
    }

    /** POST /api/admin/extras/{id}/productos — asigna la extra a un producto puntual. */
    public function asignarProducto(Request $request, int $id): ExtraResource
    {
        $datos = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
        ]);

        $this->extras->asignarAProducto($id, (int) $datos['producto_id']);

        return new ExtraResource($this->extras->buscarConAsignados($id));
    }

    /** DELETE /api/admin/extras/{id}/productos/{productoId} — quita la asignacion puntual. */
    public function desasignarProducto(int $id, int $productoId): ExtraResource
    {
        $this->extras->desasignarDeProducto($id, $productoId);

        return new ExtraResource($this->extras->buscarConAsignados($id));
    }
}
