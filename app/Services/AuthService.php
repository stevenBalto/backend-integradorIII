<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Auth\CredencialesDTO;
use App\DTOs\Auth\RegistrarUsuarioDTO;
use App\Models\User;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Logica de negocio de autenticacion: registro, login y logout con Sanctum.
 */
final class AuthService
{
    public function __construct(
        private readonly UserRepository $usuarios,
        private readonly RoleRepository $roles,
    ) {
    }

    /**
     * Registra un cliente nuevo y devuelve el usuario + token.
     *
     * @return array{user: User, token: string}
     */
    public function registrar(RegistrarUsuarioDTO $dto): array
    {
        $rolClienteId = $this->roles->idPorNombre('cliente');
        if ($rolClienteId === null) {
            throw new RuntimeException('El rol "cliente" no existe. Ejecuta RolesSeeder.');
        }

        $user = $this->usuarios->crearCliente($dto, $rolClienteId);
        $token = $user->createToken('auth')->plainTextToken;
        $user->load('role');

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Valida credenciales y devuelve el usuario + token.
     *
     * @return array{user: User, token: string}
     *
     * @throws ValidationException credenciales invalidas o cuenta inactiva
     */
    public function login(CredencialesDTO $dto): array
    {
        $user = $this->usuarios->buscarPorEmail($dto->email);

        if ($user === null || ! Hash::check($dto->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales inválidas.'],
            ]);
        }

        if (! $user->activo) {
            throw ValidationException::withMessages([
                'email' => ['La cuenta está inactiva.'],
            ]);
        }

        // Aislamiento multi-tenant: si la instancia no está activa, nadie de ella entra.
        $user->load('instancia');
        if ($user->instancia !== null && ! $user->instancia->estaActiva()) {
            throw ValidationException::withMessages([
                'email' => ['La instancia asociada está inactiva. Contactá al administrador.'],
            ]);
        }

        $token = $user->createToken('auth')->plainTextToken;
        $user->load('role');

        return ['user' => $user, 'token' => $token];
    }

    /** Invalida el token actual del usuario. */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
