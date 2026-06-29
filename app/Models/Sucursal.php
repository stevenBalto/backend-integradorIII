<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Sucursal del negocio. Mapea la tabla `sucursales`. Hoy hay una; el diseno escala a varias.
 */
class Sucursal extends Model
{
    protected $table = 'sucursales';

    /** @var list<string> */
    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'latitud',
        'longitud',
        'activa',
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
