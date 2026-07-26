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
     * Genera el resumen de analiticas para un mes.
     *
     * @param string|null $mes Formato YYYY-MM, default mes actual.
     * @param int|null $sucursalId Filtrar por sucursal (null = todas).
     */
    public function resumen(?string $mes, ?int $sucursalId): array
    {
        $mesFormato = $mes ?? Carbon::now()->format('Y-m');
        $instanciaId = $this->instanciaActual();
        $sucursalKey = $sucursalId ?? 'all';

        $cacheKey = "analiticas:{$instanciaId}:{$sucursalKey}:{$mesFormato}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($mesFormato, $sucursalId) {
            return $this->calcularResumen($mesFormato, $sucursalId);
        });
    }

    /**
     * Calcula todas las metricas del resumen (sin cache).
     */
    private function calcularResumen(string $mes, ?int $sucursalId): array
    {
        [$inicio, $fin] = $this->rangoMes($mes);

        $ventasMes = $this->repositorio->ventasMes($inicio, $fin, $sucursalId);
        $pedidosMes = $this->repositorio->pedidosMes($inicio, $fin, $sucursalId);
        $ticketPromedio = $pedidosMes > 0 ? round($ventasMes / $pedidosMes, 2) : 0.0;

        $modalidadRaw = $this->repositorio->modalidad($inicio, $fin, $sucursalId);
        $modalidad = $this->calcularPorcentajesModalidad($modalidadRaw, $pedidosMes);

        // Comparacion vs mes anterior
        $comparacionMesAnterior = $this->calcularComparacionMesAnterior($mes, $sucursalId, $ventasMes, $pedidosMes);

        return [
            'ventas_mes' => $ventasMes,
            'pedidos_mes' => $pedidosMes,
            'ticket_promedio' => $ticketPromedio,
            'ventas_por_dia' => $this->repositorio->ventasPorDia($inicio, $fin, $sucursalId),
            'horas_pico' => $this->repositorio->horasPico($inicio, $fin, $sucursalId),
            'top_productos' => $this->repositorio->topProductos($inicio, $fin, $sucursalId, 10),
            'modalidad' => $modalidad,
            'comparacion_mes_anterior' => $comparacionMesAnterior,
            'ventas_por_categoria' => $this->repositorio->ventasPorCategoria($inicio, $fin, $sucursalId),
        ];
    }

    /**
     * Calcula la variacion porcentual vs el mes calendario anterior.
     *
     * @return array{ventas_pct: float|null, pedidos_pct: float|null, ventas_mes_anterior: float, pedidos_mes_anterior: int}
     */
    private function calcularComparacionMesAnterior(string $mes, ?int $sucursalId, float $ventasActual, int $pedidosActual): array
    {
        $mesAnterior = Carbon::createFromFormat('Y-m', $mes)->subMonth()->format('Y-m');
        [$inicioAnt, $finAnt] = $this->rangoMes($mesAnterior);

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
