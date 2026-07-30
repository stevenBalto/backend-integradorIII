<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificacionResource;
use App\Services\NotificacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Bandeja de notificaciones del panel admin.
 * El frontend hace polling de `index` (~15-20s) para el "tiempo real":
 * el payload trae la lista + el contador de no leidas (badge + toasts).
 */
final class NotificacionController extends Controller
{
    public function __construct(
        private readonly NotificacionService $notificaciones,
    ) {
    }

    /** GET /api/admin/notificaciones?no_leidas=1 — lista + contador de no leidas. */
    public function index(Request $request): JsonResponse
    {
        $soloNoLeidas = $request->boolean('no_leidas');

        $items = $this->notificaciones->listar($soloNoLeidas);

        return NotificacionResource::collection($items)
            ->additional(['meta' => ['no_leidas' => $this->notificaciones->contarNoLeidas()]])
            ->response();
    }

    /** POST /api/admin/notificaciones/{id}/leer — marca una como leida. */
    public function marcarLeida(int $id): JsonResponse
    {
        $notificacion = $this->notificaciones->marcarLeida($id);

        return (new NotificacionResource($notificacion))->response();
    }

    /** POST /api/admin/notificaciones/leer-todas — marca todas como leidas. */
    public function marcarTodasLeidas(): JsonResponse
    {
        $cambiadas = $this->notificaciones->marcarTodasLeidas();

        return response()->json(['cambiadas' => $cambiadas]);
    }

    /** DELETE /api/admin/notificaciones/{id} — elimina una notificación. */
    public function destroy(int $id): JsonResponse
    {
        $this->notificaciones->eliminar($id);

        return response()->json(['eliminada' => true]);
    }

    /** DELETE /api/admin/notificaciones — borra todas las de la instancia. */
    public function destroyTodas(): JsonResponse
    {
        $eliminadas = $this->notificaciones->eliminarTodas();

        return response()->json(['eliminadas' => $eliminadas]);
    }
}
