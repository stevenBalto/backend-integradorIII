<?php

declare(strict_types=1);

namespace App\DTOs\Sucursal;

final class ActualizarSucursalDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly ?string $direccion,
        public readonly ?string $telefono,
        public readonly bool $activa,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            nombre: (string) $data['nombre'],
            direccion: isset($data['direccion']) ? (string) $data['direccion'] : null,
            telefono: isset($data['telefono']) ? (string) $data['telefono'] : null,
            activa: (bool) ($data['activa'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'activa' => $this->activa,
        ];
    }
}
