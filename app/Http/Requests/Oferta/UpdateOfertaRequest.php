<?php

declare(strict_types=1);

namespace App\Http\Requests\Oferta;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion de edicion de oferta. La autorizacion por rol la aplica el middleware de ruta.
 */
class UpdateOfertaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Normaliza booleanos (pueden llegar como string "1"/"0" o "true"/"false"). */
    protected function prepareForValidation(): void
    {
        if ($this->has('activa')) {
            $this->merge(['activa' => filter_var($this->input('activa'), FILTER_VALIDATE_BOOLEAN)]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'tipo_descuento' => ['required', 'string', Rule::in(['porcentaje', 'precio_fijo'])],
            'valor' => [
                'required',
                'numeric',
                'min:0',
                Rule::when(
                    $this->input('tipo_descuento') === 'porcentaje',
                    ['max:100']
                ),
            ],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date'],
            'activa' => ['nullable', 'boolean'],
            'producto_ids' => ['nullable', 'array'],
            'producto_ids.*' => ['integer', 'exists:productos,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la oferta es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 120 caracteres.',
            'tipo_descuento.required' => 'El tipo de descuento es obligatorio.',
            'tipo_descuento.in' => 'El tipo de descuento debe ser "porcentaje" o "precio_fijo".',
            'valor.required' => 'El valor del descuento es obligatorio.',
            'valor.numeric' => 'El valor debe ser un numero.',
            'valor.min' => 'El valor no puede ser negativo.',
            'valor.max' => 'El porcentaje no puede ser mayor a 100.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha valida.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha valida.',
            'producto_ids.array' => 'Los productos deben ser un arreglo.',
            'producto_ids.*.integer' => 'Cada ID de producto debe ser un entero.',
            'producto_ids.*.exists' => 'Uno de los productos seleccionados no existe.',
        ];
    }
}
