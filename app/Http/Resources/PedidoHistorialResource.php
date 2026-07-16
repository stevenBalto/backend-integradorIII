<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PedidoHistorialEstado */
final class PedidoHistorialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'estado' => $this->estado,
            'comentario' => $this->comentario,
            'creado_en' => $this->creado_en?->toIso8601String(),
            'cambiado_por' => $this->whenLoaded('cambiadoPor', fn () => $this->cambiadoPor?->nombre),
        ];
    }
}
