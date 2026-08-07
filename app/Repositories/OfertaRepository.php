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
            ->with(['productos', 'clientes'])
            ->orderBy('nombre')
            ->get();
    }

    /**
     * @return Collection<int, Oferta>
     * $clienteId: si viene, incluye ofertas 'todos' + las 'especifico' asignadas a ese cliente.
     * Si es null (invitado), excluye las 'especifico'.
     */
    public function listarActivas(?int $clienteId = null): Collection
    {
        $hoy = now()->toDateString();

        return Oferta::query()
            ->with('productos')
            ->where('activa', true)
            ->where(function ($query) use ($hoy): void {
                $query->whereNull('fecha_inicio')
                    ->orWhereDate('fecha_inicio', '<=', $hoy);
            })
            ->where(function ($query) use ($hoy): void {
                $query->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', $hoy);
            })
            ->where(function ($query) use ($clienteId): void {
                $query->where('alcance', 'todos');
                if ($clienteId !== null) {
                    $query->orWhereHas('clientes', fn ($q) => $q->where('users.id', $clienteId));
                }
            })
            ->orderBy('nombre')
            ->get();
    }

    public function buscarPorId(int $id): ?Oferta
    {
        return Oferta::query()->with(['productos', 'clientes'])->find($id);
    }

    /**
     * @param array<string, mixed> $datos
     * @param array<int> $productoIds
     * @param array<int> $clienteIds
     */
    public function crear(array $datos, array $productoIds = [], array $clienteIds = []): Oferta
    {
        $oferta = Oferta::create($datos);
        $oferta->productos()->sync($productoIds);
        $oferta->clientes()->sync($clienteIds);
        $oferta->load(['productos', 'clientes']);

        return $oferta;
    }

    /**
     * @param array<string, mixed> $datos
     * @param array<int>|null $productoIds si es null, no se tocan las relaciones
     * @param array<int>|null $clienteIds si es null, no se tocan las relaciones
     */
    public function actualizar(Oferta $oferta, array $datos, ?array $productoIds = null, ?array $clienteIds = null): Oferta
    {
        $oferta->update($datos);

        if ($productoIds !== null) {
            $oferta->productos()->sync($productoIds);
        }

        if ($clienteIds !== null) {
            $oferta->clientes()->sync($clienteIds);
        }

        $oferta->load(['productos', 'clientes']);

        return $oferta;
    }

    /** Borrado fisico (DELETE real, no soft delete). */
    public function eliminar(Oferta $oferta): void
    {
        $oferta->productos()->detach();
        $oferta->clientes()->detach();
        $oferta->delete();
    }
}
