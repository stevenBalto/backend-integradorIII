<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tamano (variante de precio) de un producto. Mapea `producto_tamanos`.
 * Ejemplo: Pizza Hawaiana -> Personal (5000), Mediana (8000), Familiar (12000).
 */
class ProductoTamano extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'producto_tamanos';

    /** @var list<string> */
    protected $fillable = [
        'producto_id',
        'nombre',
        'precio',
        'descripcion',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'orden' => 'integer',
            'activo' => 'boolean',
        ];
    }

    /** @return BelongsTo<Producto, ProductoTamano> */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
