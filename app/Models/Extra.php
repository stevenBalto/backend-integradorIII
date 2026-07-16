<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\PerteneceAInstancia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Extra / acompanamiento de una categoria. Mapea `extras`.
 * Ejemplo: para categoria "Pizzas" -> Extra queso, Jamon extra, Pepperoni extra.
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
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'disponible' => 'boolean',
        ];
    }

    /** @return BelongsTo<Categoria, Extra> */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }
}
