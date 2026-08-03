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
            'granularidad' => ['nullable', 'string', 'in:mes,semana,dia,rango'],
            'mes' => ['nullable', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'sucursal_id' => ['nullable', 'integer', 'min:1'],
            'desde' => ['nullable', 'date_format:Y-m-d', 'required_if:granularidad,rango'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'required_if:granularidad,rango', 'after_or_equal:desde'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'granularidad.in' => 'La granularidad debe ser mes, semana, dia o rango.',
            'mes.regex' => 'El mes debe tener el formato YYYY-MM (ej: 2026-07).',
            'fecha.date_format' => 'La fecha debe tener el formato YYYY-MM-DD.',
            'sucursal_id.integer' => 'El ID de sucursal debe ser un numero entero.',
            'sucursal_id.min' => 'El ID de sucursal debe ser mayor a 0.',
            'desde.date_format' => 'La fecha desde debe tener el formato YYYY-MM-DD.',
            'desde.required_if' => 'El parametro desde es obligatorio cuando la granularidad es rango.',
            'hasta.date_format' => 'La fecha hasta debe tener el formato YYYY-MM-DD.',
            'hasta.required_if' => 'El parametro hasta es obligatorio cuando la granularidad es rango.',
            'hasta.after_or_equal' => 'La fecha hasta debe ser igual o posterior a la fecha desde.',
        ];
    }
}
