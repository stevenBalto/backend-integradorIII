<?php

declare(strict_types=1);

namespace App\DTOs\Usuario;

/**
 * Datos para crear un usuario de la instancia desde el panel admin.
 *
 * @property list<int> $modulos IDs de modulos a los que tendra acceso
 */
final class CrearUsuarioDTO
{
    /**
     * @param list<int> $modulos
     */
    public function __construct(
        public readonly string $nombre,
        public readonly string $usuario,
        public readonly string $email,
        public readonly ?string $telefono,
        public readonly string $password,
        public readonly int $roleId,
        public readonly array $modulos = [],
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
            telefono: isset($data['telefono']) ? (string) $data['telefono'] : null,
            password: (string) $data['password'],
            roleId: (int) $data['role_id'],
            modulos: array_map('intval', $data['modulos'] ?? []),
        );
    }
}
