<?php

declare(strict_types=1);

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validacion para crear un superadministrador. Password fuerte obligatorio.
 */
class StoreSuperAdminRequest extends FormRequest
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
            'nombre'   => ['required', 'string', 'max:120'],
            'usuario'  => ['required', 'string', 'max:60', 'unique:superadministradores,usuario'],
            'email'    => ['required', 'email', 'max:150', 'unique:superadministradores,email'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'usuario.unique' => 'Ese usuario ya está en uso.',
            'email.unique'   => 'Ese correo ya está en uso.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ];
    }
}
