<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;

/**
 * Flujo "¿Olvidaste tu contraseña?" por correo (users y superadmins).
 */
final class PasswordResetController extends Controller
{
    public function __construct(private readonly PasswordResetService $service)
    {
    }

    /**
     * POST /api/forgot-password — envía el correo con el enlace.
     * Responde SIEMPRE lo mismo (no revela si el email existe).
     */
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $this->service->solicitar((string) $request->validated()['email']);

        return response()->json([
            'message' => 'Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña.',
        ]);
    }

    /** POST /api/reset-password — valida el token y cambia la contraseña. */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $datos = $request->validated();
        $this->service->restablecer(
            (string) $datos['email'],
            (string) $datos['token'],
            (string) $datos['password'],
        );

        return response()->json(['message' => 'Tu contraseña fue restablecida. Ya podés iniciar sesión.']);
    }
}
