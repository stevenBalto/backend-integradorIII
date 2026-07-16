<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\PerteneceAInstancia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimiento de puntos de fidelidad de un usuario. Mapea `puntos_movimientos`.
 * La tabla solo tiene `creado_en` (sin `updated_at`).
 * Aislado por instancia (multi-tenant) via PerteneceAInstancia.
 */
class PuntosMovimiento extends Model
{
    use HasFactory, PerteneceAInstancia;

    protected $table = 'puntos_movimientos';

    /** La tabla no tiene columna updated_at: solo se maneja creado_en. */
    public const UPDATED_AT = null;
    public const CREATED_AT = 'creado_en';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'pedido_id',
        'tipo',
        'puntos',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'puntos' => 'integer',
            'creado_en' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, PuntosMovimiento> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<Pedido, PuntosMovimiento> */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }
}
