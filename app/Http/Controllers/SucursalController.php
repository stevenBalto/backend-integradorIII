<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\SucursalResource;
use App\Repositories\SucursalRepository;
use Illuminate\Http\JsonResponse;

/**
 * Endpoints de sucursales (lectura para clientes y admins, no CRUD completo).
 */
final class SucursalController extends Controller
{
    public function __construct(
        private readonly SucursalRepository $sucursales,
    ) {
    }

    /** GET /api/sucursales — listado de sucursales activas. */
    public function index(): JsonResponse
    {
        return SucursalResource::collection($this->sucursales->listarActivas())
            ->response();
    }
}
