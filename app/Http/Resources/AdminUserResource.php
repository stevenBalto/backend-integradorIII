<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Usuario visto desde el panel admin (incluye rol, modulos y estado de password).
 * Nunca incluye la contrasena.
 *
 * @mixin \App\Models\User
 */
final class AdminUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                          => $this->id,
            'nombre'                      => $this->nombre,
            'usuario'                     => $this->usuario,
            'email'                       => $this->email,
            'telefono'                    => $this->telefono,
            'activo'                      => (bool) $this->activo,
            'role_id'                     => $this->role_id,
            'rol'                         => $this->whenLoaded('role', fn () => $this->role->nombre),
            'sucursal_id'                 => $this->sucursal_id,
            // Campos de expiracion de password (ITEM 19).
            'dias_expiracion_password'    => $this->dias_expiracion_password,
            'password_expira_en'          => $this->password_expira_en?->toDateString(),
            'password_vencida'            => $this->password_expira_en !== null
                                                ? $this->password_expira_en->isPast()
                                                : false,
            // Campos legacy (por compatibilidad; se dejan de usar en el alta).
            'password_temporal'           => (bool) $this->password_temporal,
            'cambio_password_obligatorio' => (bool) $this->cambio_password_obligatorio,
            'ultimo_acceso_en'            => $this->ultimo_acceso_en?->toIso8601String(),
            'created_at'                  => $this->created_at?->toIso8601String(),
            'modulos'                     => $this->whenLoaded(
                'modulos',
                fn () => $this->modulos->map(fn ($m) => [
                    'id' => (int) $m->id,
                    'clave' => $m->clave,
                    'nombre' => $m->nombre,
                    'permiso' => $m->pivot->permiso ?? 'lectura',
                ])->all(),
            ),
        ];
    }
}
