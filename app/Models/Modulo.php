<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modulo del panel admin (Dashboard, Pedidos, Menú...). Catalogo global.
 * A que modulos puede entrar un usuario se guarda en `usuario_modulo`.
 */
class Modulo extends Model
{
    protected $table = 'modulos';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'clave',
        'nombre',
        'orden',
        'activo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }

    /** Usuarios que tienen acceso a este modulo (con nivel de permiso). */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'usuario_modulo', 'modulo_id', 'user_id')
            ->withPivot('permiso');
    }
}
