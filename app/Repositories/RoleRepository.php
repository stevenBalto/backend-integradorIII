<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

/**
 * Acceso a datos de la tabla roles.
 */
final class RoleRepository
{
    public function idPorNombre(string $nombre): ?int
    {
        $id = Role::query()->where('nombre', $nombre)->value('id');

        return $id === null ? null : (int) $id;
    }

    public function buscarPorId(int $id): ?Role
    {
        return Role::query()->find($id);
    }

    /**
     * Roles que un admin puede asignar desde el panel (NUNCA super_admin,
     * eso seria escalacion de privilegios).
     *
     * @return Collection<int, Role>
     */
    public function asignables(): Collection
    {
        return Role::query()
            ->whereNotIn('nombre', ['super_admin'])
            ->orderBy('id')
            ->get();
    }
}
