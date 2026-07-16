<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\PerteneceAInstancia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pedido del cliente. Mapea `pedidos`.
 * Aislado por instancia (multi-tenant) via PerteneceAInstancia.
 */
class Pedido extends Model
{
    use HasFactory, PerteneceAInstancia;

    protected $table = 'pedidos';

    /** @var list<string> */
    protected $fillable = [
        'cliente_id',
        'sucursal_id',
        'cupon_id',
        'modalidad',
        'estado',
        'subtotal',
        'descuento',
        'total',
        'puntos_ganados',
        'notas',
        'codigo',
        'pagado',
        'pagado_en',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'descuento' => 'decimal:2',
            'total' => 'decimal:2',
            'puntos_ganados' => 'integer',
            'pagado' => 'boolean',
            'pagado_en' => 'datetime',
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

    /** @return HasMany<DetallePedido> */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePedido::class);
    }

    /** @return HasMany<PedidoHistorialEstado> */
    public function historial(): HasMany
    {
        return $this->hasMany(PedidoHistorialEstado::class)->orderBy('creado_en');
    }
}
