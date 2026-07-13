<?php

declare(strict_types=1);

namespace App\Http\Requests\Usuario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validacion para crear un usuario de la instancia.
 */
class StoreUsuarioRequest extends FormRequest
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
            'nombre'     => ['required', 'string', 'max:120'],
            'usuario'    => ['required', 'string', 'max:60', 'unique:users,usuario'],
            'email'      => ['required', 'email', 'max:150', 'unique:users,email'],
            'telefono'   => ['nullable', 'string', 'max:20'],
            'password'   => ['required', Password::min(12)->mixedCase()->numbers()->symbols()],
            'role_id'    => ['required', 'integer', 'exists:roles,id'],
            'modulos'    => ['array'],
            'modulos.*'  => ['integer', 'exists:modulos,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'usuario.unique' => 'Ese usuario ya está en uso.',
            'email.unique'   => 'Ese correo ya está registrado.',
        ];
    }
}
