<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\PerteneceAInstancia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Extra / acompanamiento. Mapea `extras`.
 * Una extra puede ser: de una categoria (categoria_id, es_general=false, aplica a
 * todos los productos de esa categoria), general (es_general=true, categoria_id=null,
 * aplica a TODOS los productos) o asignada puntualmente a productos via producto_extras.
 * Aislado por instancia (multi-tenant) via PerteneceAInstancia.
 */
class Extra extends Model
{
    use HasFactory, PerteneceAInstancia;

    protected $table = 'extras';

    /** @var list<string> */
    protected $fillable = [
        'categoria_id',
        'nombre',
        'precio',
        'disponible',
        'es_general',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'disponible' => 'boolean',
            'es_general' => 'boolean',
        ];
    }

    /** @return BelongsTo<Categoria, Extra> */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * Productos a los que esta extra fue asignada puntualmente (pivote producto_extras).
     *
     * @return BelongsToMany<Producto>
     */
    public function productosAsignados(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'producto_extras', 'extra_id', 'producto_id')
            ->withTimestamps();
    }
}
