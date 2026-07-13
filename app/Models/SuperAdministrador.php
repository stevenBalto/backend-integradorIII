<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Superadministrador: identidad GLOBAL, totalmente aislada de `users`.
 * Vive en su propia tabla y usa su propio guard/login. No tiene instancia_id.
 */
class SuperAdministrador extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\SuperAdministradorFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'superadministradores';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'usuario',
        'email',
        'password',
        'activo',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
            'ultimo_acceso_en' => 'datetime',
        ];
    }
}
