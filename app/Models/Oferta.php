<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\PerteneceAInstancia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Oferta aplicable a productos (descuento porcentual o precio fijo). Mapea `ofertas`.
 * Sin SoftDeletes: el borrado es fisico (DELETE real).
 * Aislada por instancia (multi-tenant) via PerteneceAInstancia — hasta
 * 2026-08-14 era global/nacional (misma para todas las instancias); ver
 * migracion_2026-08-14_ofertas_cupones_por_instancia.sql.
 * `alcance` = 'todos' (visible para cualquier cliente) o 'especifico' (solo
 * los clientes en la relacion `clientes`, ver `oferta_cliente`).
 * `alcance_sedes` = 'todas' (canjeable en cualquier sede del negocio) o
 * 'especifica' (solo en las sedes de la relacion `sucursales`, ver
 * `oferta_sucursal`) — ver migracion_2026-08-14_ofertas_cupones_alcance_sedes.sql.
 */
class Oferta extends Model
{
    use HasFactory, PerteneceAInstancia;

    protected $table = 'ofertas';

    /** @var list<string> */
    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo_descuento',
        'valor',
        'fecha_inicio',
        'fecha_fin',
        'activa',
        'imagen_url',
        'alcance',
        'alcance_sedes',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'activa' => 'boolean',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    /** @return BelongsToMany<Producto> */
    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'oferta_producto');
    }

    /** Clientes a los que se les asigna esta oferta cuando alcance = 'especifico'. */
    public function clientes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'oferta_cliente', 'oferta_id', 'cliente_id');
    }

    /** Sedes donde se puede canjear cuando alcance_sedes = 'especifica'. */
    public function sucursales(): BelongsToMany
    {
        return $this->belongsToMany(Sucursal::class, 'oferta_sucursal');
    }

    /** Si la oferta se puede canjear en esa sede (siempre true cuando alcance_sedes = 'todas'). */
    public function aplicaEnSucursal(int $sucursalId): bool
    {
        if ($this->alcance_sedes !== 'especifica') {
            return true;
        }

        return $this->sucursales->contains('id', $sucursalId);
    }
}
