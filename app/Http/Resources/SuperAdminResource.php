<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Forma del superadministrador expuesta al frontend. Nunca incluye password.
 *
 * @mixin \App\Models\SuperAdministrador
 */
final class SuperAdminResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'nombre'           => $this->nombre,
            'usuario'          => $this->usuario,
            'email'            => $this->email,
            'activo'           => (bool) $this->activo,
            'ultimo_acceso_en' => $this->ultimo_acceso_en?->toIso8601String(),
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }
}
