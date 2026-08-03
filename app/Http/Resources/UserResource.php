<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Forma del usuario que se expone al frontend. Nunca incluye password ni tokens.
 *
 * @mixin \App\Models\User
 */
final class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'nombre'         => $this->nombre,
            'usuario'        => $this->usuario,
            'email'          => $this->email,
            'telefono'       => $this->telefono,
            'activo'         => (bool) $this->activo,
            'puntos_balance' => (int) $this->puntos_balance,
            'sucursal_id'    => $this->sucursal_id,
            'rol'            => $this->whenLoaded('role', fn () => $this->role->nombre),
            // Módulos del panel a los que el usuario tiene acceso, con su nivel (lectura|editor).
            // Solo aparece cuando la relación se carga (ej. GET /me); en login no se incluye.
            'modulos'        => $this->whenLoaded('modulos', fn () => $this->modulos->map(fn ($m) => [
                'id'      => $m->id,
                'clave'   => $m->clave,
                'nombre'  => $m->nombre,
                'permiso' => $m->pivot->permiso,
            ])->values()),
            'must_change_password' => $this->debeCambiarPassword(),
        ];
    }
}
