<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\PerteneceAInstancia;
use Illuminate\Database\Eloquent\Model;

/**
 * Sucursal del negocio. Mapea la tabla `sucursales`. Hoy hay una; el diseno escala a varias.
 * Aislada por instancia (multi-tenant) via PerteneceAInstancia.
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
