<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Reseña para el panel admin (gestion): incluye autor completo, producto,
 * pedido y estado (publicada/oculta) para poder filtrar y moderar.
 *
 * @mixin \App\Models\Resena
 */
final class ResenaAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'calificacion' => $this->calificacion,
            'comentario' => $this->comentario,
            'estado' => $this->estado,
            'oculta' => $this->estado === 'oculta',
            'pedido_id' => $this->pedido_id,
            'producto' => $this->whenLoaded('producto', fn () => $this->producto ? [
                'id' => $this->producto->id,
                'nombre' => $this->producto->nombre,
            ] : null),
            'cliente' => $this->whenLoaded('user', fn () => $this->user ? [
                'nombre' => $this->user->nombre,
                'email' => $this->user->email,
            ] : null),
            'respuesta_admin' => $this->respuesta_admin,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
