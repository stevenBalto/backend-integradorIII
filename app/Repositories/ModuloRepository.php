<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Modulo;
use Illuminate\Database\Eloquent\Collection;

/**
 * Acceso a datos del catalogo de modulos del panel admin.
 */
final class ModuloRepository
{
    /** @return Collection<int, Modulo> */
    public function activos(): Collection
    {
        return Modulo::query()->where('activo', true)->orderBy('orden')->get();
    }

    /**
     * IDs validos de modulos (para filtrar lo que manda el cliente).
     *
     * @return list<int>
     */
    public function idsValidos(): array
    {
        return Modulo::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
