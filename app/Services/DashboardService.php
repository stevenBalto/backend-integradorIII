<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Pedido;
use Illuminate\Support\Facades\Cache;
use App\Repositories\DashboardRepository;
use Illuminate\Support\Carbon;

/**
 * Agrega los datos del dashboard admin (KPIs del dia, ventas de la semana,
 * pedidos nuevos y ultimos pedidos). Solo lectura, sin DTOs.
 */
final class DashboardService
{
    private const DIAS_SEMANA = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];

    public function __construct(
        private readonly DashboardRepository $repositorio,
    ) {
    }

    /** @param int $dias Ventana del gráfico de ventas (7, 14 o 30). */
    public function resumen(int $dias = 7): array
    {
        $dias = in_array($dias, [7, 14, 30], true) ? $dias : 7;

        $cacheKey = "dashboard:resumen:{$dias}";

        return Cache::remember($cacheKey, 15, function () use ($dias) {
            $hoy = $this->rango(Carbon::today());
            $ayer = $this->rango(Carbon::yesterday());

            $pedidosHoy = $this->repositorio->pedidosEntre($hoy[0], $hoy[1]);
            $pedidosAyer = $this->repositorio->pedidosEntre($ayer[0], $ayer[1]);

            $pedidosHoyValidos = $pedidosHoy->reject(fn (Pedido $p) => $p->estado === 'cancelado');
            $pedidosAyerValidos = $pedidosAyer->reject(fn (Pedido $p) => $p->estado === 'cancelado');

            $ventasHoy = (float) $pedidosHoyValidos->sum('total');
            $ventasAyer = (float) $pedidosAyerValidos->sum('total');

            return [
                'pedidos_hoy' => $pedidosHoy->count(),
                'pedidos_hoy_variacion' => $this->variacion($pedidosHoy->count(), $pedidosAyer->count()),
                'ventas_hoy' => $ventasHoy,
                'ventas_hoy_variacion' => $this->variacion($ventasHoy, $ventasAyer),
                'ticket_promedio' => $pedidosHoyValidos->count() > 0
                    ? round($ventasHoy / $pedidosHoyValidos->count(), 2)
                    : 0.0,
                'pedidos_activos' => $this->repositorio->contarActivos(),
                'ventas_semana' => $this->ventasSemana($dias),
                'pedidos_nuevos' => $this->repositorio->ultimosPendientes(5)->map(fn (Pedido $p) => [
                    'id' => $p->id,
                    'codigo' => $p->codigo,
                    'cliente' => $p->nombre_cliente,
                    'modalidad' => $p->modalidad,
                    'total' => (float) $p->total,
                    'created_at' => $p->created_at?->toIso8601String(),
                ])->values(),
                'ultimos_pedidos' => $this->repositorio->ultimos(8)->map(fn (Pedido $p) => [
                    'id' => $p->id,
                    'codigo' => $p->codigo,
                    'cliente' => $p->nombre_cliente ?? $p->cliente?->nombre,
                    'modalidad' => $p->modalidad,
                    'cantidad' => (int) ($p->detalles_sum_cantidad ?? 0),
                    'total' => (float) $p->total,
                    'estado' => $p->estado,
                    'created_at' => $p->created_at?->toIso8601String(),
                ])->values(),
            ];
        });
    }

    /** @return array{0: Carbon, 1: Carbon} Inicio y fin del dia (00:00:00 - 23:59:59). */
    private function rango(Carbon $dia): array
    {
        return [$dia->copy()->startOfDay(), $dia->copy()->endOfDay()];
    }

    private function variacion(float $actual, float $anterior): ?float
    {
        if ($anterior <= 0.0) {
            return null;
        }

        return round((($actual - $anterior) / $anterior) * 100, 1);
    }

    /**
     * Total vendido (sin cancelados) por cada uno de los ultimos N dias, terminando hoy.
     * Para ventanas > 7 dias la etiqueta usa d/m (el nombre del dia se repetiria).
     */
    private function ventasSemana(int $dias): array
    {
        $inicio = Carbon::today()->subDays($dias - 1)->startOfDay();
        $fin = Carbon::today()->endOfDay();

        $pedidos = $this->repositorio->pedidosEntre($inicio, $fin)
            ->reject(fn (Pedido $p) => $p->estado === 'cancelado');

        $filas = [];
        for ($i = 0; $i < $dias; $i++) {
            $fecha = $inicio->copy()->addDays($i);
            $totalDia = $pedidos
                ->filter(fn (Pedido $p) => $p->created_at->isSameDay($fecha))
                ->sum('total');

            $filas[] = [
                'dia' => $dias > 7
                    ? $fecha->format('d/m')
                    : self::DIAS_SEMANA[(int) $fecha->dayOfWeek],
                'fecha' => $fecha->toDateString(),
                'total' => (float) $totalDia,
            ];
        }

        return $filas;
    }
}
