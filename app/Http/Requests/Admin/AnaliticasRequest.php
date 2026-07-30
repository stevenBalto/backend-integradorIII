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
            'granularidad' => ['nullable', 'string', 'in:mes,semana,dia'],
            'mes' => ['nullable', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'sucursal_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'granularidad.in' => 'La granularidad debe ser mes, semana o dia.',
            'mes.regex' => 'El mes debe tener el formato YYYY-MM (ej: 2026-07).',
            'fecha.date_format' => 'La fecha debe tener el formato YYYY-MM-DD.',
            'sucursal_id.integer' => 'El ID de sucursal debe ser un numero entero.',
            'sucursal_id.min' => 'El ID de sucursal debe ser mayor a 0.',
        ];
    }
}
