<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe rutas a los roles indicados, reutilizando los helpers es*() de User.
 * Uso en rutas: ->middleware('role:super_admin,admin_sede')
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $permitido = collect($roles)->contains(fn (string $rol) => match ($rol) {
            'super_admin' => $user->esSuperAdmin(),
            'admin_sede' => $user->esAdminSede(),
            'cliente' => $user->esCliente(),
            default => false,
        });

        if (! $permitido) {
            return response()->json(['message' => 'No tenés permiso para realizar esta acción.'], 403);
        }

        return $next($request);
    }
}
