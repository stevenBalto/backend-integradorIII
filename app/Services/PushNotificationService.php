<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PushTokenRepository;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Envio de notificaciones push a la app del cliente via Firebase Cloud Messaging.
 *
 * Usa la **HTTP v1 API** de FCM, que exige OAuth2 con una cuenta de servicio: la
 * API legacy (server key en un header) la dio de baja Google en 2024. El flujo
 * OAuth2 esta implementado a mano (JWT firmado con openssl) para no meter
 * `google/apiclient` — el proyecto no agrega dependencias de Composer.
 *
 * Ningun fallo de push tumba la operacion que lo disparo: todo se reporta por
 * Log::warning. Que a un cliente no le llegue el aviso al telefono no puede
 * hacer que el admin no pueda cambiar el estado de un pedido.
 */
final class PushNotificationService
{
    /**
     * Credenciales de la cuenta de servicio de Firebase (JSON con la private key RSA).
     * NO confundir con `google-services.json`, que es del lado de la app Android.
     * Va en storage/app/ y esta en .gitignore: es un secreto, no se sube al repo.
     */
    private const ARCHIVO_CREDENCIALES = 'firebase-service-account.json';

    private const CACHE_ACCESS_TOKEN = 'fcm_access_token';

    /**
     * Google entrega el access token con 3600s de vida. Se cachea por 3300 para
     * tener margen: si se cacheara los 3600 exactos, el ultimo push de la hora
     * saldria con un token recien vencido.
     */
    private const CACHE_SEGUNDOS = 3300;

    private const SCOPE_FCM = 'https://www.googleapis.com/auth/firebase.messaging';

    private const URL_TOKEN = 'https://oauth2.googleapis.com/token';

    /** Sin timeout, un FCM colgado dejaria esperando al admin que cambio el estado. */
    private const TIMEOUT_SEGUNDOS = 10;

    public function __construct(
        private readonly PushTokenRepository $tokens,
    ) {}

