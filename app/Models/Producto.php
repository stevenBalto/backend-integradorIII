<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\PerteneceAInstancia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

/**
 * Producto del catalogo (pizza, grill, pasta, bebida). Mapea `productos`.
 * Aislado por instancia (multi-tenant) via PerteneceAInstancia.
 */
class Producto extends Model
{
    use HasFactory, PerteneceAInstancia, SoftDeletes;

    protected $table = 'productos';

    /** @var list<string> */
    protected $fillable = [
        'categoria_id',
        'nombre',
        'descripcion',
        'precio_base',
        'imagen_url',
        'disponible',
        'destacado',
    ];

    protected function casts(): array
    {
        return [
            'precio_base' => 'decimal:2',
            'disponible' => 'boolean',
            'destacado' => 'boolean',
        ];
    }

    /** @return BelongsTo<Categoria, Producto> */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }
}
