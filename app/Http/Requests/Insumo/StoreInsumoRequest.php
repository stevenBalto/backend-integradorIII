<?php

declare(strict_types=1);

namespace App\Http\Requests\Insumo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validacion de alta de insumo. La autorizacion por rol la aplica el middleware de ruta.
 */
class StoreInsumoRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:120'],
            'unidad_medida' => ['required', 'string', 'max:20'],
            'cantidad_actual' => ['nullable', 'numeric', 'min:0'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 120 caracteres.',
            'unidad_medida.required' => 'La unidad de medida es obligatoria.',
            'unidad_medida.max' => 'La unidad de medida no puede superar los 20 caracteres.',
            'cantidad_actual.numeric' => 'La cantidad actual debe ser un número.',
            'cantidad_actual.min' => 'La cantidad actual no puede ser negativa.',
            'stock_minimo.numeric' => 'El stock mínimo debe ser un número.',
            'stock_minimo.min' => 'El stock mínimo no puede ser negativo.',
        ];
    }

    /** El stock minimo no puede superar la cantidad inicial (0 si no se envio). */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $stockMinimo = $this->input('stock_minimo');
            $cantidadInicial = (float) $this->input('cantidad_actual', 0);

            if ($stockMinimo !== null && (float) $stockMinimo > $cantidadInicial) {
                $validator->errors()->add(
                    'stock_minimo',
                    'El stock mínimo no puede ser mayor a la cantidad inicial.',
                );
            }
        });
    }
}
