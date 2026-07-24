<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
     * Correo del usuario centinela "Invitado" (uno por instancia). Los pedidos de
     * un visitante sin sesion se guardan a su nombre para no volver cliente_id
     * nullable; el nombre real del cliente vive en pedidos.nombre_cliente.
     */
    public const EMAIL_INVITADO = 'invitado@rooster.local';

    /** True si este usuario es el centinela de pedidos de invitado. */
    public function esInvitado(): bool
    {
        return $this->email === self::EMAIL_INVITADO;
    }

    /**
     * Columnas asignables en masa (segun el esquema real, en espanol).
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'sucursal_id',
        'instancia_id',
        'nombre',
        'usuario',
        'email',
        'password',
        'telefono',
        'activo',
        'password_temporal',
        'cambio_password_obligatorio',
        'password_expira_en',
        'dias_expiracion_password',
        'ultimo_acceso_en',
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
            'password_temporal' => 'boolean',
            'cambio_password_obligatorio' => 'boolean',
            'password_expira_en' => 'date',
            'ultimo_acceso_en' => 'datetime',
        ];
    }

    /** Modulos del panel a los que este usuario tiene acceso (permisos individuales). */
    public function modulos(): BelongsToMany
    {
        return $this->belongsToMany(Modulo::class, 'usuario_modulo', 'user_id', 'modulo_id');
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

    /** Instancia (cuenta independiente) a la que pertenece el usuario. */
    public function instancia(): BelongsTo
    {
        return $this->belongsTo(Instancia::class);
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

    /**
     * ¿El usuario debe cambiar su contraseña antes de usar el sistema?
     * (temporal, cambio obligatorio marcado, o contraseña vencida).
     */
    public function debeCambiarPassword(): bool
    {
        if ($this->password_temporal || $this->cambio_password_obligatorio) {
            return true;
        }

        return $this->password_expira_en !== null && $this->password_expira_en->isPast();
    }
}
