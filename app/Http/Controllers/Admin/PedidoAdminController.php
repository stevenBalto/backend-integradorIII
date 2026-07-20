<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pedido\CambiarEstadoPedidoRequest;
use App\Http\Requests\Pedido\RevertirEstadoPedidoRequest;
use App\Http\Resources\PedidoAdminResource;
use App\Services\PedidoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints de administracion de pedidos.
 */
final class PedidoAdminController extends Controller
{
    public function __construct(
        private readonly PedidoService $pedidos,
    ) {
    }

    /** GET /api/admin/pedidos — listado de pedidos con filtros. */
    public function index(Request $request): JsonResponse
    {
        $filtros = [
            'estado' => $request->query('estado'),
            'modalidad' => $request->query('modalidad'),
            'q' => $request->query('q'),
        ];

        $pedidos = $this->pedidos->listarAdmin(array_filter($filtros));

        return PedidoAdminResource::collection($pedidos)->response();
    }

    /** GET /api/admin/pedidos/{id} — ver detalle de un pedido. */
    public function show(int $id): JsonResponse
    {
        $pedido = $this->pedidos->buscarPorId($id);

        return (new PedidoAdminResource($pedido))->response();
    }

    /** POST /api/admin/pedidos/{id}/estado — cambiar estado del pedido. */
    public function cambiarEstado(CambiarEstadoPedidoRequest $request, int $id): JsonResponse
    {
        $datos = $request->validated();

        $pedido = $this->pedidos->cambiarEstado(
            $id,
            $datos['estado'],
            $datos['comentario'] ?? null,
            $request->user()->id
        );

        return (new PedidoAdminResource($pedido))->response();
    }

    /** POST /api/admin/pedidos/{id}/revertir — revierte el pedido a un estado anterior del historial. */
    public function revertir(RevertirEstadoPedidoRequest $request, int $id): JsonResponse
    {
        $datos = $request->validated();

        $pedido = $this->pedidos->revertirEstado(
            $id,
            $datos['estado'],
            $request->user()->id
        );

        return (new PedidoAdminResource($pedido))->response();
    }

    /** POST /api/admin/pedidos/{id}/pagar — registrar pago del pedido. */
    public function pagar(int $id): JsonResponse
    {
        $pedido = $this->pedidos->registrarPago($id);

        return (new PedidoAdminResource($pedido))->response();
    }
}
