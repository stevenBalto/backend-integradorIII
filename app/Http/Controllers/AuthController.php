<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Auth\CredencialesDTO;
use App\DTOs\Auth\RegistrarUsuarioDTO;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\SuperAdminResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Services\SuperAdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints de autenticacion (registro, login, logout, me).
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
     * Primero intenta como superadmin (tabla aparte); si no, como usuario normal.
     * Devuelve `tipo` para que el frontend enrute al panel correcto.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $creds = CredencialesDTO::fromArray($request->validated());

        // 1. ¿Es un superadmin? (identidad aislada, misma puerta de login)
        $sa = $this->superAuth->intentarLogin($creds->email, $creds->password);
        if ($sa !== null) {
            return (new SuperAdminResource($sa['superadmin']))
                ->additional(['token' => $sa['token'], 'tipo' => 'superadmin'])
                ->response()
                ->setStatusCode(200);
        }

        // 2. Usuario normal (admin de sede / cliente) — flujo existente.
        $result = $this->auth->login($creds);

        return (new UserResource($result['user']))
            ->additional(['token' => $result['token'], 'tipo' => 'usuario'])
            ->response()
            ->setStatusCode(200);
    }

    /** POST /api/logout — invalida el token actual. */
    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    /** GET /api/me — usuario autenticado. */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load('role'));
    }
}
