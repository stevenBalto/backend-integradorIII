<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\DetallePedido;
use App\Models\DetallePedidoExtra;
use App\Models\Pedido;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Unica capa que consulta la tabla pedidos via Eloquent.
 */
final class PedidoRepository
{
    /**
     * Crea un pedido con sus detalles y extras en una transaccion.
     *
     * @param array $datosPedido Datos del pedido principal.
     * @param array $items Items con formato:
     *   [['producto_id'=>int,'producto_tamano_id'=>?int,'tamano_nombre'=>?string,
     *     'cantidad'=>int,'precio_unitario'=>float,'subtotal'=>float,'notas'=>?string,
     *     'extras'=>[['extra_id'=>int,'precio'=>float]]]]
     */
    public function crear(array $datosPedido, array $items): Pedido
    {
        return DB::transaction(function () use ($datosPedido, $items): Pedido {
            $pedido = Pedido::create($datosPedido);

            foreach ($items as $item) {
                $extras = $item['extras'] ?? [];
                unset($item['extras']);

                $detalle = DetallePedido::create([
                    'pedido_id' => $pedido->id,
                    ...$item,
                ]);

                foreach ($extras as $extra) {
                    DetallePedidoExtra::create([
                        'detalle_pedido_id' => $detalle->id,
                        'extra_id' => $extra['extra_id'],
                        'precio' => $extra['precio'],
                    ]);
                }
            }

            return $pedido->load([
                'sucursal',
                'detalles.producto',
                'detalles.extras.extra',
            ]);
        });
    }

    /** @return Collection<int, Pedido> Pedidos de un cliente. */
    public function listarDeCliente(int $userId): Collection
    {
        return Pedido::query()
            ->where('cliente_id', $userId)
            ->with(['sucursal', 'detalles.producto', 'detalles.extras.extra'])
            ->orderByDesc('created_at')
            ->get();
    }

    /** Busca un pedido de un cliente especifico. */
    public function buscarDeCliente(int $userId, int $pedidoId): ?Pedido
    {
        return Pedido::query()
            ->where('cliente_id', $userId)
            ->where('id', $pedidoId)
            ->with(['sucursal', 'detalles.producto', 'detalles.extras.extra'])
            ->first();
    }

    /** Busca un pedido de un cliente especifico por codigo (detalle completo). */
    public function buscarDeClientePorCodigo(int $userId, string $codigo): ?Pedido
    {
        return Pedido::query()
            ->where('cliente_id', $userId)
            ->where('codigo', $codigo)
            ->with(['sucursal', 'detalles.producto', 'detalles.extras.extra'])
            ->first();
    }

    /**
     * Lista pedidos para administracion con filtros.
     *
     * @param array $filtros Keys: estado, modalidad, q (busqueda en codigo o nombre cliente).
     * @return Collection<int, Pedido>
     */
    public function listarAdmin(array $filtros): Collection
    {
        $query = Pedido::query()
            ->with(['cliente', 'sucursal', 'detalles.producto', 'detalles.extras.extra']);

        if (! empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (! empty($filtros['modalidad'])) {
            $query->where('modalidad', $filtros['modalidad']);
        }

        if (! empty($filtros['q'])) {
            $busqueda = '%' . $filtros['q'] . '%';
            $query->where(function ($q) use ($busqueda) {
                $q->where('codigo', 'ILIKE', $busqueda)
                    ->orWhereHas('cliente', function ($qCliente) use ($busqueda) {
                        $qCliente->where('nombre', 'ILIKE', $busqueda);
                    });
            });
        }

        return $query->orderByDesc('created_at')->get();
    }

    /** Busca un pedido por ID para administracion (con todas las relaciones). */
    public function buscarPorId(int $id): ?Pedido
    {
        return Pedido::query()
            ->with([
                'cliente',
                'sucursal',
                'detalles.producto',
                'detalles.extras.extra',
                'historial.cambiadoPor',
            ])
            ->find($id);
    }

    /**
     * Busca un pedido por codigo SIN filtrar por instancia (para lookup publico).
     * El codigo es globalmente unico, no necesita filtro de tenant.
     */
    public function buscarPorCodigo(string $codigo): ?Pedido
    {
        return Pedido::withoutGlobalScope('instancia')
            ->with('sucursal')
            ->where('codigo', $codigo)
            ->first();
    }

    /** Verifica si ya existe un pedido con ese codigo. */
    public function existeCodigo(string $codigo): bool
    {
        return Pedido::withoutGlobalScope('instancia')
            ->where('codigo', $codigo)
            ->exists();
    }

    /** Actualiza el estado del pedido. */
    public function actualizarEstado(Pedido $pedido, string $estado): Pedido
    {
        $pedido->update(['estado' => $estado]);

        return $pedido;
    }

    /** Registra el pago del pedido. */
    public function registrarPago(Pedido $pedido): Pedido
    {
        $pedido->update([
            'pagado' => true,
            'pagado_en' => now(),
        ]);

        return $pedido;
    }

    /** Revierte el pago del pedido (operacion inversa de registrarPago). */
    public function revertirPago(Pedido $pedido): Pedido
    {
        $pedido->update([
            'pagado' => false,
            'pagado_en' => null,
        ]);

        return $pedido;
    }
}
