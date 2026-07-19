<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\PerteneceAInstancia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'popular',
        'nuevo',
    ];

    protected function casts(): array
    {
        return [
            'precio_base' => 'decimal:2',
            'disponible' => 'boolean',
            'destacado' => 'boolean',
            'popular' => 'boolean',
            'nuevo' => 'boolean',
        ];
    }

    /** @return BelongsTo<Categoria, Producto> */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * Tamanos (variantes de precio) del producto, ordenados y solo activos no eliminados.
     *
     * @return HasMany<ProductoTamano>
     */
    public function tamanos(): HasMany
    {
        return $this->hasMany(ProductoTamano::class)
            ->where('activo', true)
            ->orderBy('orden');
    }

    /**
     * Todos los tamanos del producto (incluyendo inactivos), para administracion.
     *
     * @return HasMany<ProductoTamano>
     */
    public function todosLosTamanos(): HasMany
    {
        return $this->hasMany(ProductoTamano::class)->orderBy('orden');
    }

    /**
     * Extras asignadas puntualmente a este producto (pivote producto_extras).
     *
     * @return BelongsToMany<Extra>
     */
    public function extrasAsignados(): BelongsToMany
    {
        return $this->belongsToMany(Extra::class, 'producto_extras', 'producto_id', 'extra_id')
            ->withTimestamps();
    }
}
