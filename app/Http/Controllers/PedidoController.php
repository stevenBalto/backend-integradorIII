<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Pedido\CrearPedidoDTO;
use App\Http\Requests\Pedido\StorePedidoInvitadoRequest;
use App\Http\Requests\Pedido\StorePedidoRequest;
use App\Http\Resources\PedidoPublicoResource;
use App\Http\Resources\PedidoResource;
use App\Models\User;
use App\Services\PedidoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    /**
     * POST /api/pedidos/invitado — crear pedido SIN sesion (visitante).
     *
     * El pedido se guarda a nombre del usuario centinela "Invitado" (para no volver
     * cliente_id nullable) con el nombre real en nombre_cliente. Se autentica el
     * centinela solo durante la peticion para que el trait multi-tenant asigne la
     * instancia; NO acumula ni canjea Roosters.
     */
    public function storeInvitado(StorePedidoInvitadoRequest $request): JsonResponse
    {
        $centinela = User::withoutGlobalScope('instancia')
            ->where('email', User::EMAIL_INVITADO)
            ->first();

        if ($centinela === null) {
            return response()->json([
                'message' => 'Los pedidos como invitado no están disponibles por el momento.',
            ], 503);
        }

        // Fijar al centinela como usuario actual solo para esta peticion (sin sesion):
        // el trait PerteneceAInstancia lee Auth::user()->instancia_id para asignar la
        // instancia al pedido y sus detalles.
        Auth::setUser($centinela);

        $pedido = $this->pedidos->crear(
            $centinela->id,
            CrearPedidoDTO::fromArray($request->validated()),
            acumulaPuntos: false,
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
