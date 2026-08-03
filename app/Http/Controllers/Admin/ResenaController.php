<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ResenaAdminResource;
use App\Services\ResenaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestion de reseñas del panel admin: listar/filtrar, ocultar/mostrar,
 * eliminar y stats por producto.
 */
final class ResenaController extends Controller
{
    public function __construct(
        private readonly ResenaService $resenas,
    ) {
    }

    /** GET /api/admin/resenas — listado con filtros (producto, estrellas, fecha, cliente, estado). */
    public function index(Request $request): JsonResponse
    {
        $filtros = array_filter([
            'producto_id' => $request->query('producto_id'),
            'calificacion' => $request->query('calificacion'),
            'estado' => $request->query('estado'),
            'desde' => $request->query('desde'),
            'hasta' => $request->query('hasta'),
            'q' => $request->query('q'),
        ], fn ($v) => $v !== null && $v !== '');

        $porPagina = $request->query('por_pagina') !== null ? (int) $request->query('por_pagina') : null;
        $pagina = (int) $request->query('pagina', 1);

        return ResenaAdminResource::collection($this->resenas->listarAdmin($filtros, $porPagina, $pagina))->response();
    }

    /** GET /api/admin/resenas/stats — promedio + total por producto. */
    public function stats(): JsonResponse
    {
        $stats = $this->resenas->statsPorProducto()->map(fn ($fila) => [
            'producto_id' => (int) $fila->producto_id,
            'producto' => $fila->producto?->nombre,
            'total' => (int) $fila->total,
            'promedio' => (float) $fila->promedio,
        ]);

        return response()->json(['data' => $stats]);
    }

    /** POST /api/admin/resenas/{id}/ocultar */
    public function ocultar(int $id): JsonResponse
    {
        return (new ResenaAdminResource($this->resenas->ocultar($id)))->response();
    }

    /** POST /api/admin/resenas/{id}/mostrar */
    public function mostrar(int $id): JsonResponse
    {
        return (new ResenaAdminResource($this->resenas->mostrar($id)))->response();
    }

    /** DELETE /api/admin/resenas/{id} — soft delete. */
    public function destroy(int $id): JsonResponse
    {
        $this->resenas->eliminar($id);

        return response()->json(['message' => 'Reseña eliminada.']);
    }
}
