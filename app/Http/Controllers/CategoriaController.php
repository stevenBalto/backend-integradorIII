<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Categoria\CrearCategoriaDTO;
use App\Http\Requests\Categoria\StoreCategoriaRequest;
use App\Http\Resources\CategoriaResource;
use App\Repositories\CategoriaRepository;
use Illuminate\Http\JsonResponse;

/**
 * Categorias: lectura publica (poblar filtros/formularios) y alta desde el panel admin.
 */
final class CategoriaController extends Controller
{
    public function __construct(private readonly CategoriaRepository $categorias)
    {
    }

    /** GET /api/categorias */
    public function index(): JsonResponse
    {
        return CategoriaResource::collection($this->categorias->listarActivas())
            ->response();
    }

    /** POST /api/admin/categorias */
    public function store(StoreCategoriaRequest $request): JsonResponse
    {
        $categoria = $this->categorias->crear(
            CrearCategoriaDTO::fromArray($request->validated())->toArray(),
        );

        return (new CategoriaResource($categoria))->response()->setStatusCode(201);
    }
}
