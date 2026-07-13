<?php

declare(strict_types=1);

namespace App\DTOs\Instancia;

/**
 * Datos para crear una instancia (cuenta independiente).
 */
final class CrearInstanciaDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $correoPrincipal,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            nombre: (string) $data['nombre'],
            correoPrincipal: (string) $data['correo_principal'],
        );
    }
}
