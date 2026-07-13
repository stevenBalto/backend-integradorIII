<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Aislamiento multi-tenant. Un modelo que use este trait:
 *   - En TODA consulta filtra automaticamente por instancia_id del usuario
 *     autenticado (imposible olvidar un WHERE).
 *   - Al crear, asigna solo el instancia_id del usuario (nunca del request →
 *     anti tenant-hopping).
 *
 * Superadmin y peticiones sin sesion NO aplican el filtro (no tienen instancia).
 */
trait PerteneceAInstancia
{
    protected static function bootPerteneceAInstancia(): void
    {
        static::addGlobalScope('instancia', function (Builder $builder): void {
            $instanciaId = self::instanciaActual();
            if ($instanciaId !== null) {
                $builder->where($builder->getModel()->getTable() . '.instancia_id', $instanciaId);
            }
        });

        static::creating(function ($model): void {
            if ($model->instancia_id === null) {
                $instanciaId = self::instanciaActual();
                if ($instanciaId !== null) {
                    $model->instancia_id = $instanciaId;
                }
            }
        });
    }

    /** Instancia del usuario autenticado; null para superadmin o sin sesion. */
    protected static function instanciaActual(): ?int
    {
        $actor = Auth::user();

        if ($actor instanceof User && $actor->instancia_id !== null) {
            return (int) $actor->instancia_id;
        }

        return null;
    }
}
