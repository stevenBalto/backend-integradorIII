<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro (log inmutable) de un cambio de cantidad de un insumo. Mapea `insumo_movimientos`.
 * No usa SoftDeletes: es historial de auditoria, nunca se borra ni se edita.
 * La tabla solo tiene `created_at` (sin `updated_at`).
 */
class InsumoMovimiento extends Model
{
    use HasFactory;

    protected $table = 'insumo_movimientos';

    /** La tabla no tiene columna updated_at: solo se maneja created_at. */
    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'insumo_id',
        'user_id',
        'tipo',
        'cantidad_anterior',
        'cantidad_nueva',
        'diferencia',
        'nota',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_anterior' => 'decimal:2',
            'cantidad_nueva' => 'decimal:2',
            'diferencia' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Insumo, InsumoMovimiento> */
    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class);
    }

    /** Usuario que registro el movimiento (FK user_id, puede quedar null si se borra el usuario). */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
