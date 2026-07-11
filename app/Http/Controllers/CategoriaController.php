<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\CategoriaResource;
use App\Repositories\CategoriaRepository;
use Illuminate\Http\JsonResponse;

/**
 * Endpoint publico de categorias (para poblar filtros de catalogo y formularios).
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
}
