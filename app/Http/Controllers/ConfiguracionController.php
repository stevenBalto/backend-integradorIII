<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Configuracion\GuardarAjustesRequest;
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

    /**
     * GET /api/restaurantes — publico. Lista los restaurantes (una por instancia
     * activa) con la "Informacion del negocio" que cada sucursal configuro.
     * Lo consume la pantalla Restaurantes de la app cliente.
     */
    public function restaurantes(): JsonResponse
    {
        return response()->json(['data' => $this->configuraciones->listarRestaurantes()]);
    }

    /** GET /api/admin/configuracion — ajustes generales de la instancia. */
    public function ajustes(): JsonResponse
    {
        return response()->json(['data' => $this->configuraciones->obtenerAjustes()]);
    }

    /** PUT /api/admin/configuracion — guarda los ajustes generales. */
    public function guardarAjustes(GuardarAjustesRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->configuraciones->guardarAjustes($request->validated())]);
    }
}
