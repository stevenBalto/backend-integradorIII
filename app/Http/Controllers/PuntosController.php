<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\PuntosResource;
use App\Repositories\PuntosMovimientoRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Roosters (puntos de fidelidad) del cliente autenticado.
 */
final class PuntosController extends Controller
{
    public function __construct(
        private readonly PuntosMovimientoRepository $puntos,
    ) {
    }

    /**
     * GET /api/puntos/mios — saldo actual + acumulado + canjeado + movimientos.
     * El total_canjeado se devuelve en positivo (los movimientos de canje son negativos).
     */
    public function mios(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $movimientos = $this->puntos->listarDeUsuario($usuario->id);

        $ganado = (int) $movimientos->where('puntos', '>', 0)->sum('puntos');
        $canjeado = (int) abs($movimientos->where('puntos', '<', 0)->sum('puntos'));

        return response()->json([
            'data' => [
                'balance' => (int) $usuario->puntos_balance,
                'total_ganado' => $ganado,
                'total_canjeado' => $canjeado,
                'movimientos' => PuntosResource::collection($movimientos),
            ],
        ]);
    }
}
