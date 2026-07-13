<?php

declare(strict_types=1);

namespace App\DTOs\Usuario;

/**
 * Datos para actualizar un usuario de la instancia (patch parcial).
 * El password NO se cambia aqui (va por reset). Los modulos, si vienen, se sincronizan.
 */
final class ActualizarUsuarioDTO
{
    /**
     * @param list<int>|null $modulos
     */
    public function __construct(
        public readonly ?string $nombre = null,
        public readonly ?string $usuario = null,
        public readonly ?string $email = null,
        public readonly ?string $telefono = null,
        public readonly ?int $roleId = null,
        public readonly ?bool $activo = null,
        public readonly ?array $modulos = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            nombre: isset($data['nombre']) ? (string) $data['nombre'] : null,
            usuario: isset($data['usuario']) ? (string) $data['usuario'] : null,
            email: isset($data['email']) ? (string) $data['email'] : null,
            telefono: array_key_exists('telefono', $data) ? ($data['telefono'] !== null ? (string) $data['telefono'] : null) : null,
            roleId: isset($data['role_id']) ? (int) $data['role_id'] : null,
            activo: isset($data['activo']) ? (bool) $data['activo'] : null,
            modulos: isset($data['modulos']) ? array_map('intval', $data['modulos']) : null,
        );
    }

    /**
     * Campos escalares presentes (para update parcial de la fila users).
     *
     * @return array<string, mixed>
     */
    public function camposUsuario(): array
    {
        $campos = [
            'nombre' => $this->nombre,
            'usuario' => $this->usuario,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'role_id' => $this->roleId,
            'activo' => $this->activo,
        ];

        return array_filter($campos, static fn ($v) => $v !== null);
    }
}
