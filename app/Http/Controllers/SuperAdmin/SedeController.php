<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\DTOs\Sucursal\ActualizarSucursalDTO;
use App\DTOs\Sucursal\CrearSucursalDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sucursal\CambiarEstadoSedeRequest;
use App\Http\Requests\Sucursal\StoreSucursalRequest;
use App\Http\Requests\Sucursal\UpdateSucursalRequest;
use App\Http\Resources\SucursalResource;
use App\Services\SucursalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Sedes del negocio, gestionadas desde el panel de superadmin.
 * Ruta: ['auth:sanctum', 'superadmin'].
 *
 * Es el unico lugar donde se dan de alta las sedes. Todas comparten menu,
 * precios, ofertas y clientes (viven en la misma instancia); lo que se separa
 * es la operacion: cada sede solo ve sus propios pedidos.
 *
 * El superadmin no tiene instancia propia, asi que el servicio resuelve a que
 * negocio pertenece la sede (ver SucursalService::instanciaDestino).
 */
final class SedeController extends Controller
{
    public function __construct(private readonly SucursalService $service) {}

    /** GET /api/superadmin/sedes — todas las sedes del negocio. */
    public function index(): AnonymousResourceCollection
    {
        return SucursalResource::collection($this->service->listarTodasParaSuperadmin());
    }

    /**
     * POST /api/superadmin/sedes — crea la sede y su administrador.
     * Las credenciales temporales viajan UNA sola vez en esta respuesta.
     */
    public function store(StoreSucursalRequest $request): JsonResponse
    {
        $resultado = $this->service->crear(CrearSucursalDTO::fromArray($request->validated()));

        return (new SucursalResource($resultado['sucursal']))
            ->additional(['credenciales' => $resultado['credenciales']])
            ->response()
            ->setStatusCode(201);
    }

    /** PUT/PATCH /api/superadmin/sedes/{id} */
    public function update(UpdateSucursalRequest $request, int $id): SucursalResource
    {
        return new SucursalResource(
            $this->service->actualizar($id, ActualizarSucursalDTO::fromArray($request->validated())),
        );
    }

    /**
     * POST /api/superadmin/sedes/{id}/estado — cierra o reabre la sede.
     *
     * Cerrar no borra nada: la sede sale del selector del cliente y deja de
     * recibir pedidos, pero conserva su historial y sus administradores siguen
     * entrando en modo solo lectura.
     */
    public function cambiarEstado(CambiarEstadoSedeRequest $request, int $id): SucursalResource
    {
        return new SucursalResource(
            $this->service->cambiarEstado($id, (bool) $request->validated('activa')),
        );
    }
}
