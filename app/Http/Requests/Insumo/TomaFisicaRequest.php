<?php

declare(strict_types=1);

namespace App\Http\Requests\Insumo;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion de una toma fisica (conteo real de existencias de un insumo).
 * La autorizacion por rol la aplica el middleware de ruta.
 */
class TomaFisicaRequest extends FormRequest
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
            'cantidad_contada' => ['required', 'numeric', 'min:0'],
            'nota' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cantidad_contada.required' => 'La cantidad contada es obligatoria.',
            'cantidad_contada.numeric' => 'La cantidad contada debe ser un número.',
            'cantidad_contada.min' => 'La cantidad contada no puede ser negativa.',
            'nota.max' => 'La nota no puede superar los 255 caracteres.',
        ];
    }
}
