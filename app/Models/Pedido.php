<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\PerteneceAInstancia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pedido de un cliente. Mapea `pedidos`.
 * Aislado por instancia (multi-tenant) via PerteneceAInstancia.
 *
 * Estados validos: pendiente, en_proceso, listo, entregado, cancelado.
 * Modalidades: para_llevar, comer_aqui.
 *
 * Modulo Clientes solo consulta (sin fillable); si el modulo Pedidos
 * necesita escritura, agregar fillable en ese momento.
 */
class Pedido extends Model
{
    use PerteneceAInstancia;

    protected $table = 'pedidos';

    // Solo lectura para el modulo Clientes; si Pedidos necesita escritura, agregar fillable.
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'descuento' => 'decimal:2',
            'total' => 'decimal:2',
            'puntos_ganados' => 'integer',
        ];
    }

    /** @return BelongsTo<User, Pedido> */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    /** @return BelongsTo<Sucursal, Pedido> */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    /** @return BelongsTo<Cupon, Pedido> */
    public function cupon(): BelongsTo
    {
        return $this->belongsTo(Cupon::class);
    }
}
