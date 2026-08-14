<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\PerteneceAInstancia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Cupon de descuento canjeable en pedidos. Mapea `cupones`.
 * Sin SoftDeletes: el borrado es fisico (DELETE real).
 * Aislado por instancia (multi-tenant) via PerteneceAInstancia — hasta
 * 2026-08-14 era global/nacional (mismo para todas las instancias); ver
 * migracion_2026-08-14_ofertas_cupones_por_instancia.sql. El codigo ahora
 * es unico POR INSTANCIA (instancia_id, codigo), no globalmente.
 * `alcance` = 'todos' (visible/aplicable para cualquier cliente) o 'especifico'
 * (solo los clientes en la relacion `clientes`, ver `cupon_cliente`).
 * `alcance_sedes` = 'todas' (canjeable en cualquier sede del negocio) o
 * 'especifica' (solo en las sedes de la relacion `sucursales`, ver
 * `cupon_sucursal`) — ver migracion_2026-08-14_ofertas_cupones_alcance_sedes.sql.
 */
class Cupon extends Model
{
    use HasFactory, PerteneceAInstancia;

    protected $table = 'cupones';

    /** @var list<string> */
    protected $fillable = [
        'codigo',
        'tipo',
        'valor',
        'monto_minimo',
        'fecha_inicio',
        'fecha_fin',
        'usos_max',
        'usos_actuales',
        'activo',
        'imagen_url',
        'alcance',
        'alcance_sedes',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'monto_minimo' => 'decimal:2',
            'activo' => 'boolean',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'usos_max' => 'integer',
            'usos_actuales' => 'integer',
        ];
    }

    /** Clientes a los que se les asigna este cupon cuando alcance = 'especifico'. */
    public function clientes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'cupon_cliente', 'cupon_id', 'cliente_id');
    }

    /** Sedes donde se puede canjear cuando alcance_sedes = 'especifica'. */
    public function sucursales(): BelongsToMany
    {
        return $this->belongsToMany(Sucursal::class, 'cupon_sucursal');
    }

    /** Si el cupon se puede canjear en esa sede (siempre true cuando alcance_sedes = 'todas'). */
    public function aplicaEnSucursal(int $sucursalId): bool
    {
        if ($this->alcance_sedes !== 'especifica') {
            return true;
        }

        return $this->sucursales->contains('id', $sucursalId);
    }
}
