<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Configuracion\UpdateHomeConfigRequest;
use App\Services\ConfiguracionService;
use Illuminate\Http\JsonResponse;

/**
 * Ajustes de curacion del Home cliente (oferta destacada). Lectura publica,
 * escritura solo admin.
 */
final class ConfiguracionController extends Controller
{
    public function __construct(
        private readonly ConfiguracionService $configuraciones,
    ) {
    }

    /** GET /api/home-config — publico, lo consume el Home del cliente. */
    public function show(): JsonResponse
    {
        return response()->json(['data' => $this->configuraciones->obtenerHomeConfig()]);
    }

    /** PUT /api/admin/home-config */
    public function update(UpdateHomeConfigRequest $request): JsonResponse
    {
        $datos = $this->configuraciones->actualizarHomeConfig($request->validated()['oferta_hero_id'] ?? null);

        return response()->json(['data' => $datos]);
    }
}
