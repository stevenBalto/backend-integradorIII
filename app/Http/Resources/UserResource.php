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
            'email'          => $this->email,
            'telefono'       => $this->telefono,
            'puntos_balance' => (int) $this->puntos_balance,
            'sucursal_id'    => $this->sucursal_id,
            'rol'            => $this->whenLoaded('role', fn () => $this->role->nombre),
            'must_change_password' => $this->debeCambiarPassword(),
        ];
    }
}
