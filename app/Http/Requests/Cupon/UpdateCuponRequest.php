<?php

declare(strict_types=1);

namespace App\Http\Requests\Cupon;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion de edicion de cupon. La autorizacion por rol la aplica el middleware de ruta.
 */
class UpdateCuponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Normaliza booleanos y convierte codigo a mayusculas. */
    protected function prepareForValidation(): void
    {
        if ($this->has('activo')) {
            $this->merge(['activo' => filter_var($this->input('activo'), FILTER_VALIDATE_BOOLEAN)]);
        }
        if ($this->has('codigo')) {
            $this->merge(['codigo' => strtoupper($this->input('codigo'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $cuponId = $this->route('id');

        return [
            'codigo' => [
                'required',
                'string',
                'max:40',
                Rule::unique('cupones', 'codigo')->ignore($cuponId),
            ],
            'tipo' => ['required', 'string', Rule::in(['porcentaje', 'monto_fijo'])],
            'valor' => ['required', 'numeric', 'min:0'],
            'monto_minimo' => ['nullable', 'numeric', 'min:0'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date'],
            'usos_max' => ['nullable', 'integer', 'min:1'],
            'activo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo.required' => 'El codigo del cupon es obligatorio.',
            'codigo.max' => 'El codigo no puede superar los 40 caracteres.',
            'codigo.unique' => 'El codigo del cupon ya esta en uso.',
            'tipo.required' => 'El tipo de cupon es obligatorio.',
            'tipo.in' => 'El tipo debe ser "porcentaje" o "monto_fijo".',
            'valor.required' => 'El valor del cupon es obligatorio.',
            'valor.numeric' => 'El valor debe ser un numero.',
            'valor.min' => 'El valor no puede ser negativo.',
            'monto_minimo.numeric' => 'El monto minimo debe ser un numero.',
            'monto_minimo.min' => 'El monto minimo no puede ser negativo.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha valida.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha valida.',
            'usos_max.integer' => 'Los usos maximos deben ser un numero entero.',
            'usos_max.min' => 'Los usos maximos deben ser al menos 1.',
        ];
    }
}
