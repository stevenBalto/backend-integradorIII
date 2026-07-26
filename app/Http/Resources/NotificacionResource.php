<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Notificacion */
final class NotificacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'pedido_id' => $this->pedido_id,
            'titulo' => $this->titulo,
            'mensaje' => $this->mensaje,
            'data' => $this->data,
            'leida' => (bool) $this->leida,
            'leida_en' => $this->leida_en?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
