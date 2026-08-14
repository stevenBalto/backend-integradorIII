<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Pedido;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Consultas de clientes con estadisticas de compra (modulo Clientes, solo lectura).
 * Filtra usuarios con rol 'cliente' de la instancia actual.
 */
final class ClienteRepository
{
    /**
     * Lista clientes de la instancia con estadisticas agregadas de compra.
     * Excluye pedidos cancelados de los calculos.
     *
     * @param  int|null  $porPagina  Si viene, devuelve un LengthAwarePaginator en vez de la Collection completa.
     * @return Collection<int, User>|LengthAwarePaginator
     */
    public function listarConEstadisticas(?int $porPagina = null, int $pagina = 1)
    {
        $instanciaId = $this->instanciaActual();
        $sucursalId = $this->sucursalActual();
        $roleCliente = Role::query()->where('nombre', 'cliente')->first();

        if ($roleCliente === null) {
            return new Collection;
        }

        // Subconsulta de estadisticas por cliente (excluye 'cancelado'). Esta
        // query es RAW (DB::table), no pasa por Eloquent -> el global scope
        // de Pedido (PerteneceASucursal) NO la filtra sola, hay que repetir
        // el mismo criterio a mano aca.
        $subquery = DB::table('pedidos')
            ->select(
                'cliente_id',
                DB::raw('SUM(total) as total_gastado'),
                DB::raw('COUNT(id) as cantidad_pedidos'),
                DB::raw('MAX(created_at) as ultimo_pedido_en'),
            )
            ->where('estado', '!=', 'cancelado')
            ->when($instanciaId !== null, fn ($q) => $q->where('instancia_id', $instanciaId))
            ->when($sucursalId !== null, fn ($q) => $q->where('sucursal_id', $sucursalId))
            ->groupBy('cliente_id');

        $query = User::query()
            ->select([
                'users.id',
                'users.nombre',
                'users.email',
                'users.telefono',
                'users.puntos_balance',
                'users.activo',
                DB::raw('COALESCE(stats.total_gastado, 0) as total_gastado'),
                DB::raw('COALESCE(stats.cantidad_pedidos, 0) as cantidad_pedidos'),
                DB::raw('stats.ultimo_pedido_en'),
            ])
            ->where('users.role_id', $roleCliente->id)
            ->whereNull('users.deleted_at');

        if ($sucursalId !== null) {
            // Admin de sede: solo clientes que YA compraron en su sede (inner
            // join) — no tiene sentido listarle clientes que nunca pisaron
            // esa sede en particular, aunque sean del mismo negocio.
            $query->joinSub($subquery, 'stats', 'users.id', '=', 'stats.cliente_id');
        } else {
            $query->leftJoinSub($subquery, 'stats', 'users.id', '=', 'stats.cliente_id');
        }

        if ($instanciaId !== null) {
            $query->where('users.instancia_id', $instanciaId);
        }

        $query->orderByDesc('total_gastado');

        if ($porPagina !== null) {
            return $query->paginate($porPagina, ['*'], 'pagina', $pagina);
        }

        return $query->get();
    }

    /**
     * Verifica que un usuario sea cliente de la instancia actual.
     */
    public function buscarClientePorId(int $id): ?User
    {
        $instanciaId = $this->instanciaActual();
        $roleCliente = Role::query()->where('nombre', 'cliente')->first();

        if ($roleCliente === null) {
            return null;
        }

        $query = User::query()
            ->where('id', $id)
            ->where('role_id', $roleCliente->id)
            ->whereNull('deleted_at');

        if ($instanciaId !== null) {
            $query->where('instancia_id', $instanciaId);
        }

        return $query->first();
    }

    /**
     * Lista pedidos de un cliente (excluye cancelados no, muestra todos).
     * Ordenado por fecha descendente.
     *
     * @return Collection<int, Pedido>
     */
    public function listarPedidosPorCliente(int $clienteId): Collection
    {
        return Pedido::query()
            ->where('cliente_id', $clienteId)
            ->orderByDesc('created_at')
            ->get();
    }

    /** Instancia del usuario autenticado; null para superadmin o sin sesion. */
    private function instanciaActual(): ?int
    {
        $actor = Auth::user();

        if ($actor instanceof User && $actor->instancia_id !== null) {
            return (int) $actor->instancia_id;
        }

        return null;
    }

    /** Sede del admin_sede autenticado; null para admin general, cliente o superadmin. */
    private function sucursalActual(): ?int
    {
        $actor = Auth::user();

        if ($actor instanceof User && $actor->esAdminSede() && $actor->sucursal_id !== null) {
            return (int) $actor->sucursal_id;
        }

        return null;
    }
}
