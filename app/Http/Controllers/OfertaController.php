<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Oferta\ActualizarOfertaDTO;
use App\DTOs\Oferta\CrearOfertaDTO;
use App\Http\Requests\Oferta\StoreOfertaRequest;
use App\Http\Requests\Oferta\UpdateOfertaRequest;
use App\Http\Resources\OfertaResource;
use App\Services\OfertaService;
use Illuminate\Http\JsonResponse;

/**
 * Endpoints de administracion de ofertas.
 */
final class OfertaController extends Controller
{
    public function __construct(
        private readonly OfertaService $ofertas,
    ) {
    }

    /** GET /api/admin/ofertas — listado completo para administracion. */
    public function indexAdmin(): JsonResponse
    {
        return OfertaResource::collection($this->ofertas->listarTodos())
            ->response();
    }

    /** GET /api/admin/ofertas/{id} */
    public function show(int $id): OfertaResource
    {
        return new OfertaResource($this->ofertas->buscarPorId($id));
    }

    /** POST /api/admin/ofertas */
    public function store(StoreOfertaRequest $request): JsonResponse
    {
        $oferta = $this->ofertas->crear(CrearOfertaDTO::fromArray($request->validated()));

        return (new OfertaResource($oferta))->response()->setStatusCode(201);
    }

    /** PUT/PATCH /api/admin/ofertas/{id} */
    public function update(UpdateOfertaRequest $request, int $id): OfertaResource
    {
        $oferta = $this->ofertas->actualizar($id, ActualizarOfertaDTO::fromArray($request->validated()));

        return new OfertaResource($oferta);
    }

    /** DELETE /api/admin/ofertas/{id} */
    public function destroy(int $id): JsonResponse
    {
        $this->ofertas->eliminar($id);

        return response()->json(['message' => 'Oferta eliminada correctamente.']);
    }
}
