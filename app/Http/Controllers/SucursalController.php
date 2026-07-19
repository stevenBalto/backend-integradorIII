<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Sucursal\ActualizarSucursalDTO;
use App\DTOs\Sucursal\CrearSucursalDTO;
use App\Http\Requests\Sucursal\StoreSucursalRequest;
use App\Http\Requests\Sucursal\UpdateSucursalRequest;
use App\Http\Resources\SucursalResource;
use App\Repositories\SucursalRepository;
use App\Services\SucursalService;
use Illuminate\Http\JsonResponse;

/**
 * Endpoints de sucursales: lectura publica de activas (cliente) y CRUD minimo (admin).
 */
final class SucursalController extends Controller
{
    public function __construct(
        private readonly SucursalRepository $sucursales,
        private readonly SucursalService $servicio,
    ) {
    }

    /** GET /api/sucursales — listado de sucursales activas (cliente autenticado). */
    public function index(): JsonResponse
    {
        return SucursalResource::collection($this->sucursales->listarActivas())
            ->response();
    }

    /** GET /api/admin/sucursales — listado admin, incluye inactivas. */
    public function indexAdmin(): JsonResponse
    {
        return SucursalResource::collection($this->servicio->listarPropias())
            ->response();
    }

    /** POST /api/admin/sucursales */
    public function store(StoreSucursalRequest $request): JsonResponse
    {
        $sucursal = $this->servicio->crear(CrearSucursalDTO::fromArray($request->validated()));

        return (new SucursalResource($sucursal))->response()->setStatusCode(201);
    }

    /** PUT/PATCH /api/admin/sucursales/{id} */
    public function update(UpdateSucursalRequest $request, int $id): SucursalResource
    {
        $sucursal = $this->servicio->actualizar($id, ActualizarSucursalDTO::fromArray($request->validated()));

        return new SucursalResource($sucursal);
    }
}
