<?php

declare(strict_types=1);

namespace App\Http\Requests\Usuario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion para actualizar un usuario de la instancia (patch parcial).
 *
 * IMPORTANTE: La password NO se cambia aqui. Los unicos flujos validos para
 * cambio de password son: (a) alta, (b) flujo de expiracion self-service,
 * (c) reset por correo. Esto garantiza que el modulo usuarios NO permite
 * a un admin fijar/resetear la password de otro usuario.
 */
class UpdateUsuarioRequest extends FormRequest
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
        $id = (int) $this->route('id');

        return [
            'nombre'                    => ['sometimes', 'string', 'max:120'],
            'usuario'                   => ['sometimes', 'string', 'max:60', Rule::unique('users', 'usuario')->ignore($id)],
            'email'                     => ['sometimes', 'email', 'max:150', Rule::unique('users', 'email')->ignore($id)],
            'telefono'                  => ['sometimes', 'nullable', 'string', 'max:20'],
            'role_id'                   => ['sometimes', 'integer', 'exists:roles,id'],
            'activo'                    => ['sometimes', 'boolean'],
            // Modulos con permiso: array de objetos {modulo_id, permiso}
            'modulos'                   => ['sometimes', 'array'],
            'modulos.*.modulo_id'       => ['required_with:modulos', 'integer', 'exists:modulos,id'],
            'modulos.*.permiso'         => ['required_with:modulos', 'string', 'in:lectura,editor'],
            // Dias de expiracion de password: 15, 30 o 60
            'dias_expiracion_password'  => ['sometimes', 'integer', 'in:15,30,60'],
            // password NO se acepta aqui (ver docblock)
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'modulos.*.modulo_id.exists' => 'Uno de los módulos seleccionados no existe.',
            'modulos.*.permiso.in'       => 'El permiso debe ser "lectura" o "editor".',
            'dias_expiracion_password.in' => 'Los días de expiración deben ser 15, 30 o 60.',
        ];
    }
}
