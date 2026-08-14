<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\PerteneceAInstancia;
use App\Models\Concerns\PerteneceASucursal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Insumo / materia prima del inventario (carnes, queso, harina...). Mapea `insumos`.
 * NO es un producto del menu: controla ingredientes, no pizzas.
 * Aislado por instancia (multi-tenant, via PerteneceAInstancia) Y por sede
 * (via PerteneceASucursal, desde 2026-08-14) — el inventario es exclusivo de
 * cada sede, a diferencia de productos/ofertas/cupones que si se comparten
 * entre las sedes del mismo negocio.
 */
class Insumo extends Model
{
    use HasFactory, PerteneceAInstancia, PerteneceASucursal, SoftDeletes;

    protected $table = 'insumos';

    /** @var list<string> */
    protected $fillable = [
        'nombre',
        'unidad_medida',
        'cantidad_actual',
        'stock_minimo',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_actual' => 'decimal:2',
            'stock_minimo' => 'decimal:2',
        ];
    }

    /** @return HasMany<InsumoMovimiento> */
    public function movimientos(): HasMany
    {
        return $this->hasMany(InsumoMovimiento::class);
    }

    /** @return BelongsTo<Sucursal, Insumo> */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    /** True cuando hay minimo definido y la cantidad actual esta en o por debajo de el. */
    public function bajoStock(): bool
    {
        return $this->stock_minimo !== null
            && (float) $this->cantidad_actual <= (float) $this->stock_minimo;
    }
}
