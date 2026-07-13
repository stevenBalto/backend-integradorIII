<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\SuperAdministrador;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garantiza el AISLAMIENTO del panel de superadmin: aunque `auth:sanctum`
 * autentique cualquier token (User o SuperAdministrador), aqui se exige que
 * quien pasa sea de verdad un SuperAdministrador. Un token de admin/cliente
 * normal recibe 403 y jamas alcanza el panel de superadministracion.
 * Uso en rutas: ->middleware(['auth:sanctum', 'superadmin'])
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $actor = $request->user();

        if (! $actor instanceof SuperAdministrador) {
            return response()->json(['message' => 'Acceso restringido a superadministradores.'], 403);
        }

        return $next($request);
    }
}