    /**
     * Manda un push a TODOS los dispositivos registrados del usuario.
     *
     * @param  array<string, mixed>  $datos  Payload para que la app sepa a donde navegar al tocar el aviso.
     */
    public function enviarAUsuario(int $userId, string $titulo, string $cuerpo, array $datos = []): void
    {
        $dispositivos = $this->tokens->tokensDeUsuario($userId);

        if ($dispositivos->isEmpty()) {
            return;
        }

        $credenciales = $this->credenciales();

        if ($credenciales === null) {
            return;
        }

        try {
            $accessToken = $this->accessToken($credenciales);
        } catch (Throwable $e) {
            Log::warning('No se pudo obtener el access token de FCM', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        foreach ($dispositivos as $dispositivo) {
            $this->enviarAToken(
                $accessToken,
                $credenciales['project_id'],
                $dispositivo->token,
                $titulo,
                $cuerpo,
                $datos,
            );
        }
    }

    /**
     * Lee el JSON de la cuenta de servicio. Devuelve null (con warning) si todavia
     * no esta puesto, para que el resto de la app siga funcionando sin push.
     *
     * @return array{client_email: string, private_key: string, project_id: string}|null
     */
    private function credenciales(): ?array
    {
        $disco = $this->discoCredenciales();

        if (! $disco->exists(self::ARCHIVO_CREDENCIALES)) {
            Log::warning('Push no enviado: falta el archivo de credenciales de Firebase', [
                'ruta_esperada' => storage_path('app/'.self::ARCHIVO_CREDENCIALES),
            ]);

            return null;
        }

        $json = json_decode((string) $disco->get(self::ARCHIVO_CREDENCIALES), true);

        if (! is_array($json)) {
            Log::warning('Push no enviado: el archivo de credenciales de Firebase no es un JSON valido');

            return null;
        }

        foreach (['client_email', 'private_key', 'project_id'] as $clave) {
            if (empty($json[$clave])) {
                Log::warning('Push no enviado: al archivo de credenciales de Firebase le falta una clave', [
                    'clave' => $clave,
                ]);

                return null;
            }
        }

        return [
            'client_email' => (string) $json['client_email'],
            'private_key' => (string) $json['private_key'],
            'project_id' => (string) $json['project_id'],
        ];
    }

    /**
     * Disco apuntado a storage/app/.
     *
     * Se construye a mano porque en Laravel 11+ la raiz del disco 'local' es
     * storage/app/PRIVATE, y las credenciales van directo en storage/app/.
     */
    private function discoCredenciales(): Filesystem
    {
        return Storage::build([
            'driver' => 'local',
            'root' => storage_path('app'),
        ]);
    }

    /**
     * Access token de Google, cacheado. Se canjea un JWT propio firmado con la
     * private key de la cuenta de servicio (grant "jwt-bearer").
     *
     * @param  array{client_email: string, private_key: string, project_id: string}  $credenciales
     *
     * @throws RuntimeException si el canje falla (a proposito: asi Cache::remember
     *                          NO guarda un valor invalido y el proximo push reintenta).
     */
    private function accessToken(array $credenciales): string
    {
        return Cache::remember(self::CACHE_ACCESS_TOKEN, self::CACHE_SEGUNDOS, function () use ($credenciales): string {
            $jwt = $this->firmarJwt($credenciales['client_email'], $credenciales['private_key']);

            $respuesta = Http::asForm()
                ->timeout(self::TIMEOUT_SEGUNDOS)
                ->post(self::URL_TOKEN, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

            if ($respuesta->failed()) {
                throw new RuntimeException('Google rechazo el JWT: '.$respuesta->status().' '.$respuesta->body());
            }

            $accessToken = $respuesta->json('access_token');

            if (! is_string($accessToken) || $accessToken === '') {
                throw new RuntimeException('La respuesta de Google no trajo access_token.');
            }

            return $accessToken;
        });
    }

    /**
     * Arma y firma el JWT que pide Google (RS256): header y claims en base64url,
     * unidos por un punto y firmados con la private key de la cuenta de servicio.
     */
    private function firmarJwt(string $clientEmail, string $privateKey): string
    {
        $ahora = time();

        $header = ['alg' => 'RS256', 'typ' => 'JWT'];

        $claims = [
            'iss' => $clientEmail,
            'scope' => self::SCOPE_FCM,
            'aud' => self::URL_TOKEN,
            'iat' => $ahora,
            'exp' => $ahora + 3600,
        ];

        $porFirmar = $this->base64Url((string) json_encode($header))
            .'.'
            .$this->base64Url((string) json_encode($claims));

        $llave = openssl_pkey_get_private($privateKey);

        if ($llave === false) {
            throw new RuntimeException('La private_key de la cuenta de servicio no es valida.');
        }

        $firma = '';

        if (! openssl_sign($porFirmar, $firma, $llave, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('No se pudo firmar el JWT con la private_key.');
        }

        return $porFirmar.'.'.$this->base64Url($firma);
    }

    /** Base64 de URL (RFC 7515): +/ pasan a -_ y se quita el relleno '='. */
    private function base64Url(string $valor): string
    {
        return rtrim(strtr(base64_encode($valor), '+/', '-_'), '=');
    }

    /**
     * Envia el mensaje a UN dispositivo. Un token muerto no debe cortar el envio
     * a los demas aparatos del usuario, por eso el error se traga aca.
     *
     * @param  array<string, mixed>  $datos
     */
    private function enviarAToken(
        string $accessToken,
        string $projectId,
        string $token,
        string $titulo,
        string $cuerpo,
        array $datos,
    ): void {
        try {
            $respuesta = Http::withToken($accessToken)
                ->timeout(self::TIMEOUT_SEGUNDOS)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $titulo,
                            'body' => $cuerpo,
                        ],
                        'data' => $this->datosComoTexto($datos),
                    ],
                ]);

            if ($respuesta->failed()) {
                // El cuerpo trae el motivo real (ej. UNREGISTERED = el usuario
                // desinstalo la app y ese token ya no sirve).
                Log::warning('FCM rechazo el push', [
                    'status' => $respuesta->status(),
                    'respuesta' => $respuesta->body(),
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Fallo el envio del push', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * FCM v1 rechaza el mensaje si algun valor de `data` no es string (un int o
     * un bool devuelven 400), asi que se castea todo.
     *
     * @param  array<string, mixed>  $datos
     * @return array<string, string>
     */
    private function datosComoTexto(array $datos): array
    {
        return array_map(
            static fn (mixed $valor): string => is_scalar($valor)
                ? (string) $valor
                : (string) json_encode($valor),
            $datos,
        );
    }
}
