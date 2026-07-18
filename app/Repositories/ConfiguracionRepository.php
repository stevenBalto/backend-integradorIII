<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Configuracion;

/**
 * Unica capa que consulta la tabla configuraciones via Eloquent.
 */
final class ConfiguracionRepository
{
    public function obtenerPorClave(string $clave): ?Configuracion
    {
        return Configuracion::query()->where('clave', $clave)->first();
    }

    public function guardar(string $clave, ?string $valor, ?string $descripcion = null): Configuracion
    {
        return Configuracion::query()->updateOrCreate(
            ['clave' => $clave],
            ['valor' => $valor, 'descripcion' => $descripcion],
        );
    }
}
