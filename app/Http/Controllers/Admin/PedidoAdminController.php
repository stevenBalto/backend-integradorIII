<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTOs\Pedido\CrearPedidoDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pedido\CambiarEstadoPedidoRequest;
use App\Http\Requests\Pedido\RevertirEstadoPedidoRequest;
use App\Http\Requests\Pedido\StorePedidoInvitadoRequest;
use App\Http\Resources\PedidoAdminResource;
use App\Models\User;
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

        $porPagina = $request->query('por_pagina') !== null ? (int) $request->query('por_pagina') : null;
        $pagina = (int) $request->query('pagina', 1);

        $pedidos = $this->pedidos->listarAdmin(array_filter($filtros), $porPagina, $pagina);

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

    /**
     * POST /api/admin/pedidos/mostrador — el staff arma el pedido de un cliente que
     * pidio en el mostrador (no uso el carrito de la app) y mostro un cupon por QR.
     * Mismo patron que storeInvitado (usuario centinela "Invitado" como cliente_id,
     * sin acumular Roosters), pero autenticado como staff: la instancia del pedido
     * se asigna sola desde el usuario admin logueado (PerteneceAInstancia), no hace
     * falta suplantar al centinela para eso.
     */
    public function storeMostrador(StorePedidoInvitadoRequest $request): JsonResponse
    {
        $centinela = User::withoutGlobalScope('instancia')
            ->where('email', User::EMAIL_INVITADO)
            ->first();

        if ($centinela === null) {
            return response()->json([
                'message' => 'Los pedidos de mostrador no están disponibles por el momento.',
            ], 503);
        }

        $pedido = $this->pedidos->crear(
            $centinela->id,
            CrearPedidoDTO::fromArray($request->validated()),
            acumulaPuntos: false,
        );

        return (new PedidoAdminResource($pedido))
            ->response()
            ->setStatusCode(201);
    }
}
