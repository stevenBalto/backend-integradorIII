<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Collection;

/**
 * Unica capa que consulta la tabla categorias via Eloquent.
 */
final class CategoriaRepository
{
    /** @return Collection<int, Categoria> */
    public function listarActivas(): Collection
    {
        return Categoria::query()
            ->where('activa', true)
            ->orderBy('orden')
            ->get();
    }

    public function existe(int $id): bool
    {
        return Categoria::query()->whereKey($id)->exists();
    }

    public function buscarPorId(int $id): ?Categoria
    {
        return Categoria::query()->find($id);
    }
}
