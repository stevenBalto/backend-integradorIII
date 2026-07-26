<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion de parametros para analiticas admin.
 */
class AnaliticasRequest extends FormRequest
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
            'mes' => ['nullable', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'sucursal_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mes.regex' => 'El mes debe tener el formato YYYY-MM (ej: 2026-07).',
            'sucursal_id.integer' => 'El ID de sucursal debe ser un numero entero.',
            'sucursal_id.min' => 'El ID de sucursal debe ser mayor a 0.',
        ];
    }
}
