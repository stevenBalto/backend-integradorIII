<?php

declare(strict_types=1);

namespace App\Http\Requests\Instancia;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion para actualizar una instancia (patch parcial).
 */
class UpdateInstanciaRequest extends FormRequest
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
            'nombre'           => ['sometimes', 'string', 'max:120', Rule::unique('instancias', 'nombre')->ignore($id)],
            'correo_principal' => ['sometimes', 'email', 'max:150'],
            'estado'           => ['sometimes', Rule::in(['activa', 'inactiva', 'suspendida'])],
        ];
    }
}
