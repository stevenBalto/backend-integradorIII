<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UsuarioFotoRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Acciones sobre la cuenta del propio usuario autenticado.
 */
final class CuentaService
{
    public function __construct(
        private readonly UsuarioFotoRepository $fotos,
        private readonly FotoPerfilService $fotoPerfil,
    ) {}

    /**
     * Actualiza los datos editables del perfil (nombre, telefono, email).
     *
     * Reglas que se aplican aca y no en el Request porque dependen del estado
     * del usuario en BD, no solo del payload:
     *
     *   - **Cuenta de Google no puede cambiar el correo.** El email es la
     *     llave con la que Google identifica la cuenta; si se cambiara de
     *     este lado, el proximo "entrar con Google" traeria el correo viejo
     *     y no calzaria con ningun usuario -> la persona quedaria afuera.
     *   - **Cambiar el correo exige la contrasena actual.** El correo ES el
     *     login: sin este cheque, cualquiera que agarre el telefono con la
     *     sesion abierta se apodera de la cuenta cambiandolo al suyo.
     *   - El saldo de Roosters (puntos_balance) NO se toca aca. Solo se
     *     mueve por pedidos/canjes, que dejan su rastro en
     *     puntos_movimientos.
     *
     * @param  array{nombre?: string, telefono?: string|null, email?: string, password_actual?: string}  $datos
     *
     * @throws ValidationException
     */
    public function actualizarPerfil(User $user, array $datos): User
    {
        $cambios = [];

        if (array_key_exists('nombre', $datos)) {
            $cambios['nombre'] = $datos['nombre'];
        }

        if (array_key_exists('telefono', $datos)) {
            $telefono = $datos['telefono'];
            $cambios['telefono'] = ($telefono === null || $telefono === '') ? null : $telefono;
        }

        $emailNuevo = isset($datos['email']) ? mb_strtolower(trim($datos['email'])) : null;
        $cambiaEmail = $emailNuevo !== null && $emailNuevo !== mb_strtolower($user->email);

        if ($cambiaEmail) {
            if ($user->google_id !== null) {
                throw ValidationException::withMessages([
                    'email' => ['Tu cuenta se creó con Google, así que el correo no se puede cambiar desde aquí.'],
                ]);
            }

            if (! Hash::check((string) ($datos['password_actual'] ?? ''), $user->password)) {
                throw ValidationException::withMessages([
                    'password_actual' => ['La contraseña actual no es correcta.'],
                ]);
            }

            $cambios['email'] = $emailNuevo;
        }

        if ($cambios !== []) {
            $user->update($cambios);
        }

        return $user->refresh();
    }

    /**
     * Reemplaza la foto de perfil. La imagen se normaliza antes de tocar la BD
     * (ver FotoPerfilService: cuadrada, 512px, sin EXIF/GPS).
     */
    public function guardarFoto(User $user, UploadedFile $archivo): void
    {
        $this->fotos->guardar($user->id, $this->fotoPerfil->normalizar($archivo));
    }

    /** Quita la foto y deja al usuario con el icono por defecto. */
    public function eliminarFoto(User $user): bool
    {
        return $this->fotos->eliminar($user->id);
    }

    /**
     * Cambia la contraseña y limpia el estado temporal / obligatorio.
     *
     * @throws ValidationException contraseña actual incorrecta o nueva igual a la actual
     */
    public function cambiarPassword(User $user, string $actual, string $nueva): void
    {
        if (! Hash::check($actual, $user->password)) {
            throw ValidationException::withMessages([
                'password_actual' => ['La contraseña actual no es correcta.'],
            ]);
        }

        if (Hash::check($nueva, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['La nueva contraseña no puede ser igual a la actual.'],
            ]);
        }

        $dias = $user->dias_expiracion_password;

        $user->update([
            'password' => $nueva, // cast 'hashed'
            'password_temporal' => false,
            'cambio_password_obligatorio' => false,
            'password_expira_en' => $dias ? now()->addDays((int) $dias)->toDateString() : null,
        ]);
    }
}
