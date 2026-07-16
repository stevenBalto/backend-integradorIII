<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Linea de detalle de un pedido. Mapea `detalle_pedido`.
 */
class DetallePedido extends Model
{
    use HasFactory;

    protected $table = 'detalle_pedido';

    /** Esta tabla no tiene timestamps. */
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'pedido_id',
        'producto_id',
        'producto_tamano_id',
        'tamano_nombre',
        'cantidad',
        'precio_unitario',
        'subtotal',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio_unitario' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Pedido, DetallePedido> */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /** @return BelongsTo<Producto, DetallePedido> */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class)->withTrashed();
    }

    /** @return HasMany<DetallePedidoExtra> */
    public function extras(): HasMany
    {
        return $this->hasMany(DetallePedidoExtra::class);
    }
}
