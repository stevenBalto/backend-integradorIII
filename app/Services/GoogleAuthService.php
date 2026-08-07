<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Inicio de sesion con Google (OAuth 2.0 "authorization code", server-side).
 *
 * Por que este flujo y no el boton de Google Identity Services: el redirect
 * funciona igual en el navegador de escritorio, en el del telefono y —el dia que
 * exista APK— abriendo el navegador del sistema y volviendo por deep link. GIS
 * depende de un script que no corre bien dentro de un WebView.
 *
 * No usa ningun paquete nuevo (regla del proyecto): el intercambio del code y la
 * lectura del ID token se hacen con Http y funciones nativas.
 *
 * El token de sesion NO viaja en la URL de vuelta. Se devuelve un codigo de un
 * solo uso (5 min) que el frontend canjea por POST; asi el token de Sanctum no
 * queda en el historial del navegador, en los logs del servidor ni en el
 * Referer.
 */
final class GoogleAuthService
{
    /** Cuanto vive el `state` que protege contra CSRF en el redirect. */
    private const TTL_STATE = 600;

    /** Cuanto vive el codigo de un solo uso que el front canjea por el token. */
    private const TTL_CODIGO = 300;

    public function __construct(
        private readonly UserRepository $usuarios,
        private readonly RoleRepository $roles,
    ) {
    }

    /**
     * URL de Google a la que hay que mandar al usuario.
     *
     * @param  string|null  $destino  Ruta del front a la que volver despues de entrar.
     */
    public function urlDeAutorizacion(?string $destino): string
    {
        $this->exigirConfiguracion();

        // El `state` ata esta respuesta a esta peticion: si vuelve uno que no
        // emitimos nosotros (o ya vencido), el callback lo rechaza.
        $state = Str::random(40);
        Cache::put($this->claveState($state), $destino ?? '', self::TTL_STATE);

        $parametros = [
            'client_id' => (string) config('services.google.client_id'),
            'redirect_uri' => (string) config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            // Cuenta ya elegida en el navegador: que igual pregunte cual usar.
            'prompt' => 'select_account',
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($parametros);
    }

    /**
     * Procesa la vuelta de Google: valida, resuelve el usuario y deja listo un
     * codigo de un solo uso.
     *
     * @return array{codigo: string, destino: string}
     */
    public function procesarCallback(string $code, string $state): array
    {
        $this->exigirConfiguracion();

        $claveState = $this->claveState($state);
        $destino = Cache::get($claveState);
        if ($destino === null) {
            throw new RuntimeException('La sesion con Google vencio o no es valida. Volve a intentar.');
        }
        // Un `state` sirve una sola vez.
        Cache::forget($claveState);

        $datos = $this->datosDeGoogle($code);
        $user = $this->resolverUsuario($datos);

        $codigo = Str::random(64);
        Cache::put($this->claveCodigo($codigo), $user->id, self::TTL_CODIGO);

        return ['codigo' => $codigo, 'destino' => is_string($destino) ? $destino : ''];
    }

    /**
     * Canjea el codigo de un solo uso por el usuario + token de Sanctum.
     *
     * @return array{user: User, token: string}
     */
    public function canjear(string $codigo): array
    {
        $clave = $this->claveCodigo($codigo);
        $userId = Cache::get($clave);
        if ($userId === null) {
            throw new RuntimeException('El codigo de acceso vencio o ya fue usado.');
        }
        // De un solo uso: se quema apenas se lee.
        Cache::forget($clave);

        $user = $this->usuarios->buscarPorId((int) $userId);
        if ($user === null || ! $user->activo) {
            throw new RuntimeException('La cuenta no esta disponible.');
        }

        $token = $user->createToken('auth')->plainTextToken;
        $user->load('role');

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Cambia el `code` por los tokens y devuelve los datos verificados del ID token.
     *
     * @return array{sub: string, email: string, nombre: string}
     */
    private function datosDeGoogle(string $code): array
    {
        $respuesta = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => (string) config('services.google.client_id'),
            'client_secret' => (string) config('services.google.client_secret'),
            'redirect_uri' => (string) config('services.google.redirect'),
            'grant_type' => 'authorization_code',
        ]);

        if ($respuesta->failed()) {
            throw new RuntimeException('Google rechazo la autenticacion.');
        }

        $idToken = $respuesta->json('id_token');
        if (! is_string($idToken) || $idToken === '') {
            throw new RuntimeException('Google no devolvio el ID token.');
        }

        return $this->leerIdToken($idToken);
    }

