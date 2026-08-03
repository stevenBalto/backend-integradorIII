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
 *
 * REGLA (ITEM 21): El modulo usuarios NO permite cambiar la password de otro
 * usuario. La password solo se fija al crear. Los cambios son: (a) flujo de
 * expiracion self-service, (b) reset por correo.
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

    /** GET /api/admin/usuarios/opciones — roles asignables + modulos para el formulario. */
    public function opciones(): JsonResponse
    {
        return response()->json([
            'roles' => RoleResource::collection($this->roles->asignables()),
            'modulos' => ModuloResource::collection($this->modulos->activos()),
        ]);
    }

    /** GET /api/admin/modulos — catalogo de todos los modulos (para el modal del frontend). */
    public function modulos(): AnonymousResourceCollection
    {
        return ModuloResource::collection($this->modulos->activos());
    }

    /** POST /api/admin/usuarios — crea un usuario (con password fija + modulos con permiso). */
    public function store(StoreUsuarioRequest $request): JsonResponse
    {
        $usuario = $this->service->crear(
            CrearUsuarioDTO::fromArray($request->validated()),
            $this->instanciaId($request),
        );

        return (new AdminUserResource($usuario))->response()->setStatusCode(201);
    }

    /** PUT/PATCH /api/admin/usuarios/{id} — actualiza un usuario (sin password). */
    public function update(UpdateUsuarioRequest $request, int $id): AdminUserResource
    {
        $usuario = $this->service->actualizar(
            $id,
            ActualizarUsuarioDTO::fromArray($request->validated()),
            $this->instanciaId($request),
        );

        return new AdminUserResource($usuario);
    }

    /** PATCH /api/admin/usuarios/{id}/estado — toggle activo/inactivo. */
    public function cambiarEstado(Request $request, int $id): AdminUserResource
    {
        $request->validate([
            'activo' => ['required', 'boolean'],
        ]);

        $usuario = $this->service->cambiarEstado(
            $id,
            (bool) $request->input('activo'),
            $this->instanciaId($request),
            (int) $request->user()->id,
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
