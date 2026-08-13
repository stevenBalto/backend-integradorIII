<?php

declare(strict_types=1);

namespace App\DTOs\Sucursal;

final class ActualizarSucursalDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly ?string $direccion,
        public readonly ?string $telefono,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            nombre: (string) $data['nombre'],
            direccion: isset($data['direccion']) ? (string) $data['direccion'] : null,
            telefono: isset($data['telefono']) ? (string) $data['telefono'] : null,
        );
    }

    /**
     * `activa` no se edita: una sede existente siempre esta operativa.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
        ];
    }
}
