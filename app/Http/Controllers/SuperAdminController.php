<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\SuperAdmin\ActualizarSuperAdminDTO;
use App\DTOs\SuperAdmin\CrearSuperAdminDTO;
use App\Http\Requests\SuperAdmin\ResetPasswordSuperAdminRequest;
use App\Http\Requests\SuperAdmin\StoreSuperAdminRequest;
use App\Http\Requests\SuperAdmin\UpdateSuperAdminRequest;
use App\Http\Resources\SuperAdminResource;
use App\Services\SuperAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * CRUD de superadministradores. Solo accesible por un superadmin autenticado
 * (ruta protegida con middleware ['auth:sanctum', 'superadmin']).
 */
final class SuperAdminController extends Controller
{
    public function __construct(private readonly SuperAdminService $service)
    {
    }

    /** GET /api/superadmin/superadmins — lista todos los superadmins. */
    public function index(): AnonymousResourceCollection
    {
        return SuperAdminResource::collection($this->service->listar());
    }

    /** POST /api/superadmin/superadmins — crea un superadmin. */
    public function store(StoreSuperAdminRequest $request): JsonResponse
    {
        $superadmin = $this->service->crear(CrearSuperAdminDTO::fromArray($request->validated()));

        return (new SuperAdminResource($superadmin))->response()->setStatusCode(201);
    }

    /** PUT/PATCH /api/superadmin/superadmins/{id} — actualiza datos (no password). */
    public function update(UpdateSuperAdminRequest $request, int $id): SuperAdminResource
    {
        $superadmin = $this->service->actualizar($id, ActualizarSuperAdminDTO::fromArray($request->validated()));

        return new SuperAdminResource($superadmin);
    }

    /** POST /api/superadmin/superadmins/{id}/reset-password — resetea la contraseña. */
    public function resetPassword(ResetPasswordSuperAdminRequest $request, int $id): JsonResponse
    {
        $this->service->resetPassword($id, (string) $request->validated()['password']);

        return response()->json(['message' => 'Contraseña restablecida.']);
    }

    /** POST /api/superadmin/superadmins/{id}/desactivar — desactiva (no a uno mismo). */
    public function desactivar(Request $request, int $id): SuperAdminResource
    {
        $superadmin = $this->service->desactivar($id, (int) $request->user()->id);

        return new SuperAdminResource($superadmin);
    }

    /** DELETE /api/superadmin/superadmins/{id} — soft delete (no a uno mismo). */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->service->eliminar($id, (int) $request->user()->id);

        return response()->json(['message' => 'Superadministrador eliminado.']);
    }
}
