<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Pedido;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Consultas agregadas para analiticas del panel admin.
 * Todas las consultas respetan el global scope de instancia (multi-tenant).
 */
final class AnaliticasRepository
{
    /**
     * Total de ventas (SUM total) de pedidos entregados en el mes.
     */
    public function ventasMes(Carbon $inicio, Carbon $fin, ?int $sucursalId): float
    {
        $query = Pedido::query()
            ->where('estado', 'entregado')
            ->whereBetween('created_at', [$inicio, $fin]);

        if ($sucursalId !== null) {
            $query->where('sucursal_id', $sucursalId);
        }

        return (float) $query->sum('total');
    }

    /**
     * Cantidad de pedidos entregados en el mes.
     */
    public function pedidosMes(Carbon $inicio, Carbon $fin, ?int $sucursalId): int
    {
        $query = Pedido::query()
            ->where('estado', 'entregado')
            ->whereBetween('created_at', [$inicio, $fin]);

        if ($sucursalId !== null) {
            $query->where('sucursal_id', $sucursalId);
        }

        return $query->count();
    }

    /**
     * Ventas por dia: SUM(total) agrupado por fecha, orden ascendente.
     *
     * @return array<int, array{fecha: string, total: float}>
     */
    public function ventasPorDia(Carbon $inicio, Carbon $fin, ?int $sucursalId): array
    {
        $query = Pedido::query()
            ->selectRaw('DATE(created_at) as fecha, SUM(total) as total')
            ->where('estado', 'entregado')
            ->whereBetween('created_at', [$inicio, $fin])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'));

        if ($sucursalId !== null) {
            $query->where('sucursal_id', $sucursalId);
        }

        return $query->get()
            ->map(fn ($row) => [
                'fecha' => $row->fecha,
                'total' => (float) $row->total,
            ])
            ->toArray();
    }

    /**
     * Horas pico: COUNT(*) agrupado por hora (0-23), solo horas con datos.
     *
     * @return array<int, array{hora: int, cantidad: int}>
     */
    public function horasPico(Carbon $inicio, Carbon $fin, ?int $sucursalId): array
    {
        $query = Pedido::query()
            ->selectRaw('EXTRACT(HOUR FROM created_at)::int as hora, COUNT(*) as cantidad')
            ->where('estado', 'entregado')
            ->whereBetween('created_at', [$inicio, $fin])
            ->groupBy(DB::raw('EXTRACT(HOUR FROM created_at)'))
            ->orderBy(DB::raw('EXTRACT(HOUR FROM created_at)'));

        if ($sucursalId !== null) {
            $query->where('sucursal_id', $sucursalId);
        }

        return $query->get()
            ->map(fn ($row) => [
                'hora' => (int) $row->hora,
                'cantidad' => (int) $row->cantidad,
            ])
            ->toArray();
    }

    /**
     * Top productos mas vendidos: join detalles_pedido -> productos, SUM(cantidad).
     * Incluye ingresos calculados desde detalle_pedido.subtotal (precio base/tamano * cantidad,
     * SIN extras — los extras son ingresos de los propios extras, no del producto).
     *
     * @return array<int, array{nombre: string, unidades: int, ingresos: float, imagen_url: string|null}>
     */
    public function topProductos(Carbon $inicio, Carbon $fin, ?int $sucursalId, int $limite = 10): array
    {
        $query = DB::table('detalle_pedido')
            ->join('pedidos', 'detalle_pedido.pedido_id', '=', 'pedidos.id')
            ->join('productos', 'detalle_pedido.producto_id', '=', 'productos.id')
            ->selectRaw('productos.nombre, productos.imagen_url, SUM(detalle_pedido.cantidad)::int as unidades, SUM(detalle_pedido.subtotal)::numeric(12,2) as ingresos')
            ->where('pedidos.estado', 'entregado')
            ->whereBetween('pedidos.created_at', [$inicio, $fin]);

        // Filtro multi-tenant: aplicar manualmente porque usamos Query Builder
        $instanciaId = $this->instanciaActual();
        if ($instanciaId !== null) {
            $query->where('pedidos.instancia_id', $instanciaId);
        }

        if ($sucursalId !== null) {
            $query->where('pedidos.sucursal_id', $sucursalId);
        }

        return $query
            ->groupBy('productos.id', 'productos.nombre', 'productos.imagen_url')
            ->orderByDesc('unidades')
            ->limit($limite)
            ->get()
            ->map(fn ($row) => [
                'nombre' => $row->nombre,
                'unidades' => (int) $row->unidades,
                'ingresos' => (float) $row->ingresos,
                'imagen_url' => $row->imagen_url,
            ])
            ->toArray();
    }

    /**
     * Distribucion por modalidad: COUNT(*) agrupado por modalidad.
     *
     * @return array<int, array{modalidad: string, cantidad: int}>
     */
    public function modalidad(Carbon $inicio, Carbon $fin, ?int $sucursalId): array
    {
        $query = Pedido::query()
            ->selectRaw('modalidad, COUNT(*) as cantidad')
            ->where('estado', 'entregado')
            ->whereBetween('created_at', [$inicio, $fin])
            ->groupBy('modalidad');

        if ($sucursalId !== null) {
            $query->where('sucursal_id', $sucursalId);
        }

        return $query->get()
            ->map(fn ($row) => [
                'modalidad' => $row->modalidad,
                'cantidad' => (int) $row->cantidad,
            ])
            ->toArray();
    }

    /**
     * Ventas por categoria: SUM(subtotal) agrupado por categoria del producto.
     * Orden descendente por total.
     *
     * @return array<int, array{categoria: string, total: float}>
     */
    public function ventasPorCategoria(Carbon $inicio, Carbon $fin, ?int $sucursalId): array
    {
        $query = DB::table('detalle_pedido')
            ->join('pedidos', 'detalle_pedido.pedido_id', '=', 'pedidos.id')
            ->join('productos', 'detalle_pedido.producto_id', '=', 'productos.id')
            ->leftJoin('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->selectRaw("COALESCE(categorias.nombre, 'Sin categoria') as categoria, SUM(detalle_pedido.subtotal) as total")
            ->where('pedidos.estado', 'entregado')
            ->whereBetween('pedidos.created_at', [$inicio, $fin]);

        // Filtro multi-tenant: aplicar manualmente porque usamos Query Builder
        $instanciaId = $this->instanciaActual();
        if ($instanciaId !== null) {
            $query->where('pedidos.instancia_id', $instanciaId);
        }

        if ($sucursalId !== null) {
            $query->where('pedidos.sucursal_id', $sucursalId);
        }

        return $query
            ->groupBy('categorias.id', 'categorias.nombre')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'categoria' => $row->categoria,
                'total' => (float) $row->total,
            ])
            ->toArray();
    }

    /**
     * Obtiene instancia_id del usuario autenticado (para queries con Query Builder).
     */
    private function instanciaActual(): ?int
    {
        $actor = auth()->user();

        if ($actor !== null && isset($actor->instancia_id) && $actor->instancia_id !== null) {
            return (int) $actor->instancia_id;
        }

        return null;
    }
}
