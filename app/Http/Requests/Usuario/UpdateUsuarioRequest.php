<?php

declare(strict_types=1);

namespace App\Http\Requests\Usuario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion para actualizar un usuario de la instancia (patch parcial).
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
            'nombre'    => ['sometimes', 'string', 'max:120'],
            'usuario'   => ['sometimes', 'string', 'max:60', Rule::unique('users', 'usuario')->ignore($id)],
            'email'     => ['sometimes', 'email', 'max:150', Rule::unique('users', 'email')->ignore($id)],
            'telefono'  => ['sometimes', 'nullable', 'string', 'max:20'],
            'role_id'   => ['sometimes', 'integer', 'exists:roles,id'],
            'activo'    => ['sometimes', 'boolean'],
            'modulos'   => ['sometimes', 'array'],
            'modulos.*' => ['integer', 'exists:modulos,id'],
        ];
    }
}
