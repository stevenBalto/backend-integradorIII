<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\PerteneceAInstancia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Configuracion clave-valor (horarios, ajustes del Home, etc). Mapea `configuraciones`.
 * Aislado por instancia (multi-tenant) via PerteneceAInstancia.
 */
class Configuracion extends Model
{
    use HasFactory, PerteneceAInstancia;

    protected $table = 'configuraciones';

    /** @var list<string> */
    protected $fillable = [
        'clave',
        'valor',
        'descripcion',
    ];
}
