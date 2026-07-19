<?php

declare(strict_types=1);

namespace App\Http\Requests\Extra;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validacion de edicion de extra. La autorizacion por rol la aplica el middleware de ruta.
 */
class UpdateExtraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Normaliza booleanos (llegan como string "1"/"0"). */
    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('disponible')) {
            $merge['disponible'] = filter_var($this->input('disponible'), FILTER_VALIDATE_BOOLEAN);
        }

        if ($this->has('es_general')) {
            $merge['es_general'] = filter_var($this->input('es_general'), FILTER_VALIDATE_BOOLEAN);
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'categoria_id' => ['nullable', 'integer', 'exists:categorias,id'],
            'nombre' => ['required', 'string', 'max:80'],
            'precio' => ['required', 'numeric', 'min:0'],
            'disponible' => ['nullable', 'boolean'],
            'es_general' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Replica en la aplicacion el CHECK de BD: general y categoria son mutuamente
     * excluyentes, y una de las dos es obligatoria.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $esGeneral = filter_var($this->input('es_general'), FILTER_VALIDATE_BOOLEAN);
            $tieneCategoria = $this->filled('categoria_id');

            if ($esGeneral && $tieneCategoria) {
                $validator->errors()->add('categoria_id', 'No debe indicar categoría si la extra es general.');
            }

            if (! $esGeneral && ! $tieneCategoria) {
                $validator->errors()->add('categoria_id', 'La categoría es obligatoria si la extra no es general.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 80 caracteres.',
            'precio.required' => 'El precio es obligatorio.',
            'precio.numeric' => 'El precio debe ser un número.',
            'precio.min' => 'El precio no puede ser negativo.',
        ];
    }
}
