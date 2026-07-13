<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Acciones sobre la cuenta del propio usuario autenticado.
 */
final class CuentaService
{
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
