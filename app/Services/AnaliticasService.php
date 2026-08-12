<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AnaliticasRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Logica de negocio para analiticas del panel admin.
 * Coordina el repository y aplica cache de 30 minutos.
 */
final class AnaliticasService
{
    private const CACHE_TTL_MINUTES = 30;

    public function __construct(
        private readonly AnaliticasRepository $repositorio,
    ) {
    }

    /**
     * Genera el resumen de analiticas para un periodo (mes, semana, dia o rango).
     *
     * @param string|null $granularidad mes|semana|dia|rango, default mes.
     * @param string|null $mes Formato YYYY-MM, usado cuando granularidad=mes (default mes actual).
     * @param string|null $fecha Formato YYYY-MM-DD, fecha ancla usada cuando granularidad=semana|dia (default hoy).
     * @param int|null $sucursalId Filtrar por sucursal (null = todas).
     * @param string|null $desde Formato YYYY-MM-DD, inicio del rango (obligatorio si granularidad=rango).
     * @param string|null $hasta Formato YYYY-MM-DD, fin del rango (obligatorio si granularidad=rango).
     */
    public function resumen(?string $granularidad, ?string $mes, ?string $fecha, ?int $sucursalId, ?string $desde = null, ?string $hasta = null): array
    {
        [$granularidad, $periodo] = $this->resolverPeriodo($granularidad, $mes, $fecha, $desde, $hasta);
        $instanciaId = $this->instanciaActual();
        $sucursalKey = $sucursalId ?? 'all';

        $cacheKey = "analiticas:{$instanciaId}:{$sucursalKey}:{$granularidad}:{$periodo}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($granularidad, $periodo, $sucursalId) {
            $data = $this->calcularResumen($granularidad, $periodo, $sucursalId);
            // Metadatos de caché para que el frontend muestre el contador de próxima actualización.
            $data['generado_en'] = now()->toIso8601String();
            $data['expira_en'] = now()->addMinutes(self::CACHE_TTL_MINUTES)->toIso8601String();
            $data['ttl_minutos'] = self::CACHE_TTL_MINUTES;

            return $data;
        });
    }

    /**
     * Resuelve granularidad/periodo con sus defaults (mes actual / hoy).
     * Publico: lo reutiliza el export (Excel/PDF) para calcular la etiqueta y el rango de fechas.
     *
     * @return array{0: string, 1: string}
     */
    public function resolverPeriodo(?string $granularidad, ?string $mes, ?string $fecha, ?string $desde = null, ?string $hasta = null): array
    {
        $granularidad = $granularidad ?? 'mes';

        $periodo = match ($granularidad) {
            'rango' => "{$desde}_{$hasta}",
            'semana', 'dia' => $fecha ?? Carbon::now('America/Costa_Rica')->format('Y-m-d'),
            default => $mes ?? Carbon::now('America/Costa_Rica')->format('Y-m'),
        };

        return [$granularidad, $periodo];
    }

    /**
     * Expone el calculo de rango de fechas (usado por resumen() y por el export).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function rango(string $granularidad, string $periodo): array
    {
        return $this->rangoPeriodo($granularidad, $periodo);
    }

    /**
     * Calcula todas las metricas del resumen (sin cache).
     *
     * @param string $periodo YYYY-MM cuando granularidad=mes, YYYY-MM-DD cuando granularidad=semana|dia, YYYY-MM-DD_YYYY-MM-DD cuando granularidad=rango.
     */
    private function calcularResumen(string $granularidad, string $periodo, ?int $sucursalId): array
    {
        [$inicio, $fin] = $this->rangoPeriodo($granularidad, $periodo);

        $ventasMes = $this->repositorio->ventasMes($inicio, $fin, $sucursalId);
        $pedidosMes = $this->repositorio->pedidosMes($inicio, $fin, $sucursalId);
        $ticketPromedio = $pedidosMes > 0 ? round($ventasMes / $pedidosMes, 2) : 0.0;

        $modalidadRaw = $this->repositorio->modalidad($inicio, $fin, $sucursalId);
        $modalidad = $this->calcularPorcentajesModalidad($modalidadRaw, $pedidosMes);

        // Comparacion vs periodo anterior equivalente
        $comparacionPeriodoAnterior = $this->calcularComparacionPeriodoAnterior($granularidad, $inicio, $fin, $sucursalId, $ventasMes, $pedidosMes);

        return [
            'ventas_mes' => $ventasMes,
            'pedidos_mes' => $pedidosMes,
            'ticket_promedio' => $ticketPromedio,
            'ventas_por_dia' => $this->repositorio->ventasPorDia($inicio, $fin, $sucursalId),
            'horas_pico' => $this->repositorio->horasPico($inicio, $fin, $sucursalId),
            'top_productos' => $this->repositorio->topProductos($inicio, $fin, $sucursalId, 10),
            'modalidad' => $modalidad,
            'comparacion_periodo_anterior' => $comparacionPeriodoAnterior,
            'ventas_por_categoria' => $this->repositorio->ventasPorCategoria($inicio, $fin, $sucursalId),
        ];
    }

    /**
     * Calcula la variacion porcentual vs el periodo (mes/semana/dia/rango) anterior equivalente.
     *
     * @return array{ventas_pct: float|null, pedidos_pct: float|null, ventas_mes_anterior: float, pedidos_mes_anterior: int}
     */
    private function calcularComparacionPeriodoAnterior(string $granularidad, Carbon $inicioActual, Carbon $finActual, ?int $sucursalId, float $ventasActual, int $pedidosActual): array
    {
        [$inicioAnt, $finAnt] = match ($granularidad) {
            'rango' => $this->calcularRangoAnterior($inicioActual, $finActual),
            'semana' => [
                $inicioActual->copy()->subWeek()->startOfWeek(Carbon::MONDAY),
                $inicioActual->copy()->subWeek()->endOfWeek(Carbon::SUNDAY),
            ],
            'dia' => [$inicioActual->copy()->subDay(), $inicioActual->copy()->subDay()->endOfDay()],
            default => $this->rangoMes($inicioActual->copy()->subMonth()->format('Y-m')),
        };

        $ventasAnterior = $this->repositorio->ventasMes($inicioAnt, $finAnt, $sucursalId);
        $pedidosAnterior = $this->repositorio->pedidosMes($inicioAnt, $finAnt, $sucursalId);

        // Calcular porcentajes: null si denominador es 0
        $ventasPct = $ventasAnterior > 0
            ? round((($ventasActual - $ventasAnterior) / $ventasAnterior) * 100, 2)
            : null;

        $pedidosPct = $pedidosAnterior > 0
            ? round((($pedidosActual - $pedidosAnterior) / $pedidosAnterior) * 100, 2)
            : null;

        return [
            'ventas_pct' => $ventasPct,
            'pedidos_pct' => $pedidosPct,
            'ventas_mes_anterior' => $ventasAnterior,
            'pedidos_mes_anterior' => $pedidosAnterior,
        ];
    }

    /**
     * Calcula el rango anterior equivalente para granularidad 'rango'.
     * El periodo anterior tiene la misma cantidad de dias y termina el dia anterior al inicio actual.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function calcularRangoAnterior(Carbon $inicioActual, Carbon $finActual): array
    {
        $dias = $inicioActual->diffInDays($finActual);
        $finAnterior = $inicioActual->copy()->subDay()->endOfDay();
        $inicioAnterior = $finAnterior->copy()->subDays($dias)->startOfDay();

        return [$inicioAnterior, $finAnterior];
    }

    /**
     * Calcula el rango de fechas segun granularidad.
     *
     * @param string $periodo YYYY-MM cuando granularidad=mes, YYYY-MM-DD cuando granularidad=semana|dia, YYYY-MM-DD_YYYY-MM-DD cuando granularidad=rango.
     * @return array{0: Carbon, 1: Carbon}
     */
    private function rangoPeriodo(string $granularidad, string $periodo): array
    {
        return match ($granularidad) {
            'rango' => $this->rangoCustom($periodo),
            'semana' => $this->rangoSemana($periodo),
            'dia' => $this->rangoDia($periodo),
            default => $this->rangoMes($periodo),
        };
    }

    /**
     * Parsea el rango custom desde el periodo en formato "YYYY-MM-DD_YYYY-MM-DD".
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function rangoCustom(string $periodo): array
    {
        [$desde, $hasta] = explode('_', $periodo);
        $inicio = Carbon::createFromFormat('Y-m-d', $desde)->startOfDay();
        $fin = Carbon::createFromFormat('Y-m-d', $hasta)->endOfDay();

        return [$inicio, $fin];
    }

    /**
     * Calcula el rango de fechas para un mes (inicio y fin).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function rangoMes(string $mes): array
    {
        $inicio = Carbon::createFromFormat('Y-m', $mes)->startOfMonth();
        $fin = $inicio->copy()->endOfMonth();

        return [$inicio, $fin];
    }

    /**
     * Calcula el rango de fechas para la semana calendario (lunes-domingo) que contiene $fecha.
     * Usa Carbon::MONDAY explicitamente para evitar dependencia del locale del servidor.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function rangoSemana(string $fecha): array
    {
        $fechaCarbon = Carbon::createFromFormat('Y-m-d', $fecha);
        // Forzar lunes como inicio de semana (ISO 8601), independiente del locale del servidor
        $inicio = $fechaCarbon->startOfWeek(Carbon::MONDAY);
        $fin = $inicio->copy()->endOfWeek(Carbon::SUNDAY);

        return [$inicio, $fin];
    }

    /**
     * Calcula el rango de fechas para un dia especifico.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function rangoDia(string $fecha): array
    {
        $inicio = Carbon::createFromFormat('Y-m-d', $fecha)->startOfDay();
        $fin = $inicio->copy()->endOfDay();

        return [$inicio, $fin];
    }

    /**
     * Agrega porcentajes a la distribucion por modalidad.
     *
     * @param array<int, array{modalidad: string, cantidad: int}> $modalidadRaw
     * @return array<int, array{modalidad: string, cantidad: int, pct: int}>
     */
    private function calcularPorcentajesModalidad(array $modalidadRaw, int $totalPedidos): array
    {
        if ($totalPedidos === 0) {
            return [];
        }

        return array_map(function ($item) use ($totalPedidos) {
            return [
                'modalidad' => $item['modalidad'],
                'cantidad' => $item['cantidad'],
                'pct' => (int) round(($item['cantidad'] / $totalPedidos) * 100),
            ];
        }, $modalidadRaw);
    }

    /**
     * Obtiene instancia_id del usuario autenticado.
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
