<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\SuperAdmin\CrearSuperAdminDTO;
use App\Models\SuperAdministrador;
use Illuminate\Database\Eloquent\Collection;

/**
 * Unica capa que consulta la tabla superadministradores via Eloquent.
 */
final class SuperAdminRepository
{
    /** Busca por email O por usuario (el login acepta cualquiera de los dos). */
    public function buscarPorLogin(string $login): ?SuperAdministrador
    {
        return SuperAdministrador::query()
            ->where('email', $login)
            ->orWhere('usuario', $login)
            ->first();
    }

    public function buscarPorId(int $id): ?SuperAdministrador
    {
        return SuperAdministrador::query()->find($id);
    }

    public function buscarPorEmail(string $email): ?SuperAdministrador
    {
        return SuperAdministrador::query()->where('email', $email)->first();
    }

    /** @return Collection<int, SuperAdministrador> */
    public function listar(): Collection
    {
        return SuperAdministrador::query()->orderBy('nombre')->get();
    }

    public function crear(CrearSuperAdminDTO $dto): SuperAdministrador
    {
        // El cast 'hashed' del modelo hashea el password.
        return SuperAdministrador::create([
            'nombre' => $dto->nombre,
            'usuario' => $dto->usuario,
            'email' => $dto->email,
            'password' => $dto->password,
            'activo' => true,
        ]);
    }
}
