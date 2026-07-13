<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Instancia
 */
final class InstanciaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'nombre'           => $this->nombre,
            'correo_principal' => $this->correo_principal,
            'estado'           => $this->estado,
            'usuarios_count'   => (int) ($this->users_count ?? 0),
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }
}
