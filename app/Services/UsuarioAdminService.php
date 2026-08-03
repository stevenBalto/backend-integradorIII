<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Usuario\ActualizarUsuarioDTO;
use App\DTOs\Usuario\CrearUsuarioDTO;
use App\Models\User;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * CRUD de usuarios de una instancia (panel admin).
 *
 * AISLAMIENTO: todo se filtra/crea con el instancia_id del admin autenticado;
 * jamas se acepta instancia_id desde el cliente.
 *
 * REGLA DE PASSWORD (ITEM 21): La password solo se fija en el ALTA. El modulo
 * usuarios NO permite a un admin cambiar la password de otro usuario. Los
 * unicos flujos validos para cambio de password son: (a) alta, (b) flujo de
 * expiracion self-service (POST /api/auth/password-expirada), (c) reset por
 * correo (POST /api/forgot-password + POST /api/reset-password).
 */
final class UsuarioAdminService
{
    public function __construct(
        private readonly UserRepository $usuarios,
        private readonly RoleRepository $roles,
        private readonly NotificacionService $notificaciones,
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

        // Calcular fecha de expiracion de password (hoy + dias_expiracion_password).
        // NO se marca password_temporal ni cambio_password_obligatorio: la password
        // es FIJA desde el principio; solo expirara segun dias_expiracion_password.
        $passwordExpiraEn = now()->addDays($dto->diasExpiracionPassword)->toDateString();

        $user = $this->usuarios->crear([
            'instancia_id' => $instanciaId,
            'role_id' => $dto->roleId,
            'nombre' => $dto->nombre,
            'usuario' => $dto->usuario,
            'email' => $dto->email,
            'telefono' => $dto->telefono,
            'password' => $dto->password, // cast 'hashed'
            'activo' => true,
            'password_temporal' => false,
            'cambio_password_obligatorio' => false,
            'dias_expiracion_password' => $dto->diasExpiracionPassword,
            'password_expira_en' => $passwordExpiraEn,
        ]);

        $user->modulos()->sync($dto->modulos);
        $user->load(['role', 'modulos']);

        try {
            $this->notificaciones->notificarUsuarioNuevo($user);
        } catch (\Throwable $e) {
            Log::warning('No se pudo crear la notificacion de usuario nuevo', ['error' => $e->getMessage()]);
        }

        return $user;
    }

    /**
     * Actualiza un usuario. NOTA: La password NO se cambia aqui (ver docblock de la clase).
     */
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

    /**
     * Cambia el estado activo/inactivo de un usuario (toggle).
     */
    public function cambiarEstado(int $id, bool $activo, int $instanciaId, int $actorId): User
    {
        if ($id === $actorId) {
            throw ValidationException::withMessages([
                'id' => ['No podés desactivar tu propia cuenta.'],
            ]);
        }

        $user = $this->obtenerEnInstancia($id, $instanciaId);
        $this->usuarios->actualizar($user, ['activo' => $activo]);
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
