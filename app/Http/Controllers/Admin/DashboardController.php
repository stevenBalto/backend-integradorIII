<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

/**
 * Resumen del dashboard admin (KPIs, ventas de la semana, pedidos nuevos/ultimos).
 */
final class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {
    }

    /** GET /api/admin/dashboard */
    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->resumen()]);
    }
}
