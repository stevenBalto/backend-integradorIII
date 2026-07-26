<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\PerteneceAInstancia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Reseña de un cliente. Mapea `resenas`.
 * Aislada por instancia (multi-tenant) via PerteneceAInstancia.
 *
 * Dos tipos (columna `tipo`):
 *   - 'general'  : experiencia de la compra (producto_id NULL, 1 por pedido).
 *   - 'producto' : sobre un producto puntual del pedido (producto_id NOT NULL).
 *
 * Estado: 'publicada' (visible) | 'oculta' (la esconde el admin; no cuenta en
 * el promedio publico). Eliminar = soft delete (deleted_at).
 */
class Resena extends Model
{
    use HasFactory, PerteneceAInstancia, SoftDeletes;

    protected $table = 'resenas';

    /** @var list<string> */
    protected $fillable = [
        'instancia_id',
        'user_id',
        'producto_id',
        'pedido_id',
        'tipo',
        'calificacion',
        'comentario',
        'estado',
        'respuesta_admin',
        'respondido_por',
    ];

    protected function casts(): array
    {
        return [
            'calificacion' => 'integer',
        ];
    }

    /** Solo reseñas visibles al publico (publicadas y no borradas). */
    public function scopeVisibles(Builder $q): Builder
    {
        return $q->where('estado', 'publicada');
    }

    /** @return BelongsTo<User, Resena> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Producto, Resena> */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class)->withTrashed();
    }

    /** @return BelongsTo<Pedido, Resena> */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }
}
