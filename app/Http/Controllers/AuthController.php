<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Auth\CredencialesDTO;
use App\DTOs\Auth\RegistrarUsuarioDTO;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\PasswordExpiradaRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\SuperAdminResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Services\SuperAdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Endpoints de autenticacion (registro, login, logout, me, password expirada).
 */
final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly SuperAdminAuthService $superAuth,
    ) {
    }

    /** POST /api/register — crea un cliente y devuelve usuario + token. */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->auth->registrar(RegistrarUsuarioDTO::fromArray($request->validated()));

        return (new UserResource($result['user']))
            ->additional(['token' => $result['token']])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * POST /api/login — login UNIFICADO (una sola puerta).
     *
     * Primero intenta como superadmin (tabla aparte); si no, como usuario normal.
     * Devuelve `tipo` para que el frontend enrute al panel correcto.
     *
     * ITEM 19: Si el usuario esta activo y credenciales correctas pero la password
     * esta vencida (password_expira_en en el pasado o cambio_password_obligatorio),
     * NO emite token normal; responde con debe_cambiar_password=true y el motivo.
     * El frontend debe redirigir a la pantalla de cambio de password expirada.
     */
    /** Cuantos intentos FALLIDOS se toleran antes de bloquear la cuenta un rato. */
    private const INTENTOS_FALLIDOS = 5;

    /** Cuanto dura el bloqueo tras agotar los intentos (segundos). */
    private const BLOQUEO_SEGUNDOS = 60;

    public function login(LoginRequest $request): JsonResponse
    {
        $creds = CredencialesDTO::fromArray($request->validated());
        $clave = $this->claveDeIntentos($creds->email, $request->ip());

        // El middleware `throttle:login` ya frena el volumen bruto de peticiones.
        // Este contador es mas fino: cuenta SOLO los intentos fallidos y se borra
        // al entrar bien, asi el usuario que se equivoca una vez y despues acierta
        // no arrastra penalizacion.
        $this->exigirIntentosDisponibles($clave);

        try {
            // 1. ¿Es un superadmin? (identidad aislada, misma puerta de login)
            $sa = $this->superAuth->intentarLogin($creds->email, $creds->password);
            if ($sa !== null) {
                RateLimiter::clear($clave);

                return (new SuperAdminResource($sa['superadmin']))
                    ->additional(['token' => $sa['token'], 'tipo' => 'superadmin'])
                    ->response()
                    ->setStatusCode(200);
            }

            // 2. Usuario normal (admin de sede / cliente) — flujo existente.
            $result = $this->auth->login($creds);
        } catch (ValidationException $e) {
            // Credenciales invalidas / cuenta inactiva: cuenta como intento fallido.
            RateLimiter::hit($clave, self::BLOQUEO_SEGUNDOS);

            throw $e;
        }

        RateLimiter::clear($clave);

        // ¿Debe cambiar password? (sin token)
        if (isset($result['debe_cambiar_password']) && $result['debe_cambiar_password'] === true) {
            return response()->json([
                'debe_cambiar_password' => true,
                'motivo' => $result['motivo'],
                'usuario' => $result['usuario'],
                'email' => $result['email'],
            ], 200);
        }

        // Login exitoso normal.
        return (new UserResource($result['user']))
            ->additional(['token' => $result['token'], 'tipo' => 'usuario'])
            ->response()
            ->setStatusCode(200);
    }

    /**
     * POST /api/auth/password-expirada — cambio de password por expiracion (self-service).
     *
     * Endpoint publico: se autentica con las credenciales vencidas (login + password_actual).
     * Si todo bien, cambia la password, recalcula password_expira_en, y emite token normal.
     */
    public function passwordExpirada(PasswordExpiradaRequest $request): JsonResponse
    {
        $datos = $request->validated();

        $result = $this->auth->cambiarPasswordExpirada(
            (string) $datos['login'],
            (string) $datos['password_actual'],
            (string) $datos['password_nueva'],
        );

        return (new UserResource($result['user']))
            ->additional(['token' => $result['token'], 'tipo' => 'usuario'])
            ->response()
            ->setStatusCode(200);
    }

    /** POST /api/logout — invalida el token actual. */
    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());

        return response()->json(['message' => 'Sesion cerrada correctamente.']);
    }

    /** GET /api/me — usuario autenticado. */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load('role', 'modulos'));
    }

    /**
     * Clave del contador de intentos fallidos.
     *
     * Va por email+IP y no por IP sola: en el local todos salen por la misma IP
     * publica, asi que contar por IP dejaria a un cliente sin poder entrar solo
     * porque otro se equivoco. El email se normaliza y se hashea para no dejar
     * correos en las claves de cache.
     */
    private function claveDeIntentos(string $email, ?string $ip): string
    {
        return 'login-fallidos:'.sha1(mb_strtolower($email).'|'.($ip ?? 'sin-ip'));
    }

    /** Corta con 429 si la cuenta ya agoto sus intentos fallidos. */
    private function exigirIntentosDisponibles(string $clave): void
    {
        if (! RateLimiter::tooManyAttempts($clave, self::INTENTOS_FALLIDOS)) {
            return;
        }

        $segundos = RateLimiter::availableIn($clave);

        abort(response()->json([
            'message' => "Demasiados intentos fallidos. Espera {$segundos} segundos y volve a probar.",
            'retry_after' => $segundos,
        ], 429));
    }
}
