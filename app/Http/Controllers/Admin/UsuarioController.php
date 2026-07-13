<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTOs\Usuario\ActualizarUsuarioDTO;
use App\DTOs\Usuario\CrearUsuarioDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Usuario\StoreUsuarioRequest;
use App\Http\Requests\Usuario\UpdateUsuarioRequest;
use App\Http\Resources\AdminUserResource;
use App\Http\Resources\ModuloResource;
use App\Http\Resources\RoleResource;
use App\Repositories\ModuloRepository;
use App\Repositories\RoleRepository;
use App\Services\UsuarioAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * CRUD de usuarios de la instancia (panel admin). Aislado por instancia_id
 * del admin autenticado. Ruta: ['auth:sanctum', 'role:super_admin,admin_sede'].
 */
final class UsuarioController extends Controller
{
    public function __construct(
        private readonly UsuarioAdminService $service,
        private readonly RoleRepository $roles,
        private readonly ModuloRepository $modulos,
    ) {
    }

    /** GET /api/admin/usuarios — usuarios de la instancia del admin. */
    public function index(Request $request): AnonymousResourceCollection
    {
        return AdminUserResource::collection(
            $this->service->listar($this->instanciaId($request)),
        );
    }

    /** GET /api/admin/usuarios/opciones — roles asignables + módulos para el formulario. */
    public function opciones(): JsonResponse
    {
        return response()->json([
            'roles' => RoleResource::collection($this->roles->asignables()),
            'modulos' => ModuloResource::collection($this->modulos->activos()),
        ]);
    }

    /** POST /api/admin/usuarios — crea un usuario (con contraseña temporal + módulos). */
    public function store(StoreUsuarioRequest $request): JsonResponse
    {
        $usuario = $this->service->crear(
            CrearUsuarioDTO::fromArray($request->validated()),
            $this->instanciaId($request),
        );

        return (new AdminUserResource($usuario))->response()->setStatusCode(201);
    }

    /** PUT/PATCH /api/admin/usuarios/{id} — actualiza un usuario. */
    public function update(UpdateUsuarioRequest $request, int $id): AdminUserResource
    {
        $usuario = $this->service->actualizar(
            $id,
            ActualizarUsuarioDTO::fromArray($request->validated()),
            $this->instanciaId($request),
        );

        return new AdminUserResource($usuario);
    }

    /** DELETE /api/admin/usuarios/{id} — soft delete (no a uno mismo). */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->service->eliminar($id, $this->instanciaId($request), (int) $request->user()->id);

        return response()->json(['message' => 'Usuario eliminado.']);
    }

    /** Instancia del admin autenticado (NUNCA se toma del request). */
    private function instanciaId(Request $request): int
    {
        return (int) $request->user()->instancia_id;
    }
}
