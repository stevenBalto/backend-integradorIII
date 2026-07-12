<?php

declare(strict_types=1);

namespace App\DTOs\Cupon;

final class ActualizarCuponDTO
{
    public function __construct(
        public readonly string $codigo,
        public readonly string $tipo,
        public readonly float $valor,
        public readonly ?float $montoMinimo,
        public readonly ?string $fechaInicio,
        public readonly ?string $fechaFin,
        public readonly ?int $usosMax,
        public readonly bool $activo,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            codigo: strtoupper((string) $data['codigo']),
            tipo: (string) $data['tipo'],
            valor: (float) $data['valor'],
            montoMinimo: isset($data['monto_minimo']) ? (float) $data['monto_minimo'] : null,
            fechaInicio: $data['fecha_inicio'] ?? null,
            fechaFin: $data['fecha_fin'] ?? null,
            usosMax: isset($data['usos_max']) ? (int) $data['usos_max'] : null,
            activo: (bool) ($data['activo'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'codigo' => $this->codigo,
            'tipo' => $this->tipo,
            'valor' => $this->valor,
            'monto_minimo' => $this->montoMinimo,
            'fecha_inicio' => $this->fechaInicio,
            'fecha_fin' => $this->fechaFin,
            'usos_max' => $this->usosMax,
            'activo' => $this->activo,
        ];
    }
}
