<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Instancia = cuenta completamente independiente (multi-tenant). Cada instancia
 * es un negocio/sucursal aislado; toda su data operativa cuelga de instancia_id.
 * La gestionan SOLO los superadministradores.
 */
class Instancia extends Model
{
    use SoftDeletes;

    protected $table = 'instancias';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'correo_principal',
        'estado',
        'creada_por',
    ];

    /** Usuarios que pertenecen a esta instancia. */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'instancia_id');
    }

    public function estaActiva(): bool
    {
        return $this->estado === 'activa';
    }
}
