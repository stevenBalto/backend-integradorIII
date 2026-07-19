<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Asignacion puntual de una extra a un producto especifico. Mapea `producto_extras`.
 * Permite ofrecer una extra (de categoria) en un producto de otra categoria, sin
 * volverla general.
 */
class ProductoExtra extends Model
{
    use HasFactory;

    protected $table = 'producto_extras';

    /** @var list<string> */
    protected $fillable = [
        'producto_id',
        'extra_id',
    ];

    /** @return BelongsTo<Producto, ProductoExtra> */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /** @return BelongsTo<Extra, ProductoExtra> */
    public function extra(): BelongsTo
    {
        return $this->belongsTo(Extra::class);
    }
}
