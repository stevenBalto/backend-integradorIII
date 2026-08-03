<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ClienteResumenResource;
use App\Http\Resources\PedidoResumenResource;
use App\Services\ClienteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function index(Request $request): JsonResponse
    {
        $porPagina = $request->query('por_pagina') !== null ? (int) $request->query('por_pagina') : null;
        $pagina = (int) $request->query('pagina', 1);

        return ClienteResumenResource::collection($this->clientes->listarConEstadisticas($porPagina, $pagina))
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
