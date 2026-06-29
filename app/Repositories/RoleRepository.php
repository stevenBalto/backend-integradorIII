<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Role;

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
}
