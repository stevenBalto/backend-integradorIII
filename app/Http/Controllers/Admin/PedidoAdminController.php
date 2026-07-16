<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pedido\CambiarEstadoPedidoRequest;
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

        // Agregar hora estimada a cada pedido
        $pedidosConHora = $pedidos->map(function ($pedido) {
            $horaEstimada = $this->pedidos->estimarHoraLista($pedido);

            return (new PedidoAdminResource($pedido))
                ->additional(['hora_estimada' => $horaEstimada->toIso8601String()]);
        });

        return response()->json(['data' => $pedidosConHora]);
    }

    /** GET /api/admin/pedidos/{id} — ver detalle de un pedido. */
    public function show(int $id): JsonResponse
    {
        $pedido = $this->pedidos->buscarPorId($id);

        $horaEstimada = $this->pedidos->estimarHoraLista($pedido);

        return (new PedidoAdminResource($pedido))
            ->additional(['hora_estimada' => $horaEstimada->toIso8601String()])
            ->response();
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

        $horaEstimada = $this->pedidos->estimarHoraLista($pedido);

        return (new PedidoAdminResource($pedido))
            ->additional(['hora_estimada' => $horaEstimada->toIso8601String()])
            ->response();
    }

    /** POST /api/admin/pedidos/{id}/pagar — registrar pago del pedido. */
    public function pagar(int $id): JsonResponse
    {
        $pedido = $this->pedidos->registrarPago($id);

        $horaEstimada = $this->pedidos->estimarHoraLista($pedido);

        return (new PedidoAdminResource($pedido))
            ->additional(['hora_estimada' => $horaEstimada->toIso8601String()])
            ->response();
    }
}
