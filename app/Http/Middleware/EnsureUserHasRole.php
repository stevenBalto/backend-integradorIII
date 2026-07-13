<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
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

        // Un superadmin (u otra identidad que no sea User) no tiene roles de este
        // panel: se rechaza limpio. Aisla el panel admin de otras identidades.
        if (! $user instanceof User) {
            return response()->json(['message' => 'No tenés permiso para realizar esta acción.'], 403);
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
