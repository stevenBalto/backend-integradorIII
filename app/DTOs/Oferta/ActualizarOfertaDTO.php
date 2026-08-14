<?php

declare(strict_types=1);

namespace App\DTOs\Oferta;

final class ActualizarOfertaDTO
{
    /**
     * @param  array<int>  $productoIds
     * @param  array<int>  $clienteIds
     * @param  array<int>  $sucursalIds
     */
    public function __construct(
        public readonly string $nombre,
        public readonly ?string $descripcion,
        public readonly string $tipoDescuento,
        public readonly float $valor,
        public readonly ?string $fechaInicio,
        public readonly ?string $fechaFin,
        public readonly bool $activa,
        public readonly array $productoIds,
        public readonly string $alcance,
        public readonly array $clienteIds,
        public readonly string $alcanceSedes,
        public readonly array $sucursalIds,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nombre: (string) $data['nombre'],
            descripcion: isset($data['descripcion']) ? (string) $data['descripcion'] : null,
            tipoDescuento: (string) $data['tipo_descuento'],
            valor: (float) $data['valor'],
            fechaInicio: $data['fecha_inicio'] ?? null,
            fechaFin: $data['fecha_fin'] ?? null,
            activa: (bool) ($data['activa'] ?? true),
            productoIds: array_map('intval', $data['producto_ids'] ?? []),
            alcance: (string) ($data['alcance'] ?? 'todos'),
            clienteIds: array_map('intval', $data['cliente_ids'] ?? []),
            alcanceSedes: (string) ($data['alcance_sedes'] ?? 'todas'),
            sucursalIds: array_map('intval', $data['sucursal_ids'] ?? []),
        );
    }

    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'tipo_descuento' => $this->tipoDescuento,
            'valor' => $this->valor,
            'fecha_inicio' => $this->fechaInicio,
            'fecha_fin' => $this->fechaFin,
            'activa' => $this->activa,
            'alcance' => $this->alcance,
            'alcance_sedes' => $this->alcanceSedes,
        ];
    }
}
