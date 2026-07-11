<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Categoria de catalogo (pizza, grill, pastas, bebidas). Mapea `categorias`.
 */
class Categoria extends Model
{
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
}
