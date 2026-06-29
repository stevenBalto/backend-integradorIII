<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Auth\CredencialesDTO;
use App\DTOs\Auth\RegistrarUsuarioDTO;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints de autenticacion (registro, login, logout, me).
 */
final class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth)
    {
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

    /** POST /api/login — valida credenciales y devuelve usuario + token. */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->login(CredencialesDTO::fromArray($request->validated()));

        return (new UserResource($result['user']))
            ->additional(['token' => $result['token']])
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
