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

    /**
     * Crea una categoria de la instancia actual. El `orden` se asigna al final
     * (MAX + 1 dentro de la instancia) y `activa` arranca en true. El `instancia_id`
     * lo asigna automaticamente el trait PerteneceAInstancia (nunca del request).
     */
    public function crear(array $datos): Categoria
    {
        return Categoria::create([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'orden' => $this->siguienteOrden(),
            'activa' => true,
        ]);
    }

    /** Proximo `orden` disponible en la instancia actual (1 si aun no hay ninguna). */
    private function siguienteOrden(): int
    {
        // El global scope de instancia acota el MAX a la instancia del usuario autenticado.
        return (int) Categoria::query()->max('orden') + 1;
    }
}
