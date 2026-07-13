<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Cuenta\CambiarPasswordRequest;
use App\Services\CuentaService;
use Illuminate\Http\JsonResponse;

/**
 * Acciones de la cuenta del usuario autenticado (cambiar contraseña, etc.).
 */
final class CuentaController extends Controller
{
    public function __construct(private readonly CuentaService $service)
    {
    }

    /** POST /api/cuenta/cambiar-password — cambia la contraseña propia. */
    public function cambiarPassword(CambiarPasswordRequest $request): JsonResponse
    {
        $datos = $request->validated();
        $this->service->cambiarPassword(
            $request->user(),
            (string) $datos['password_actual'],
            (string) $datos['password'],
        );

        return response()->json(['message' => 'Contraseña actualizada. Ya podés usar el sistema.']);
    }
}
