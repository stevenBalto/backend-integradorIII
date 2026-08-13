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
     * Instancia mas antigua activa: el negocio "principal" del sistema.
     * La usa el panel de superadmin para saber a que negocio pertenece una
     * sede nueva, ya que el superadmin no tiene instancia propia.
     */
    public function primeraActiva(): ?Instancia
    {
        return Instancia::query()
            ->where('estado', 'activa')
            ->orderBy('id')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public function crear(array $datos): Instancia
    {
        return Instancia::create($datos);
    }

    /**
     * @param  array<string, mixed>  $datos
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
