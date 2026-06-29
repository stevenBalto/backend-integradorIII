<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Usuario del sistema. Mapea la tabla `users` del esquema Rooster Pizza & Grill.
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * Columnas asignables en masa (segun el esquema real, en espanol).
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'sucursal_id',
        'nombre',
        'email',
        'password',
        'telefono',
    ];

    /**
     * Columnas ocultas en la serializacion.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Casts de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
            'puntos_balance' => 'integer',
        ];
    }

    /** Rol del usuario (super_admin / admin_sede / cliente). */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** Sucursal asignada (solo aplica a admin_sede; null para cliente/super_admin). */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function esSuperAdmin(): bool
    {
        return $this->role?->nombre === 'super_admin';
    }

    public function esAdminSede(): bool
    {
        return $this->role?->nombre === 'admin_sede';
    }

    public function esCliente(): bool
    {
        return $this->role?->nombre === 'cliente';
    }
}
