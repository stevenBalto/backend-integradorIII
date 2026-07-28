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

    /** GET /api/admin/dashboard?dias=7|14|30 */
    public function index(Request $request): JsonResponse
    {
        $dias = (int) $request->integer('dias', 7);

        return response()->json(['data' => $this->dashboard->resumen($dias)]);
    }
}
