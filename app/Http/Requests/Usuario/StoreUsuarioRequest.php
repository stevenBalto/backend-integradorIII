<?php

declare(strict_types=1);

namespace App\Http\Requests\Usuario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validacion para crear un usuario de la instancia.
 *
 * El acceso se define SOLO por modulos + nivel de permiso (lectura|editor).
 * El role_id sigue siendo obligatorio (asigna admin_sede internamente), pero
 * no hay un "toggle administrador" aparte; los privilegios granulares vienen
 * de los modulos.
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
            'nombre'                     => ['required', 'string', 'max:120'],
            'usuario'                    => ['required', 'string', 'max:60', 'unique:users,usuario'],
            'email'                      => ['required', 'email', 'max:150', 'unique:users,email'],
            'telefono'                   => ['nullable', 'string', 'max:20'],
            'password'                   => ['required', Password::min(12)->mixedCase()->numbers()->symbols()],
            'role_id'                    => ['required', 'integer', 'exists:roles,id'],
            // Modulos con permiso: array de objetos {modulo_id, permiso} o mapa modulo_id => permiso
            'modulos'                    => ['array'],
            'modulos.*.modulo_id'        => ['required_with:modulos', 'integer', 'exists:modulos,id'],
            'modulos.*.permiso'          => ['required_with:modulos', 'string', 'in:lectura,editor'],
            // Dias de expiracion de password: 15, 30 o 60 (default 30)
            'dias_expiracion_password'   => ['sometimes', 'integer', 'in:15,30,60'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'usuario.unique'             => 'Ese usuario ya está en uso.',
            'email.unique'               => 'Ese correo ya está registrado.',
            'modulos.*.modulo_id.exists' => 'Uno de los módulos seleccionados no existe.',
            'modulos.*.permiso.in'       => 'El permiso debe ser "lectura" o "editor".',
            'dias_expiracion_password.in' => 'Los días de expiración deben ser 15, 30 o 60.',
        ];
    }
}
