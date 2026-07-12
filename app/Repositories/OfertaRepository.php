<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Oferta;
use Illuminate\Database\Eloquent\Collection;

/**
 * Unica capa que consulta la tabla ofertas via Eloquent.
 */
final class OfertaRepository
{
    /** @return Collection<int, Oferta> */
    public function listarTodos(): Collection
    {
        return Oferta::query()
            ->with('productos')
            ->orderBy('nombre')
            ->get();
    }

    public function buscarPorId(int $id): ?Oferta
    {
        return Oferta::query()->with('productos')->find($id);
    }

    /**
     * @param array<string, mixed> $datos
     * @param array<int> $productoIds
     */
    public function crear(array $datos, array $productoIds = []): Oferta
    {
        $oferta = Oferta::create($datos);
        $oferta->productos()->sync($productoIds);
        $oferta->load('productos');

        return $oferta;
    }

    /**
     * @param array<string, mixed> $datos
     * @param array<int>|null $productoIds si es null, no se tocan las relaciones
     */
    public function actualizar(Oferta $oferta, array $datos, ?array $productoIds = null): Oferta
    {
        $oferta->update($datos);

        if ($productoIds !== null) {
            $oferta->productos()->sync($productoIds);
        }

        $oferta->load('productos');

        return $oferta;
    }

    /** Borrado fisico (DELETE real, no soft delete). */
    public function eliminar(Oferta $oferta): void
    {
        $oferta->productos()->detach();
        $oferta->delete();
    }
}
