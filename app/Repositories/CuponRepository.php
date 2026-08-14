<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Cupon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Unica capa que consulta la tabla cupones via Eloquent.
 */
final class CuponRepository
{
    /** @return Collection<int, Cupon> */
    public function listarTodos(): Collection
    {
        return Cupon::query()
            ->with(['clientes', 'sucursales'])
            ->orderBy('codigo')
            ->get();
    }

    /**
     * @return Collection<int, Cupon>
     *                                $clienteId: si viene, incluye cupones 'todos' + los 'especifico' asignados a ese cliente.
     *                                Si es null (invitado), excluye los 'especifico'.
     */
    public function listarActivos(?int $clienteId = null): Collection
    {
        $hoy = now('America/Costa_Rica')->toDateString();

        return Cupon::query()
            ->with('sucursales')
            ->where('activo', true)
            ->where(function ($query) use ($hoy): void {
                $query->whereNull('fecha_inicio')
                    ->orWhereDate('fecha_inicio', '<=', $hoy);
            })
            ->where(function ($query) use ($hoy): void {
                $query->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', $hoy);
            })
            ->where(function ($query): void {
                $query->whereNull('usos_max')
                    ->orWhereColumn('usos_actuales', '<', 'usos_max');
            })
            ->where(function ($query) use ($clienteId): void {
                $query->where('alcance', 'todos');
                if ($clienteId !== null) {
                    $query->orWhereHas('clientes', fn ($q) => $q->where('users.id', $clienteId));
                }
            })
            ->orderBy('codigo')
            ->get();
    }

    public function buscarPorId(int $id): ?Cupon
    {
        return Cupon::query()->with(['clientes', 'sucursales'])->find($id);
    }

    public function buscarPorCodigo(string $codigo): ?Cupon
    {
        return Cupon::query()->where('codigo', strtoupper($codigo))->first();
    }

    /**
     * Busca un cupon por codigo SOLO si esta activo, vigente y con usos disponibles.
     * $clienteId: si el cupon es 'especifico', solo lo devuelve si el cliente esta asignado
     * (null = invitado, nunca elegible para cupones especificos).
     */
    public function buscarActivoPorCodigo(string $codigo, ?int $clienteId = null): ?Cupon
    {
        $hoy = now('America/Costa_Rica')->toDateString();

        return Cupon::query()
            ->with(['clientes', 'sucursales'])
            ->where('codigo', strtoupper($codigo))
            ->where('activo', true)
            ->where(function ($query) use ($hoy): void {
                $query->whereNull('fecha_inicio')
                    ->orWhereDate('fecha_inicio', '<=', $hoy);
            })
            ->where(function ($query) use ($hoy): void {
                $query->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', $hoy);
            })
            ->where(function ($query): void {
                $query->whereNull('usos_max')
                    ->orWhereColumn('usos_actuales', '<', 'usos_max');
            })
            ->where(function ($query) use ($clienteId): void {
                $query->where('alcance', 'todos');
                if ($clienteId !== null) {
                    $query->orWhereHas('clientes', fn ($q) => $q->where('users.id', $clienteId));
                }
            })
            ->first();
    }

    /**
     * @param  array<string, mixed>  $datos
     * @param  array<int>  $clienteIds
     * @param  array<int>  $sucursalIds
     */
    public function crear(array $datos, array $clienteIds = [], array $sucursalIds = []): Cupon
    {
        $cupon = Cupon::create($datos);
        $cupon->clientes()->sync($clienteIds);
        $cupon->sucursales()->sync($sucursalIds);
        $cupon->load(['clientes', 'sucursales']);

        return $cupon;
    }

    /**
     * @param  array<string, mixed>  $datos
     * @param  array<int>|null  $clienteIds  si es null, no se tocan las relaciones
     * @param  array<int>|null  $sucursalIds  si es null, no se tocan las relaciones
     */
    public function actualizar(Cupon $cupon, array $datos, ?array $clienteIds = null, ?array $sucursalIds = null): Cupon
    {
        $cupon->update($datos);

        if ($clienteIds !== null) {
            $cupon->clientes()->sync($clienteIds);
        }

        if ($sucursalIds !== null) {
            $cupon->sucursales()->sync($sucursalIds);
        }

        $cupon->load(['clientes', 'sucursales']);

        return $cupon;
    }

    /** Borrado fisico (DELETE real, no soft delete). */
    public function eliminar(Cupon $cupon): void
    {
        $cupon->clientes()->detach();
        $cupon->sucursales()->detach();
        $cupon->delete();
    }

    /** Incrementa el contador de usos (canje via QR/pedido de mostrador). */
    public function incrementarUso(Cupon $cupon): void
    {
        $cupon->increment('usos_actuales');
    }
}
