<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Cupon\ActualizarCuponDTO;
use App\DTOs\Cupon\CrearCuponDTO;
use App\Http\Requests\Cupon\StoreCuponRequest;
use App\Http\Requests\Cupon\UpdateCuponRequest;
use App\Http\Resources\CuponResource;
use App\Services\CuponService;
use Illuminate\Http\JsonResponse;

/**
 * Endpoints de administracion de cupones.
 */
final class CuponController extends Controller
{
    public function __construct(
        private readonly CuponService $cupones,
    ) {
    }

    /** GET /api/admin/cupones — listado completo para administracion. */
    public function indexAdmin(): JsonResponse
    {
        return CuponResource::collection($this->cupones->listarTodos())
            ->response();
    }

    /** GET /api/admin/cupones/{id} */
    public function show(int $id): CuponResource
    {
        return new CuponResource($this->cupones->buscarPorId($id));
    }

    /** POST /api/admin/cupones */
    public function store(StoreCuponRequest $request): JsonResponse
    {
        $cupon = $this->cupones->crear(CrearCuponDTO::fromArray($request->validated()));

        return (new CuponResource($cupon))->response()->setStatusCode(201);
    }

    /** PUT/PATCH /api/admin/cupones/{id} */
    public function update(UpdateCuponRequest $request, int $id): CuponResource
    {
        $cupon = $this->cupones->actualizar($id, ActualizarCuponDTO::fromArray($request->validated()));

        return new CuponResource($cupon);
    }

    /** DELETE /api/admin/cupones/{id} */
    public function destroy(int $id): JsonResponse
    {
        $this->cupones->eliminar($id);

        return response()->json(['message' => 'Cupon eliminado correctamente.']);
    }
}
