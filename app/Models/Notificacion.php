<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\PerteneceAInstancia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Notificacion para administradores. Mapea `notificaciones`.
 * Aislada por instancia (multi-tenant) via PerteneceAInstancia.
 *
 * `sucursal_id` es NULLABLE a proposito (no reutiliza PerteneceASucursal, que
 * exige igualdad estricta): hay tipos que SI son de una sede puntual
 * (pedido_nuevo/resena_nueva heredan la sede del pedido; stock_bajo la del
 * insumo) y tipos que son del negocio completo (producto_nuevo/cliente_nuevo/
 * usuario_nuevo -> sucursal_id NULL, visibles para todas las sedes). Un
 * admin_sede ve las suyas + las que no son de ninguna sede en particular.
 */
class Notificacion extends Model
{
    use HasFactory, PerteneceAInstancia;

    protected $table = 'notificaciones';

    /** @var list<string> */
    protected $fillable = [
        'instancia_id',
        'sucursal_id',
        'tipo',
        'pedido_id',
        'titulo',
        'mensaje',
        'data',
        'leida',
        'leida_en',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('sucursal', function (Builder $builder): void {
            $actor = Auth::user();
            if (! $actor instanceof User || ! $actor->esAdminSede() || $actor->sucursal_id === null) {
                return;
            }

            $tabla = $builder->getModel()->getTable();
            $builder->where(fn ($q) => $q->whereNull("{$tabla}.sucursal_id")
                ->orWhere("{$tabla}.sucursal_id", $actor->sucursal_id));
        });
    }

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'leida' => 'boolean',
            'leida_en' => 'datetime',
        ];
    }

    /** @return BelongsTo<Pedido, Notificacion> */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }
}
