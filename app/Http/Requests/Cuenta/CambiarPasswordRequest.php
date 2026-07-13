<?php

declare(strict_types=1);

namespace App\Http\Requests\Cuenta;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validacion del cambio de contraseña de la cuenta autenticada.
 */
class CambiarPasswordRequest extends FormRequest
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
            'password_actual' => ['required', 'string'],
            'password'        => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password_actual.required' => 'Ingresá tu contraseña actual.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ];
    }
}
