<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea el uso del sistema mientras el usuario tenga una contraseña temporal /
 * de cambio obligatorio / vencida. Devuelve 423 (Locked) con un flag para que el
 * frontend lo mande a la pantalla de cambio de contraseña.
 * Uso en rutas: ->middleware('password.valida')
 */
class EnsurePasswordNoTemporal
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->debeCambiarPassword()) {
            return response()->json([
                'message' => 'Debés cambiar tu contraseña antes de continuar.',
                'must_change_password' => true,
            ], 423);
        }

        return $next($request);
    }
}
