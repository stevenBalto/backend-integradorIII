<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Repositories\SucursalRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sede cerrada = panel en SOLO LECTURA para sus administradores.
 *
 * Cuando una sede cierra no se borra nada ni se les quita el acceso: pueden
 * entrar y consultar todo su historial (pedidos, clientes, analiticas), pero no
 * modificar. Si la sede vuelve a abrir, recuperan la escritura sin mas tramite.
 *
 * Solo afecta a un `admin_sede` cuya sede este cerrada. El admin general del
 * negocio y el superadmin nunca se ven limitados por esto.
 *
 * Uso en rutas: ->middleware('sede.abierta')
 */
class EnsureSedeAbiertaParaEscribir
{
    /** Metodos que solo leen: siempre pasan. */
    private const METODOS_DE_LECTURA = ['GET', 'HEAD', 'OPTIONS'];

    public function __construct(private readonly SucursalRepository $sucursales) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), self::METODOS_DE_LECTURA, true)) {
            return $next($request);
        }

        $actor = $request->user();

        if (! $actor instanceof User || ! $actor->esAdminSede() || $actor->sucursal_id === null) {
            return $next($request);
        }

        // Se consulta la sede, en vez de leer $actor->sucursal: esa relacion puede
        // venir cargada de antes y no reflejar que la sede ya volvio a abrir.
        $sede = $this->sucursales->buscarPorId((int) $actor->sucursal_id);

        if ($sede !== null && ! $sede->activa) {
            return response()->json([
                'message' => 'Esta sede está cerrada: podés consultar la información, pero no modificarla.',
                'solo_lectura' => true,
            ], 403);
        }

        return $next($request);
    }
}
