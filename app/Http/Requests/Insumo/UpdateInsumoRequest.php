<?php

declare(strict_types=1);

namespace App\Http\Requests\Insumo;

use App\Models\Insumo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validacion de edicion de insumo. NO acepta cantidad_actual a proposito:
 * la cantidad solo cambia via toma fisica. La autorizacion por rol la aplica el middleware de ruta.
 */
class UpdateInsumoRequest extends FormRequest
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
            'nombre' => ['sometimes', 'required', 'string', 'max:120'],
            'unidad_medida' => ['sometimes', 'required', 'string', 'max:20'],
            'stock_minimo' => ['sometimes', 'nullable', 'numeric', 'min:0'],
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
            'stock_minimo.numeric' => 'El stock mínimo debe ser un número.',
            'stock_minimo.min' => 'El stock mínimo no puede ser negativo.',
        ];
    }

    /** El stock minimo no puede superar la cantidad_actual actual del insumo (no editable aqui). */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $stockMinimo = $this->input('stock_minimo');

            if ($stockMinimo === null) {
                return;
            }

            $cantidadActual = (float) (Insumo::query()->find($this->route('id'))?->cantidad_actual ?? 0);

            if ((float) $stockMinimo > $cantidadActual) {
                $validator->errors()->add(
                    'stock_minimo',
                    'El stock mínimo no puede ser mayor a la cantidad actual.',
                );
            }
        });
    }
}
