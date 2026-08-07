<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\Auth\RegistrarUsuarioDTO;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

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

    public function buscarPorId(int $id): ?User
    {
        return User::query()->find($id);
    }

    /** Cuenta ya vinculada a una cuenta de Google (claim "sub" del ID token). */
    public function buscarPorGoogleId(string $googleId): ?User
    {
        return User::query()->where('google_id', $googleId)->first();
    }

    /** Ata una cuenta que ya existia (email + contrasena) a su cuenta de Google. */
    public function vincularGoogle(User $user, string $googleId): User
    {
        $user->google_id = $googleId;
        $user->save();

        return $user;
    }

    /**
     * Cliente nuevo que entra por primera vez con Google.
     *
     * `password` es NOT NULL en el esquema y esta cuenta no la usa nunca: se le
     * pone una aleatoria (el cast 'hashed' del modelo la hashea) en vez de tocar
     * la nulabilidad de la columna, que arrastraria todo el flujo de login,
     * expiracion y cambio obligatorio de contrasena.
     */
    public function crearClienteDeGoogle(
        string $email,
        string $nombre,
        string $googleId,
        int $rolClienteId,
    ): User {
        return User::create([
            'role_id'      => $rolClienteId,
            'instancia_id' => self::INSTANCIA_DEFAULT,
            'nombre'       => $nombre,
            'email'        => $email,
            'password'     => Str::random(48),
            'google_id'    => $googleId,
        ]);
    }

    public function buscarPorUsuario(string $usuario): ?User
    {
        return User::query()->where('usuario', $usuario)->first();
    }

    public function existeUsuario(string $usuario): bool
    {
        return User::query()->where('usuario', $usuario)->exists();
    }

    // ── Panel admin: gestion de usuarios de la instancia ────────────────────

    /**
     * Usuarios de una instancia (aislamiento: SIEMPRE filtra por instancia_id).
     * Solo trae el rol 'admin_sede': excluye 'cliente' (panel de Clientes, ver
     * ClienteRepository) y 'super_admin' (ese vive en la tabla
     * `superadministradores`, ver SuperAdminRepository, no en este panel).
     *
     * @return Collection<int, User>
     */
    public function listarDeInstancia(int $instanciaId): Collection
    {
        return User::query()
            ->with(['role', 'modulos'])
            ->where('instancia_id', $instanciaId)
            ->whereHas('role', fn ($q) => $q->where('nombre', 'admin_sede'))
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Busca un usuario admin_sede dentro de una instancia (nunca de otra).
     * Excluye 'cliente' y 'super_admin' por el mismo motivo que listarDeInstancia().
     */
    public function buscarEnInstancia(int $id, int $instanciaId): ?User
    {
        return User::query()
            ->with(['role', 'modulos'])
            ->where('id', $id)
            ->where('instancia_id', $instanciaId)
            ->whereHas('role', fn ($q) => $q->where('nombre', 'admin_sede'))
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
