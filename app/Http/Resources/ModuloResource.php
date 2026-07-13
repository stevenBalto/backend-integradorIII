<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Modulo
 */
final class ModuloResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'clave'  => $this->clave,
            'nombre' => $this->nombre,
            'orden'  => (int) $this->orden,
        ];
    }
}
