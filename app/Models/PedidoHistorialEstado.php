<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro historico de cambios de estado de un pedido. Mapea `pedido_historial_estado`.
 * La tabla solo tiene `creado_en` (sin `updated_at`).
 */
class PedidoHistorialEstado extends Model
{
    use HasFactory;

    protected $table = 'pedido_historial_estado';

    /** La tabla no tiene columna updated_at: solo se maneja creado_en. */
    public const UPDATED_AT = null;
    public const CREATED_AT = 'creado_en';

    /** @var list<string> */
    protected $fillable = [
        'pedido_id',
        'estado',
        'comentario',
        'cambiado_por',
    ];

    protected function casts(): array
    {
        return [
            'creado_en' => 'datetime',
        ];
    }

    /** @return BelongsTo<Pedido, PedidoHistorialEstado> */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /** Usuario que realizo el cambio de estado (FK cambiado_por, puede ser null). */
    public function cambiadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cambiado_por');
    }
}
