<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Rol del sistema. Mapea la tabla `roles`. Valores: super_admin, admin_sede, cliente.
 */
class Role extends Model
{
    protected $table = 'roles';

    /** @var list<string> */
    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    /** @return HasMany<User> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
