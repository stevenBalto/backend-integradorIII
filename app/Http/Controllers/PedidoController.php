<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Pedido\CrearPedidoDTO;
use App\Http\Requests\Pedido\StorePedidoRequest;
use App\Http\Resources\PedidoPublicoResource;
use App\Http\Resources\PedidoResource;
use App\Services\PedidoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints de pedidos para clientes.
 */
final class PedidoController extends Controller
{
    public function __construct(
        private readonly PedidoService $pedidos,
    ) {
    }

    /** POST /api/pedidos — crear un nuevo pedido. */
    public function store(StorePedidoRequest $request): JsonResponse
    {
        $pedido = $this->pedidos->crear(
            $request->user()->id,
            CrearPedidoDTO::fromArray($request->validated())
        );

        return (new PedidoResource($pedido))
            ->response()
            ->setStatusCode(201);
    }

    /** GET /api/pedidos/mios — mis pedidos. */
    public function misPedidos(Request $request): JsonResponse
    {
        $pedidos = $this->pedidos->listarDeCliente($request->user()->id);

        return PedidoResource::collection($pedidos)->response();
    }

    /** GET /api/pedidos/mios/buscar?codigo=XXXX-XXXX — el cliente busca SU propio pedido por codigo. */
    public function misPedidosBuscar(Request $request): JsonResponse
    {
        $codigo = $request->query('codigo');

        if (empty($codigo)) {
            return response()->json([
                'message' => 'El código del pedido es obligatorio.',
            ], 422);
        }

        $pedido = $this->pedidos->buscarDeClientePorCodigo($request->user()->id, $codigo);

        return (new PedidoResource($pedido))->response();
    }

    /** GET /api/pedidos/mios/{id} — ver un pedido propio. */
    public function misPedidosShow(Request $request, int $id): JsonResponse
    {
        $pedido = $this->pedidos->buscarDeCliente($request->user()->id, $id);

        return (new PedidoResource($pedido))->response();
    }

    /** GET /api/pedidos/buscar?codigo=XXXX-XXXX — busqueda publica por codigo. */
    public function buscarPublico(Request $request): JsonResponse
    {
        $codigo = $request->query('codigo');

        if (empty($codigo)) {
            return response()->json([
                'message' => 'El código del pedido es obligatorio.',
            ], 422);
        }

        $pedido = $this->pedidos->buscarPorCodigo($codigo);

        if ($pedido === null) {
            return response()->json([
                'message' => 'No encontramos un pedido con ese código.',
            ], 404);
        }

        return (new PedidoPublicoResource($pedido))->response();
    }
}
