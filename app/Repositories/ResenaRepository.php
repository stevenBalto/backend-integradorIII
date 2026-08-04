<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Resena;
use Illuminate\Database\Eloquent\Collection;

/**
 * Unica capa que consulta la tabla resenas via Eloquent.
 * El aislamiento por instancia lo garantiza el global scope del modelo.
 */
final class ResenaRepository
{
    public function crear(array $datos): Resena
    {
        return Resena::create($datos);
    }

    public function buscarPorId(int $id): ?Resena
    {
        return Resena::query()->find($id);
    }

    /** True si el pedido ya tiene una reseña general (no borrada). */
    public function existeGeneral(int $pedidoId): bool
    {
        return Resena::query()
            ->where('pedido_id', $pedidoId)
            ->where('tipo', 'general')
            ->exists();
    }

    /**
     * IDs de productos ya reseñados en ese pedido (para no duplicar).
     *
     * @return list<int>
     */
    public function productosResenados(int $pedidoId): array
    {
        return Resena::query()
            ->where('pedido_id', $pedidoId)
            ->where('tipo', 'producto')
            ->pluck('producto_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Devuelve un map pedido_id => Collection<int, int> (ids de productos reseñados)
     * para los pedidos indicados. Util para evitar N+1 en listados.
     *
     * @param array<int> $pedidoIds
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, int>>
     */
    public function productosResenadosPorPedidos(array $pedidoIds): \Illuminate\Support\Collection
    {
        if (empty($pedidoIds)) {
            return collect();
        }

        $filas = Resena::query()
            ->whereIn('pedido_id', $pedidoIds)
            ->where('tipo', 'producto')
            ->get(['pedido_id', 'producto_id']);

        return $filas->groupBy('pedido_id')
            ->map(fn ($grupo) => $grupo->pluck('producto_id')->map(fn ($id) => (int) $id)->unique()->values());
    }

    // ── Admin ──────────────────────────────────────────────────────────────

    /**
     * Listado admin con filtros: producto_id, calificacion, desde, hasta,
     * q (nombre/email del cliente), estado ('publicada'|'oculta').
     *
     * @param array<string, mixed> $filtros
     * @param int|null $porPagina Si viene, devuelve un LengthAwarePaginator en vez de la Collection completa.
     * @return Collection<int, Resena>|\Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listarAdmin(array $filtros, ?int $porPagina = null, int $pagina = 1)
    {
        $query = Resena::query()
            ->with(['user:id,nombre,email', 'producto:id,nombre,imagen_url'])
            ->when(isset($filtros['producto_id']), fn ($q) => $q->where('producto_id', $filtros['producto_id']))
            ->when(isset($filtros['calificacion']), fn ($q) => $q->where('calificacion', $filtros['calificacion']))
            ->when(isset($filtros['estado']), fn ($q) => $q->where('estado', $filtros['estado']))
            ->when(isset($filtros['desde']), fn ($q) => $q->whereDate('created_at', '>=', $filtros['desde']))
            ->when(isset($filtros['hasta']), fn ($q) => $q->whereDate('created_at', '<=', $filtros['hasta']))
            ->when(isset($filtros['q']), function ($q) use ($filtros) {
                $texto = '%' . $filtros['q'] . '%';
                $q->whereHas('user', function ($u) use ($texto) {
                    $u->where('nombre', 'ilike', $texto)->orWhere('email', 'ilike', $texto);
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($porPagina !== null) {
            return $query->paginate($porPagina, ['*'], 'pagina', $pagina);
        }

        return $query->get();
    }

    public function actualizar(Resena $resena, array $datos): Resena
    {
        $resena->update($datos);

        return $resena;
    }

    public function eliminar(Resena $resena): void
    {
        $resena->delete();
    }

    // ── Stats / publico ──────────────────────────────────────────────────────

    /**
     * Resumen de un producto: promedio, total y distribucion (1..5) de las
     * reseñas VISIBLES (publicadas, no borradas).
     *
     * @return array{promedio: float, total: int, distribucion: array<int, int>}
     */
    public function resumenProducto(int $productoId): array
    {
        $filas = Resena::query()
            ->visibles()
            ->where('tipo', 'producto')
            ->where('producto_id', $productoId)
            ->selectRaw('calificacion, count(*) as n')
            ->groupBy('calificacion')
            ->pluck('n', 'calificacion');

        return $this->armarResumen($filas->toArray());
    }

    /**
     * Reseñas visibles de un producto (opiniones de otros clientes).
     *
     * @return Collection<int, Resena>
     */
    public function visiblesDeProducto(int $productoId, int $limite = 20): Collection
    {
        return Resena::query()
            ->visibles()
            ->where('tipo', 'producto')
            ->where('producto_id', $productoId)
            ->with('user:id,nombre')
            ->orderByDesc('created_at')
            ->limit($limite)
            ->get();
    }

    /**
     * Stats agregadas por producto para el panel admin (promedio + total por
     * cada producto que tenga reseñas visibles).
     *
     * @return Collection<int, object>
     */
    public function statsPorProducto(): Collection
    {
        return Resena::query()
            ->visibles()
            ->where('tipo', 'producto')
            ->selectRaw('producto_id, count(*) as total, round(avg(calificacion)::numeric, 2) as promedio')
            ->groupBy('producto_id')
            ->with('producto:id,nombre')
            ->orderByDesc('promedio')
            ->get();
    }

    /**
     * @param array<int, int> $conteos calificacion => cantidad
     * @return array{promedio: float, total: int, distribucion: array<int, int>}
     */
    private function armarResumen(array $conteos): array
    {
        $distribucion = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        $total = 0;
        $suma = 0;

        foreach ($conteos as $estrella => $n) {
            $estrella = (int) $estrella;
            $n = (int) $n;
            $distribucion[$estrella] = $n;
            $total += $n;
            $suma += $estrella * $n;
        }

        return [
            'promedio' => $total > 0 ? round($suma / $total, 2) : 0.0,
            'total' => $total,
            'distribucion' => $distribucion,
        ];
    }
}
