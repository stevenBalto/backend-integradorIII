<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Oferta aplicable a productos (descuento porcentual o precio fijo). Mapea `ofertas`.
 * Sin SoftDeletes: el borrado es fisico (DELETE real).
 * GLOBAL (no aislada por instancia): las promos son nacionales, iguales para
 * todas las sucursales (estilo Papa John's).
 * `alcance` = 'todos' (visible para cualquier cliente) o 'especifico' (solo
 * los clientes en la relacion `clientes`, ver `oferta_cliente`).
 */
class Oferta extends Model
{
    use HasFactory;

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
}
