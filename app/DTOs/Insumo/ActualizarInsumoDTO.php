<?php

declare(strict_types=1);

namespace App\DTOs\Insumo;

/**
 * DTO de edicion normal de un insumo. NO incluye cantidad_actual a proposito:
 * la cantidad solo cambia via toma fisica (endpoint auditado), nunca por un PUT/PATCH.
 */
final class ActualizarInsumoDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $unidadMedida,
        public readonly ?float $stockMinimo,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            nombre: (string) $data['nombre'],
            unidadMedida: (string) $data['unidad_medida'],
            stockMinimo: isset($data['stock_minimo']) ? (float) $data['stock_minimo'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'unidad_medida' => $this->unidadMedida,
            'stock_minimo' => $this->stockMinimo,
        ];
    }
}
