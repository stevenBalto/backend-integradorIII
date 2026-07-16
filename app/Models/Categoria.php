<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\PerteneceAInstancia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Categoria de catalogo (pizza, grill, pastas, bebidas). Mapea `categorias`.
 * Aislada por instancia (multi-tenant) via PerteneceAInstancia.
 */
class Categoria extends Model
{
    use PerteneceAInstancia;

    protected $table = 'categorias';

    /** @var list<string> */
    protected $fillable = [
        'nombre',
        'descripcion',
        'orden',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'activa' => 'boolean',
        ];
    }

    /** @return HasMany<Producto> */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    /** @return HasMany<Extra> */
    public function extras(): HasMany
    {
        return $this->hasMany(Extra::class);
    }

    /** Extras disponibles de esta categoria. */
    public function extrasDisponibles(): HasMany
    {
        return $this->hasMany(Extra::class)->where('disponible', true);
    }
}
