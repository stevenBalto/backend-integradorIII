<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\PerteneceAInstancia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notificacion para administradores. Mapea `notificaciones`.
 * Aislada por instancia (multi-tenant) via PerteneceAInstancia: cada sucursal
 * ve solo SUS avisos.
 *
 * Hoy el unico `tipo` es 'pedido_nuevo' (se crea 1 fila al nacer un pedido),
 * pero el campo deja la puerta abierta a futuros avisos (stock bajo, etc).
 */
class Notificacion extends Model
{
    use HasFactory, PerteneceAInstancia;

    protected $table = 'notificaciones';

    /** @var list<string> */
    protected $fillable = [
        'instancia_id',
        'tipo',
        'pedido_id',
        'titulo',
        'mensaje',
        'data',
        'leida',
        'leida_en',
    ];

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
