<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Pedido;
use App\Models\Role;
use App\Models\User;
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
     * @return Collection<int, User>
     */
    public function listarConEstadisticas(): Collection
    {
        $instanciaId = $this->instanciaActual();
        $roleCliente = Role::query()->where('nombre', 'cliente')->first();

        if ($roleCliente === null) {
            return new Collection();
        }

        // Subconsulta de estadisticas por cliente (excluye 'cancelado')
        $subquery = DB::table('pedidos')
            ->select(
                'cliente_id',
                DB::raw('SUM(total) as total_gastado'),
                DB::raw('COUNT(id) as cantidad_pedidos'),
                DB::raw('MAX(created_at) as ultimo_pedido_en'),
            )
            ->where('estado', '!=', 'cancelado')
            ->when($instanciaId !== null, fn ($q) => $q->where('instancia_id', $instanciaId))
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
            ->leftJoinSub($subquery, 'stats', 'users.id', '=', 'stats.cliente_id')
            ->where('users.role_id', $roleCliente->id)
            ->whereNull('users.deleted_at');

        if ($instanciaId !== null) {
            $query->where('users.instancia_id', $instanciaId);
        }

        return $query->orderByDesc('total_gastado')->get();
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
}
