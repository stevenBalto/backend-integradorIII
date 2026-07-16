<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\Auth\RegistrarUsuarioDTO;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Unica capa que consulta la tabla users via Eloquent.
 */
final class UserRepository
{
    /**
     * Instancia a la que se asigna un cliente que se registra por el formulario
     * publico (no elige tenant). Hoy el negocio real es una sola instancia.
     */
    private const INSTANCIA_DEFAULT = 1;

    public function crearCliente(RegistrarUsuarioDTO $dto, int $rolClienteId): User
    {
        // El cast 'hashed' del modelo se encarga de hashear el password.
        return User::create([
            'role_id'      => $rolClienteId,
            'instancia_id' => self::INSTANCIA_DEFAULT,
            'nombre'       => $dto->nombre,
            'email'        => $dto->email,
            'password'     => $dto->password,
            'telefono'     => $dto->telefono,
        ]);
    }

    public function buscarPorEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function existeUsuario(string $usuario): bool
    {
        return User::query()->where('usuario', $usuario)->exists();
    }

    // ── Panel admin: gestion de usuarios de la instancia ────────────────────

    /**
     * Usuarios de una instancia (aislamiento: SIEMPRE filtra por instancia_id).
     *
     * @return Collection<int, User>
     */
    public function listarDeInstancia(int $instanciaId): Collection
    {
        return User::query()
            ->with(['role', 'modulos'])
            ->where('instancia_id', $instanciaId)
            ->orderByDesc('created_at')
            ->get();
    }

    /** Busca un usuario dentro de una instancia (nunca de otra). */
    public function buscarEnInstancia(int $id, int $instanciaId): ?User
    {
        return User::query()
            ->with(['role', 'modulos'])
            ->where('id', $id)
            ->where('instancia_id', $instanciaId)
            ->first();
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function crear(array $datos): User
    {
        return User::create($datos);
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function actualizar(User $user, array $datos): User
    {
        $user->update($datos);

        return $user;
    }

    public function eliminar(User $user): void
    {
        $user->delete();
    }
}
