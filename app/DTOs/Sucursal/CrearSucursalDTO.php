<?php

declare(strict_types=1);

namespace App\DTOs\Sucursal;

final class CrearSucursalDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly ?string $direccion,
        public readonly ?string $telefono,
        public readonly string $correoAdmin,
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
            correoAdmin: (string) $data['correo_admin'],
        );
    }

    /**
     * Solo los campos de la tabla `sucursales`. El correo del admin NO va aca:
     * se usa para crear su usuario, no para guardar en la sede.
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
