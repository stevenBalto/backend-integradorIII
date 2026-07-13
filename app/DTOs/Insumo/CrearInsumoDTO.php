<?php

declare(strict_types=1);

namespace App\DTOs\Insumo;

final class CrearInsumoDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $unidadMedida,
        public readonly float $cantidadActual,
        public readonly ?float $stockMinimo,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            nombre: (string) $data['nombre'],
            unidadMedida: (string) $data['unidad_medida'],
            cantidadActual: isset($data['cantidad_actual']) ? (float) $data['cantidad_actual'] : 0.0,
            stockMinimo: isset($data['stock_minimo']) ? (float) $data['stock_minimo'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'unidad_medida' => $this->unidadMedida,
            'cantidad_actual' => $this->cantidadActual,
            'stock_minimo' => $this->stockMinimo,
        ];
    }
}
