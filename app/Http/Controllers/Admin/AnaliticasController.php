<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnaliticasRequest;
use App\Http\Resources\AnaliticasResource;
use App\Services\AnaliticasService;
use Illuminate\Http\JsonResponse;

/**
 * Analiticas del panel admin (ventas, pedidos, horas pico, top productos).
 */
final class AnaliticasController extends Controller
{
    public function __construct(
        private readonly AnaliticasService $analiticas,
    ) {
    }

    /**
     * GET /api/admin/analiticas?mes=YYYY-MM&sucursal_id=N
     */
    public function index(AnaliticasRequest $request): JsonResponse
    {
        $mes = $request->validated('mes');
        $sucursalId = $request->validated('sucursal_id') ? (int) $request->validated('sucursal_id') : null;

        $datos = $this->analiticas->resumen($mes, $sucursalId);

        return (new AnaliticasResource($datos))->response();
    }
}
