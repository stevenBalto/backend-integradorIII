<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ClienteResumenResource;
use App\Http\Resources\PedidoResumenResource;
use App\Services\ClienteService;
use Illuminate\Http\JsonResponse;

/**
 * Endpoints del modulo Clientes (analitica de compra, solo lectura).
 * Solo admin_sede y super_admin pueden acceder.
 */
final class ClienteController extends Controller
{
    public function __construct(
        private readonly ClienteService $clientes,
    ) {
    }

    /**
     * GET /api/admin/clientes
     * Lista clientes con estadisticas agregadas de compra.
     */
    public function index(): JsonResponse
    {
        return ClienteResumenResource::collection($this->clientes->listarConEstadisticas())
            ->response();
    }

    /**
     * GET /api/admin/clientes/{id}/pedidos
     * Historial de pedidos de un cliente puntual.
     */
    public function pedidos(int $id): JsonResponse
    {
        return PedidoResumenResource::collection($this->clientes->listarPedidosDeCliente($id))
            ->response();
    }
}
