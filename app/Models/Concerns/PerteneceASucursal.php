<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Aisla por SEDE lo que ve un administrador de sede.
 *
 * Complementa a PerteneceAInstancia, que separa negocios distintos: este separa
 * la operacion diaria entre sedes del MISMO negocio. Un pedido hecho en Liberia
 * no aparece en el panel de La Fortuna.
 *
 * Solo filtra cuando el actor es un `admin_sede` con sucursal asignada:
 *   - admin_sede con `sucursal_id`  -> ve unicamente su sede.
 *   - admin general (`sucursal_id` null) -> ve todas las sedes del negocio.
 *   - cliente / invitado / superadmin    -> sin filtro (el cliente no esta
 *     atado a ninguna sede: puede pedir en la que quiera, y sus consultas ya
 *     se acotan por `cliente_id`).
 *
 * Va como global scope y NO como parametro del request a proposito: si el
 * frontend mandara la sucursal, un admin de sede podria pedir la de otra.
 */
trait PerteneceASucursal
{
    protected static function bootPerteneceASucursal(): void
    {
        static::addGlobalScope('sucursal', function (Builder $builder): void {
            $sucursalId = self::sucursalDelAdminActual();

            if ($sucursalId !== null) {
                $builder->where($builder->getModel()->getTable().'.sucursal_id', $sucursalId);
            }
        });
    }

    /** Sede del admin_sede autenticado; null para cualquier otro actor. */
    protected static function sucursalDelAdminActual(): ?int
    {
        $actor = Auth::user();

        if ($actor === null || ! method_exists($actor, 'esAdminSede') || ! $actor->esAdminSede()) {
            return null;
        }

        return $actor->sucursal_id !== null ? (int) $actor->sucursal_id : null;
    }
}
