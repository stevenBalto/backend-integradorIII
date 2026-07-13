<?php

declare(strict_types=1);

namespace App\DTOs\SuperAdmin;

/**
 * Datos para crear un superadministrador.
 */
final class CrearSuperAdminDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $usuario,
        public readonly string $email,
        public readonly string $password,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            nombre: (string) $data['nombre'],
            usuario: (string) $data['usuario'],
            email: (string) $data['email'],
            password: (string) $data['password'],
        );
    }
}
