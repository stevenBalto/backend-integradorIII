<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\PerteneceAInstancia;
use Illuminate\Database\Eloquent\Model;

/**
 * Sede del negocio. Mapea la tabla `sucursales`.
 *
 * Varias sedes conviven dentro de UNA instancia y comparten menu, precios y
 * clientes; lo que se separa por sede son los pedidos y las analiticas (via
 * `users.sucursal_id` del administrador).
 *
 * `activa` queda fuera de $fillable a proposito: por decision de producto una
 * sede NO se desactiva (no hay "fuera de servicio"). La columna se conserva
 * porque es NOT NULL DEFAULT true en la BD, pero nadie la escribe.
 */
class Sucursal extends Model
{
    use PerteneceAInstancia;

    protected $table = 'sucursales';

    /** @var list<string> */
    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'latitud',
        'longitud',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
        ];
    }
}
