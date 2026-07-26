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

    /**
     * Mapa clave => valor de TODA la configuracion de la instancia actual.
     *
     * @return array<string, string|null>
     */
    public function obtenerMapa(): array
    {
        return Configuracion::query()->pluck('valor', 'clave')->toArray();
    }

    /**
     * Guarda varias claves de una (upsert por clave, aislado por instancia).
     *
     * @param array<string, string|null> $pares clave => valor
     */
    public function guardarVarias(array $pares): void
    {
        foreach ($pares as $clave => $valor) {
            $this->guardar($clave, $valor);
        }
    }

    /**
     * Lee un valor de una instancia ESPECIFICA, sin depender de la sesion
     * (el aislamiento normal filtra por el usuario logueado; esto lo usa el
     * backend en contexto de sistema, ej. al crear una notificacion cuando
     * quien dispara es el cliente pero el ajuste es del admin de esa instancia).
     */
    public function valorDeInstancia(int $instanciaId, string $clave): ?string
    {
        return Configuracion::withoutGlobalScope('instancia')
            ->where('instancia_id', $instanciaId)
            ->where('clave', $clave)
            ->value('valor');
    }
}
