<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Resumen del dashboard admin (KPIs, ventas de la semana, pedidos nuevos/ultimos).
 */
final class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {
    }

    /**
     * GET /api/admin/dashboard?dias=7|14|30&desde=YYYY-MM-DD&hasta=YYYY-MM-DD
     * `dias` controla SOLO la ventana del gráfico de ventas. `desde`/`hasta` controlan
     * SOLO el rango de "Últimos pedidos" (independientes entre sí). Sin desde/hasta =
     * pedidos de hoy.
     */
    public function index(Request $request): JsonResponse
    {
        $dias = (int) $request->integer('dias', 7);
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        return response()->json([
            'data' => $this->dashboard->resumen(
                $dias,
                is_string($desde) ? $desde : null,
                is_string($hasta) ? $hasta : null,
            ),
        ]);
    }
}
