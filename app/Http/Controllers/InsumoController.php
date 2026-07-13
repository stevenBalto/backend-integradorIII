<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Insumo\ActualizarInsumoDTO;
use App\DTOs\Insumo\CrearInsumoDTO;
use App\Http\Requests\Insumo\StoreInsumoRequest;
use App\Http\Requests\Insumo\TomaFisicaRequest;
use App\Http\Requests\Insumo\UpdateInsumoRequest;
use App\Http\Resources\InsumoMovimientoResource;
use App\Http\Resources\InsumoResource;
use App\Services\InsumoService;
use Illuminate\Http\JsonResponse;

/**
 * Endpoints del inventario de insumos (materia prima). 100% administracion, sin acceso publico.
 */
final class InsumoController extends Controller
{
    public function __construct(
        private readonly InsumoService $insumos,
    ) {
    }

    /** GET /api/admin/insumos */
    public function index(): JsonResponse
    {
        return InsumoResource::collection($this->insumos->listarTodos())
            ->response();
    }

    /** GET /api/admin/insumos/{id} */
    public function show(int $id): InsumoResource
    {
        return new InsumoResource($this->insumos->buscarPorId($id));
    }

    /** POST /api/admin/insumos */
    public function store(StoreInsumoRequest $request): JsonResponse
    {
        $insumo = $this->insumos->crear(CrearInsumoDTO::fromArray($request->validated()));

        return (new InsumoResource($insumo))->response()->setStatusCode(201);
    }

    /**
     * PUT/PATCH /api/admin/insumos/{id}
     * Solo cambia nombre/unidad_medida/stock_minimo. cantidad_actual se ignora aqui a proposito.
     */
    public function update(UpdateInsumoRequest $request, int $id): InsumoResource
    {
        $insumo = $this->insumos->actualizar($id, ActualizarInsumoDTO::fromArray($request->validated()));

        return new InsumoResource($insumo);
    }

    /** DELETE /api/admin/insumos/{id} */
    public function destroy(int $id): JsonResponse
    {
        $this->insumos->eliminar($id);

        return response()->json(['message' => 'Insumo eliminado correctamente.']);
    }

    /**
     * POST /api/admin/insumos/{id}/toma-fisica
     * Fija la cantidad contada como nueva existencia y deja el ajuste auditado.
     */
    public function tomaFisica(TomaFisicaRequest $request, int $id): JsonResponse
    {
        $datos = $request->validated();

        $resultado = $this->insumos->registrarTomaFisica(
            $id,
            (float) $datos['cantidad_contada'],
            $datos['nota'] ?? null,
            $request->user()->id,
        );

        return response()->json([
            'data' => [
                'insumo' => new InsumoResource($resultado['insumo']),
                'movimiento' => new InsumoMovimientoResource($resultado['movimiento']),
            ],
        ]);
    }

    /** GET /api/admin/insumos/{id}/movimientos */
    public function movimientos(int $id): JsonResponse
    {
        return InsumoMovimientoResource::collection($this->insumos->listarMovimientos($id))
            ->response();
    }
}
