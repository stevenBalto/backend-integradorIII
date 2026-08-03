<?php

declare(strict_types=1);

namespace App\DTOs\Usuario;

/**
 * Datos para crear un usuario de la instancia desde el panel admin.
 *
 * El acceso se define SOLO por modulos + nivel de permiso (lectura|editor).
 * La password es FIJA (no temporal); el usuario la cambiara cuando expire
 * segun dias_expiracion_password.
 *
 * @property array<int, array{permiso: string}> $modulos Mapa modulo_id => ['permiso'=>'lectura'|'editor']
 */
final class CrearUsuarioDTO
{
    /**
     * @param array<int, array{permiso: string}> $modulos
     */
    public function __construct(
        public readonly string $nombre,
        public readonly string $usuario,
        public readonly string $email,
        public readonly ?string $telefono,
        public readonly string $password,
        public readonly int $roleId,
        public readonly array $modulos = [],
        public readonly int $diasExpiracionPassword = 30,
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
            modulos: self::normalizarModulos($data['modulos'] ?? []),
            diasExpiracionPassword: isset($data['dias_expiracion_password'])
                ? (int) $data['dias_expiracion_password']
                : 30,
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
}
