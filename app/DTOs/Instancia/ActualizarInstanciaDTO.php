<?php

declare(strict_types=1);

namespace App\DTOs\Instancia;

/**
 * Datos para actualizar una instancia (patch parcial).
 */
final class ActualizarInstanciaDTO
{
    public function __construct(
        public readonly ?string $nombre = null,
        public readonly ?string $correoPrincipal = null,
        public readonly ?string $estado = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            nombre: isset($data['nombre']) ? (string) $data['nombre'] : null,
            correoPrincipal: isset($data['correo_principal']) ? (string) $data['correo_principal'] : null,
            estado: isset($data['estado']) ? (string) $data['estado'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function soloDefinidos(): array
    {
        return array_filter([
            'nombre' => $this->nombre,
            'correo_principal' => $this->correoPrincipal,
            'estado' => $this->estado,
        ], static fn ($v) => $v !== null);
    }
}
