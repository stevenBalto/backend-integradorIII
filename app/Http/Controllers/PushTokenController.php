<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Push\EliminarPushTokenRequest;
use App\Http\Requests\Push\RegistrarPushTokenRequest;
use App\Repositories\PushTokenRepository;
use Illuminate\Http\JsonResponse;

/**
 * Alta y baja del dispositivo del cliente para notificaciones push (FCM).
 * La app registra su token al iniciar sesion y lo borra al cerrarla.
 */
final class PushTokenController extends Controller
{
    public function __construct(
        private readonly PushTokenRepository $tokens,
    ) {}

    /** POST /api/push/token — registra el dispositivo del usuario autenticado. */
    public function store(RegistrarPushTokenRequest $request): JsonResponse
    {
        $this->tokens->registrar(
            (int) $request->user()->id,
            $request->string('token')->toString(),
            $request->string('plataforma')->toString(),
        );

        return response()->json(['registrado' => true]);
    }

    /**
     * DELETE /api/push/token — da de baja el dispositivo (logout).
     * Sin esto, el telefono seguiria recibiendo los pedidos de la cuenta anterior.
     */
    public function destroy(EliminarPushTokenRequest $request): JsonResponse
    {
        $this->tokens->eliminar($request->string('token')->toString());

        return response()->json(['eliminado' => true]);
    }
}
