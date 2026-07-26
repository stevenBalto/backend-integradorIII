<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Resena\EnviarResenaRequest;
use App\Http\Resources\ResenaResource;
use App\Services\ResenaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reseñas desde la vista del cliente + lectura publica en la ficha de producto.
 */
final class ResenaController extends Controller
{
    public function __construct(
        private readonly ResenaService $resenas,
    ) {
    }

    /** GET /api/resenas/pendientes — pedidos entregados sin reseñar (re-prompt Uber). */
    public function pendientes(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->resenas->pendientes($request->user()->id),
        ]);
    }

    /** POST /api/resenas/pedidos/{pedidoId} — enviar reseña general y/o de productos. */
    public function enviar(EnviarResenaRequest $request, int $pedidoId): JsonResponse
    {
        $datos = $request->validated();

        $creadas = $this->resenas->resenar(
            $request->user()->id,
            $pedidoId,
            $datos['general'] ?? null,
            $datos['productos'] ?? [],
        );

        return response()->json([
            'message' => 'Gracias por tu reseña.',
            'creadas' => $creadas->count(),
        ], 201);
    }

    /** POST /api/resenas/pedidos/{pedidoId}/descartar — no volver a pedir esa reseña. */
    public function descartar(Request $request, int $pedidoId): JsonResponse
    {
        $this->resenas->descartar($request->user()->id, $pedidoId);

        return response()->json(['message' => 'Listo.']);
    }

    /** GET /api/productos/{id}/resenas — resumen + opiniones publicas del producto. */
    public function producto(int $id): JsonResponse
    {
        return response()->json([
            'resumen' => $this->resenas->resumenProducto($id),
            'opiniones' => ResenaResource::collection($this->resenas->opinionesProducto($id)),
        ]);
    }
}
