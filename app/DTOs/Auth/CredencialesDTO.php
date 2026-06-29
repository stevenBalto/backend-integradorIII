<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

/**
 * Credenciales de inicio de sesion.
 */
final class CredencialesDTO
{
    public function __construct(
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
            email: (string) $data['email'],
            password: (string) $data['password'],
        );
    }
}
