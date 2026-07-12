<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Oferta aplicable a productos (descuento porcentual o precio fijo). Mapea `ofertas`.
 * Sin SoftDeletes: el borrado es fisico (DELETE real).
 */
class Oferta extends Model
{
    use HasFactory;

    protected $table = 'ofertas';

    /** @var list<string> */
    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo_descuento',
        'valor',
        'fecha_inicio',
        'fecha_fin',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'activa' => 'boolean',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    /** @return BelongsToMany<Producto> */
    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'oferta_producto');
    }
}
