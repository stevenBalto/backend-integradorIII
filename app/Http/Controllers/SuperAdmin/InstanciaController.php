<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\DTOs\Instancia\ActualizarInstanciaDTO;
use App\DTOs\Instancia\CrearInstanciaDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instancia\StoreInstanciaRequest;
use App\Http\Requests\Instancia\UpdateInstanciaRequest;
use App\Http\Resources\InstanciaResource;
use App\Services\InstanciaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * CRUD de instancias (panel superadmin, aislado).
 * Ruta: ['auth:sanctum', 'superadmin'].
 */
final class InstanciaController extends Controller
{
    public function __construct(private readonly InstanciaService $service)
    {
    }

    /** GET /api/superadmin/instancias — lista todas las instancias. */
    public function index(): AnonymousResourceCollection
    {
        return InstanciaResource::collection($this->service->listar());
    }

    /**
     * POST /api/superadmin/instancias — crea instancia + admin inicial temporal.
     * Devuelve las credenciales temporales UNA sola vez.
     */
    public function store(StoreInstanciaRequest $request): JsonResponse
    {
        $result = $this->service->crear(
            CrearInstanciaDTO::fromArray($request->validated()),
            (int) $request->user()->id,
        );

        return (new InstanciaResource($result['instancia']))
            ->additional(['credenciales' => $result['credenciales']])
            ->response()
            ->setStatusCode(201);
    }

    /** PUT/PATCH /api/superadmin/instancias/{id} — actualiza datos. */
    public function update(UpdateInstanciaRequest $request, int $id): InstanciaResource
    {
        $instancia = $this->service->actualizar($id, ActualizarInstanciaDTO::fromArray($request->validated()));

        return new InstanciaResource($instancia);
    }

    /** POST /api/superadmin/instancias/{id}/estado — activar/desactivar/suspender. */
    public function cambiarEstado(Request $request, int $id): InstanciaResource
    {
        $estado = (string) $request->input('estado');
        $instancia = $this->service->cambiarEstado($id, $estado);

        return new InstanciaResource($instancia);
    }

    /** DELETE /api/superadmin/instancias/{id} — soft delete. */
    public function destroy(int $id): JsonResponse
    {
        $this->service->eliminar($id);

        return response()->json(['message' => 'Instancia eliminada.']);
    }
}
