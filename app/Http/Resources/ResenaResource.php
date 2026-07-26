<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Reseña para la vista publica del cliente (opiniones en la ficha de producto).
 * No expone email ni datos sensibles del autor.
 *
 * @mixin \App\Models\Resena
 */
final class ResenaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'calificacion' => $this->calificacion,
            'comentario' => $this->comentario,
            'cliente' => $this->whenLoaded('user', fn () => $this->user?->nombre),
            'respuesta_admin' => $this->respuesta_admin,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
