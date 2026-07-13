<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Usuario\ActualizarUsuarioDTO;
use App\DTOs\Usuario\CrearUsuarioDTO;
use App\Models\User;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * CRUD de usuarios de una instancia (panel admin).
 * AISLAMIENTO: todo se filtra/crea con el instancia_id del admin autenticado;
 * jamas se acepta instancia_id desde el cliente.
 */
final class UsuarioAdminService
{
    public function __construct(
        private readonly UserRepository $usuarios,
        private readonly RoleRepository $roles,
    ) {
    }

    /** @return Collection<int, User> */
    public function listar(int $instanciaId): Collection
    {
        return $this->usuarios->listarDeInstancia($instanciaId);
    }

    public function crear(CrearUsuarioDTO $dto, int $instanciaId): User
    {
        $this->assertRolAsignable($dto->roleId);

        // Password temporal: debe cambiarla en el primer inicio de sesion.
        $user = $this->usuarios->crear([
            'instancia_id' => $instanciaId,
            'role_id' => $dto->roleId,
            'nombre' => $dto->nombre,
            'usuario' => $dto->usuario,
            'email' => $dto->email,
            'telefono' => $dto->telefono,
            'password' => $dto->password, // cast 'hashed'
            'activo' => true,
            'password_temporal' => true,
            'cambio_password_obligatorio' => true,
        ]);

        $user->modulos()->sync($dto->modulos);
        $user->load(['role', 'modulos']);

        return $user;
    }

    public function actualizar(int $id, ActualizarUsuarioDTO $dto, int $instanciaId): User
    {
        $user = $this->obtenerEnInstancia($id, $instanciaId);

        if ($dto->roleId !== null) {
            $this->assertRolAsignable($dto->roleId);
        }

        $this->usuarios->actualizar($user, $dto->camposUsuario());

        if ($dto->modulos !== null) {
            $user->modulos()->sync($dto->modulos);
        }

        $user->load(['role', 'modulos']);

        return $user;
    }

    public function eliminar(int $id, int $instanciaId, int $actorId): void
    {
        if ($id === $actorId) {
            throw ValidationException::withMessages([
                'id' => ['No podés eliminar tu propia cuenta.'],
            ]);
        }

        $user = $this->obtenerEnInstancia($id, $instanciaId);
        $this->usuarios->eliminar($user);
    }

    private function obtenerEnInstancia(int $id, int $instanciaId): User
    {
        $user = $this->usuarios->buscarEnInstancia($id, $instanciaId);

        if ($user === null) {
            throw ValidationException::withMessages([
                'id' => ['El usuario no existe en esta instancia.'],
            ]);
        }

        return $user;
    }

    /** Un admin NUNCA puede asignar super_admin (anti-escalacion de privilegios). */
    private function assertRolAsignable(int $roleId): void
    {
        $role = $this->roles->buscarPorId($roleId);

        if ($role === null || $role->nombre === 'super_admin') {
            throw ValidationException::withMessages([
                'role_id' => ['El rol seleccionado no es válido.'],
            ]);
        }
    }
}
