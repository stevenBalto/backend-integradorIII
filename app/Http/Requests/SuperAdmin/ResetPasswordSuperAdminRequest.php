<?php

declare(strict_types=1);

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validacion para resetear la contraseña de un superadministrador.
 */
class ResetPasswordSuperAdminRequest extends FormRequest
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
            // max:72 = tope real de bcrypt; mas alla los caracteres se ignoran en
            // silencio y el usuario creeria tener una contraseña mas larga.
            'password' => ['required', 'confirmed', 'max:72', Password::min(12)->mixedCase()->numbers()->symbols()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ];
    }
}
