<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

/**
 * Datos de entrada para registrar un cliente.
 */
final class RegistrarUsuarioDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $email,
        public readonly string $password,
        public readonly ?string $telefono,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            nombre: (string) $data['nombre'],
            email: (string) $data['email'],
            password: (string) $data['password'],
            telefono: isset($data['telefono']) ? (string) $data['telefono'] : null,
        );
    }
}
