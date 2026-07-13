<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\SuperAdmin\CredencialesSuperAdminDTO;
use App\Http\Requests\SuperAdmin\SuperAdminLoginRequest;
use App\Http\Resources\SuperAdminResource;
use App\Services\SuperAdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints de autenticacion del panel de superadministracion (login aislado).
 */
final class SuperAdminAuthController extends Controller
{
    public function __construct(private readonly SuperAdminAuthService $auth)
    {
    }

    /** POST /api/superadmin/login — valida credenciales y devuelve superadmin + token. */
    public function login(SuperAdminLoginRequest $request): JsonResponse
    {
        $result = $this->auth->login(CredencialesSuperAdminDTO::fromArray($request->validated()));

        return (new SuperAdminResource($result['superadmin']))
            ->additional(['token' => $result['token']])
            ->response()
            ->setStatusCode(200);
    }

    /** POST /api/superadmin/logout — invalida el token actual. */
    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());

        return response()->json(['message' => 'Sesión de superadministrador cerrada.']);
    }

    /** GET /api/superadmin/me — superadmin autenticado. */
    public function me(Request $request): SuperAdminResource
    {
        return new SuperAdminResource($request->user());
    }
}
