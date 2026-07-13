<?php

declare(strict_types=1);

namespace App\DTOs\SuperAdmin;

/**
 * Datos para actualizar un superadministrador. Todos opcionales (patch parcial).
 * `password` viaja aparte (endpoint reset-password), aqui no se toca.
 */
final class ActualizarSuperAdminDTO
{
    public function __construct(
        public readonly ?string $nombre = null,
        public readonly ?string $usuario = null,
        public readonly ?string $email = null,
        public readonly ?bool $activo = null,
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
            activo: isset($data['activo']) ? (bool) $data['activo'] : null,
        );
    }

    /**
     * Devuelve solo los campos presentes (para un update parcial).
     *
     * @return array<string, mixed>
     */
    public function soloDefinidos(): array
    {
        return array_filter([
            'nombre' => $this->nombre,
            'usuario' => $this->usuario,
            'email' => $this->email,
            'activo' => $this->activo,
        ], static fn ($v) => $v !== null);
    }
}
