<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Cupon de descuento canjeable en pedidos. Mapea `cupones`.
 * Sin SoftDeletes: el borrado es fisico (DELETE real).
 */
class Cupon extends Model
{
    use HasFactory;

    protected $table = 'cupones';

    /** @var list<string> */
    protected $fillable = [
        'codigo',
        'tipo',
        'valor',
        'monto_minimo',
        'fecha_inicio',
        'fecha_fin',
        'usos_max',
        'usos_actuales',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'monto_minimo' => 'decimal:2',
            'activo' => 'boolean',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'usos_max' => 'integer',
            'usos_actuales' => 'integer',
        ];
    }
}
