<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\SucursalResource;
use App\Repositories\SucursalRepository;
use App\Services\SucursalService;
use Illuminate\Http\JsonResponse;

/**
 * Endpoints de sucursales para cliente y admin: solo LECTURA.
 * El alta y la edicion de sedes viven en SuperAdmin\SedeController, para que
 * exista un unico lugar donde se dan de alta.
 */
final class SucursalController extends Controller
{
    public function __construct(
        private readonly SucursalRepository $sucursales,
        private readonly SucursalService $servicio,
    ) {}

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
}
