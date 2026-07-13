<?php

declare(strict_types=1);

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion para actualizar un superadministrador (patch parcial).
 * El password NO se cambia aqui; va por el endpoint reset-password.
 */
class UpdateSuperAdminRequest extends FormRequest
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
            'nombre'  => ['sometimes', 'string', 'max:120'],
            'usuario' => ['sometimes', 'string', 'max:60', Rule::unique('superadministradores', 'usuario')->ignore($id)],
            'email'   => ['sometimes', 'email', 'max:150', Rule::unique('superadministradores', 'email')->ignore($id)],
            'activo'  => ['sometimes', 'boolean'],
        ];
    }
}
