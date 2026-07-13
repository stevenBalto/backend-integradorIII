<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Instancia;
use Illuminate\Database\Eloquent\Collection;

/**
 * Acceso a datos de la tabla instancias.
 */
final class InstanciaRepository
{
    /** @return Collection<int, Instancia> */
    public function listar(): Collection
    {
        return Instancia::query()
            ->withCount('users')
            ->orderByDesc('created_at')
            ->get();
    }

    public function buscarPorId(int $id): ?Instancia
    {
        return Instancia::query()->withCount('users')->find($id);
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function crear(array $datos): Instancia
    {
        return Instancia::create($datos);
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function actualizar(Instancia $instancia, array $datos): Instancia
    {
        $instancia->update($datos);

        return $instancia;
    }

    public function eliminar(Instancia $instancia): void
    {
        $instancia->delete();
    }
}
