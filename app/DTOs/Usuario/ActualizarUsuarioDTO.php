<?php

declare(strict_types=1);

namespace App\DTOs\Usuario;

/**
 * Datos para actualizar un usuario de la instancia (patch parcial).
 *
 * IMPORTANTE: La password NO se cambia aqui. Los unicos flujos validos para
 * cambio de password son: (a) alta, (b) flujo de expiracion self-service,
 * (c) reset por correo. Esto garantiza que el modulo usuarios NO permite
 * a un admin fijar/resetear la password de otro usuario.
 *
 * Los modulos, si vienen, se sincronizan con sus permisos respectivos.
 */
final class ActualizarUsuarioDTO
{
    /**
     * @param array<int, array{permiso: string}>|null $modulos
     */
    public function __construct(
        public readonly ?string $nombre = null,
        public readonly ?string $usuario = null,
        public readonly ?string $email = null,
        public readonly ?string $telefono = null,
        public readonly ?int $roleId = null,
        public readonly ?bool $activo = null,
        public readonly ?array $modulos = null,
        public readonly ?int $diasExpiracionPassword = null,
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
            modulos: isset($data['modulos']) ? self::normalizarModulos($data['modulos']) : null,
            diasExpiracionPassword: isset($data['dias_expiracion_password'])
                ? (int) $data['dias_expiracion_password']
                : null,
        );
    }

    /**
     * Normaliza el array de modulos a formato sync de Laravel: modulo_id => ['permiso' => ...].
     * Acepta:
     *   - Array de objetos: [{modulo_id: 5, permiso: 'editor'}, ...]
     *   - Mapa string=>string: {"5": "editor", ...}
     *   - Lista simple de IDs: [5, 7, 9] (default lectura)
     *
     * @param array<int|string, mixed> $modulos
     * @return array<int, array{permiso: string}>
     */
    private static function normalizarModulos(array $modulos): array
    {
        $resultado = [];

        foreach ($modulos as $key => $value) {
            // Formato nuevo preferido: [{modulo_id: 5, permiso: 'editor'}, ...]
            if (is_array($value) && isset($value['modulo_id'])) {
                $moduloId = (int) $value['modulo_id'];
                $permiso = isset($value['permiso']) && in_array($value['permiso'], ['lectura', 'editor'], true)
                    ? $value['permiso']
                    : 'lectura';
                $resultado[$moduloId] = ['permiso' => $permiso];
                continue;
            }

            // Formato mapa: { "5": { "permiso": "editor" } } o { "5": "editor" }
            if (is_string($key) || (is_int($key) && (is_array($value) || is_string($value)))) {
                $moduloId = is_string($key) ? (int) $key : $key;
                if (is_array($value) && isset($value['permiso'])) {
                    $permiso = in_array($value['permiso'], ['lectura', 'editor'], true) ? $value['permiso'] : 'lectura';
                } elseif (is_string($value) && in_array($value, ['lectura', 'editor'], true)) {
                    $permiso = $value;
                } else {
                    // El value es un modulo_id, no un permiso
                    $moduloId = is_int($value) ? $value : (int) $value;
                    $permiso = 'lectura';
                }
                $resultado[$moduloId] = ['permiso' => $permiso];
            } else {
                // Formato lista simple: [5, 7, 9]
                $resultado[(int) $value] = ['permiso' => 'lectura'];
            }
        }

        return $resultado;
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
            'dias_expiracion_password' => $this->diasExpiracionPassword,
        ];

        return array_filter($campos, static fn ($v) => $v !== null);
    }
}
