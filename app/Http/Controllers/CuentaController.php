<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Cuenta\ActualizarPerfilRequest;
use App\Http\Requests\Cuenta\CambiarPasswordRequest;
use App\Http\Requests\Cuenta\SubirFotoRequest;
use App\Http\Resources\UserResource;
use App\Repositories\UsuarioFotoRepository;
use App\Services\CuentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Acciones de la cuenta del usuario autenticado (perfil, foto, contraseña).
 */
final class CuentaController extends Controller
{
    public function __construct(
        private readonly CuentaService $service,
        private readonly UsuarioFotoRepository $fotos,
    ) {}

    /** POST /api/cuenta/cambiar-password — cambia la contraseña propia. */
    public function cambiarPassword(CambiarPasswordRequest $request): JsonResponse
    {
        $datos = $request->validated();
        $this->service->cambiarPassword(
            $request->user(),
            (string) $datos['password_actual'],
            (string) $datos['password'],
        );

        return response()->json(['message' => 'Contraseña actualizada. Ya podés usar el sistema.']);
    }

    /** PUT /api/cuenta/perfil — edita nombre / teléfono / correo propios. */
    public function actualizarPerfil(ActualizarPerfilRequest $request): UserResource
    {
        $usuario = $this->service->actualizarPerfil($request->user(), $request->validated());

        return new UserResource($usuario->load('role')->loadExists('foto'));
    }

    /** POST /api/cuenta/foto — sube o reemplaza la foto de perfil. */
    public function subirFoto(SubirFotoRequest $request): JsonResponse
    {
        $this->service->guardarFoto($request->user(), $request->file('foto'));

        return response()->json(['message' => 'Foto actualizada.']);
    }

    /**
     * GET /api/cuenta/foto — devuelve la foto del PROPIO usuario autenticado.
     *
     * Privacidad: la foto se busca por el id que viene del token de Sanctum,
     * nunca por un id de la URL. Sin parametro que manipular, no existe forma
     * de pedir la foto de otra persona — no hace falta un chequeo de dueño
     * porque no hay manera de nombrar a otro.
     *
     * Se responde con Cache-Control: private para que ningun proxy compartido
     * guarde la imagen de un usuario y se la sirva a otro.
     */
    public function verFoto(Request $request): Response
    {
        $foto = $this->fotos->buscarDeUsuario($request->user()->id);

        if ($foto === null) {
            return response()->json(['message' => 'Todavía no tenés una foto de perfil.'], 404);
        }

        // El cast del modelo ya devuelve binario (el driver entrega un stream).
        $contenido = $foto->contenido;

        return response($contenido, 200, [
            'Content-Type' => $foto->mime,
            'Content-Length' => (string) strlen($contenido),
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    /** DELETE /api/cuenta/foto — vuelve al ícono por defecto. */
    public function eliminarFoto(Request $request): JsonResponse
    {
        $habia = $this->service->eliminarFoto($request->user());

        return response()->json([
            'message' => $habia ? 'Foto eliminada.' : 'No tenías foto de perfil.',
        ]);
    }
}
