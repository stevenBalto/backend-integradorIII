<?php

declare(strict_types=1);

namespace App\DTOs\SuperAdmin;

/**
 * Credenciales de inicio de sesion del superadmin (login = email o usuario).
 */
final class CredencialesSuperAdminDTO
{
    public function __construct(
        public readonly string $login,
        public readonly string $password,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            login: (string) $data['login'],
            password: (string) $data['password'],
        );
    }
}
