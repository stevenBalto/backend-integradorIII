<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\Auth\RegistrarUsuarioDTO;
use App\Models\User;

/**
 * Unica capa que consulta la tabla users via Eloquent.
 */
final class UserRepository
{
    public function crearCliente(RegistrarUsuarioDTO $dto, int $rolClienteId): User
    {
        // El cast 'hashed' del modelo se encarga de hashear el password.
        return User::create([
            'role_id'  => $rolClienteId,
            'nombre'   => $dto->nombre,
            'email'    => $dto->email,
            'password' => $dto->password,
            'telefono' => $dto->telefono,
        ]);
    }

    public function buscarPorEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }
}
