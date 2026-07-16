<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Extra agregado a una linea de detalle de pedido. Mapea `detalle_pedido_extras`.
 * El precio se congela al momento de la compra.
 */
class DetallePedidoExtra extends Model
{
    use HasFactory;

    protected $table = 'detalle_pedido_extras';

    /** Esta tabla no tiene timestamps. */
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'detalle_pedido_id',
        'extra_id',
        'precio',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<DetallePedido, DetallePedidoExtra> */
    public function detallePedido(): BelongsTo
    {
        return $this->belongsTo(DetallePedido::class);
    }

    /** @return BelongsTo<Extra, DetallePedidoExtra> */
    public function extra(): BelongsTo
    {
        return $this->belongsTo(Extra::class);
    }
}