    /**
     * Lee y valida el ID token.
     *
     * No hace falta verificar la FIRMA: el token no vino del navegador sino de
     * una llamada nuestra, por TLS, directo al endpoint de Google, autenticada
     * con nuestro client_secret (es la excepcion que la propia documentacion de
     * Google contempla para el flujo server-side). Igual se validan las claims,
     * que es lo que evita aceptar un token emitido para OTRA aplicacion.
     *
     * @return array{sub: string, email: string, nombre: string}
     */
    private function leerIdToken(string $idToken): array
    {
        $partes = explode('.', $idToken);
        if (count($partes) !== 3) {
            throw new RuntimeException('El ID token de Google no tiene el formato esperado.');
        }

        $json = base64_decode(strtr($partes[1], '-_', '+/'), true);
        $claims = is_string($json) ? json_decode($json, true) : null;
        if (! is_array($claims)) {
            throw new RuntimeException('No se pudo leer el ID token de Google.');
        }

        // aud: el token fue emitido para NUESTRA app y no para otra.
        if (($claims['aud'] ?? null) !== (string) config('services.google.client_id')) {
            throw new RuntimeException('El ID token no corresponde a esta aplicacion.');
        }

        $emisor = $claims['iss'] ?? '';
        if (! in_array($emisor, ['accounts.google.com', 'https://accounts.google.com'], true)) {
            throw new RuntimeException('El ID token no fue emitido por Google.');
        }

        if ((int) ($claims['exp'] ?? 0) < time()) {
            throw new RuntimeException('El ID token de Google esta vencido.');
        }

        // Sin email verificado no se puede vincular por correo sin riesgo de que
        // alguien reclame la cuenta de otra persona.
        $verificado = $claims['email_verified'] ?? false;
        if ($verificado !== true && $verificado !== 'true') {
            throw new RuntimeException('La cuenta de Google no tiene el correo verificado.');
        }

        $email = $claims['email'] ?? null;
        $sub = $claims['sub'] ?? null;
        if (! is_string($email) || ! is_string($sub) || $email === '' || $sub === '') {
            throw new RuntimeException('Google no devolvio el correo de la cuenta.');
        }

        $nombre = $claims['name'] ?? null;

        return [
            'sub' => $sub,
            'email' => $email,
            'nombre' => is_string($nombre) && $nombre !== '' ? $nombre : Str::before($email, '@'),
        ];
    }

    /**
     * Busca al usuario por google_id, si no por email (y lo vincula), y si no
     * existe lo crea como cliente.
     *
     * @param  array{sub: string, email: string, nombre: string}  $datos
     */
    private function resolverUsuario(array $datos): User
    {
        $porGoogle = $this->usuarios->buscarPorGoogleId($datos['sub']);
        if ($porGoogle !== null) {
            $this->exigirCuentaUsable($porGoogle);

            return $porGoogle;
        }

        $porEmail = $this->usuarios->buscarPorEmail($datos['email']);
        if ($porEmail !== null) {
            $this->exigirCuentaUsable($porEmail);

            // Vinculacion: la cuenta ya existia con contrasena y ahora suma Google.
            // Es seguro porque el email viene verificado por Google (ver leerIdToken).
            return $this->usuarios->vincularGoogle($porEmail, $datos['sub']);
        }

        $rolClienteId = $this->roles->idPorNombre('cliente');
        if ($rolClienteId === null) {
            throw new RuntimeException('El rol "cliente" no existe. Ejecuta RolesSeeder.');
        }

        return $this->usuarios->crearClienteDeGoogle(
            $datos['email'],
            $datos['nombre'],
            $datos['sub'],
            $rolClienteId,
        );
    }

    /**
     * Google es una puerta para CLIENTES.
     *
     * Un admin que entrara por aca se saltearia la politica de contrasena
     * vencida y cambio obligatorio, que es justo lo que protege esas cuentas.
     */
    private function exigirCuentaUsable(User $user): void
    {
        if (! $user->activo) {
            throw new RuntimeException('La cuenta esta inactiva.');
        }

        $user->loadMissing('role');
        if ($user->role?->nombre !== 'cliente') {
            throw new RuntimeException(
                'Las cuentas administrativas entran con correo y contrasena, no con Google.'
            );
        }
    }

    private function exigirConfiguracion(): void
    {
        $faltan = collect(['client_id', 'client_secret', 'redirect'])
            ->filter(fn (string $clave): bool => blank(config("services.google.$clave")))
            ->isNotEmpty();

        if ($faltan) {
            throw new RuntimeException(
                'Falta configurar GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET / GOOGLE_REDIRECT_URI en el .env.'
            );
        }
    }

    private function claveState(string $state): string
    {
        return "google:state:$state";
    }

    private function claveCodigo(string $codigo): string
    {
        return "google:codigo:$codigo";
    }
}
