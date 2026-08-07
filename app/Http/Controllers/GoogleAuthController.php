<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Services\GoogleAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Inicio de sesion con Google.
 *
 * Recorrido completo:
 *   1. El front manda al navegador a  GET /api/auth/google/redirect
 *   2. Este redirige a Google; el usuario elige su cuenta
 *   3. Google vuelve a          GET /api/auth/google/callback?code&state
 *   4. El callback resuelve el usuario y redirige al front con un codigo de
 *      un solo uso (NO con el token de sesion, que no debe viajar en la URL)
 *   5. El front lo canjea en    POST /api/auth/google/exchange
 *      y recibe el mismo { data: usuario, token } que devuelve /api/login
 */
final class GoogleAuthController extends Controller
{
    public function __construct(private readonly GoogleAuthService $google)
    {
    }

    /** GET /api/auth/google/redirect — manda al usuario a elegir su cuenta. */
    public function redirect(Request $request): RedirectResponse
    {
        // Ruta del front a la que volver (ej. /tabs/home). Se valida como ruta
        // relativa para que nadie use este endpoint como redirector abierto
        // hacia un sitio externo.
        $destino = (string) $request->query('destino', '');
        if (! str_starts_with($destino, '/') || str_starts_with($destino, '//')) {
            $destino = '';
        }

        // El front manda su propio origen (cada dev levanta en el puerto que
        // quiere: 4200 con ng serve, 8100 con ionic serve, o la URL del tunel).
        // El service lo valida contra la lista blanca.
        $origen = (string) $request->query('origen', '');

        try {
            return redirect()->away($this->google->urlDeAutorizacion($destino, $origen));
        } catch (Throwable $e) {
            return $this->volverAlFrontConError($e->getMessage());
        }
    }

    /** GET /api/auth/google/callback — vuelta de Google. */
    public function callback(Request $request): RedirectResponse
    {
        // El usuario pudo cancelar en la pantalla de Google.
        if ($request->query('error') !== null) {
            return $this->volverAlFrontConError('Cancelaste el inicio de sesion con Google.');
        }

        $code = (string) $request->query('code', '');
        $state = (string) $request->query('state', '');
        if ($code === '' || $state === '') {
            return $this->volverAlFrontConError('Google no devolvio los datos esperados.');
        }

        try {
            ['codigo' => $codigo, 'destino' => $destino, 'origen' => $origen] =
                $this->google->procesarCallback($code, $state);
        } catch (Throwable $e) {
            Log::warning('Fallo el callback de Google', ['error' => $e->getMessage()]);

            return $this->volverAlFrontConError($e->getMessage());
        }

        // El codigo va en el FRAGMENTO (#) y no en la query: el fragmento no se
        // manda al servidor, no queda en los access logs ni se filtra por Referer.
        return redirect()->away(
            $this->urlDelFront($destino, $origen).'#google_codigo='.urlencode($codigo)
        );
    }

    /** POST /api/auth/google/exchange — canjea el codigo por el token de sesion. */
    public function exchange(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:128'],
        ]);

        try {
            $resultado = $this->google->canjear($datos['codigo']);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new UserResource($resultado['user']))
            ->additional(['token' => $resultado['token']])
            ->response();
    }

    /**
     * URL del front a la que volver. $origen viene validado por el service
     * (lista blanca); si no hay, se cae al del .env.
     */
    private function urlDelFront(string $destino = '', string $origen = ''): string
    {
        $base = rtrim($origen !== '' ? $origen : (string) config('services.google.frontend_url'), '/');

        return $destino !== '' ? $base.$destino : $base.'/login';
    }

    private function volverAlFrontConError(string $mensaje): RedirectResponse
    {
        return redirect()->away(
            $this->urlDelFront().'#google_error='.urlencode($mensaje)
        );
    }
}
