<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validacion para el flujo de cambio de password expirada (self-service).
 *
 * El usuario se autentica con sus credenciales vencidas (login + password_actual)
 * y proporciona una nueva password. Es un endpoint PUBLICO (sin token).
 */
class PasswordExpiradaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // login puede ser el usuario o el email
            'login'                      => ['required', 'string', 'max:150'],
            'password_actual'            => ['required', 'string'],
            'password_nueva'             => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
            'password_nueva_confirmation' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'login.required'                => 'Ingresa tu usuario o correo.',
            'password_actual.required'      => 'Ingresa tu contraseña actual.',
            'password_nueva.required'       => 'Ingresa la nueva contraseña.',
            'password_nueva.confirmed'      => 'La confirmacion de la contraseña no coincide.',
            'password_nueva.min'            => 'La contraseña debe tener al menos 12 caracteres.',
        ];
    }
}
