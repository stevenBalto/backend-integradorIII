<?php

declare(strict_types=1);

namespace App\Http\Requests\Extra;

use Illuminate\Foundation\Http\FormRequest;

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
        if ($this->has('disponible')) {
            $this->merge(['disponible' => filter_var($this->input('disponible'), FILTER_VALIDATE_BOOLEAN)]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
            'nombre' => ['required', 'string', 'max:80'],
            'precio' => ['required', 'numeric', 'min:0'],
            'disponible' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 80 caracteres.',
            'precio.required' => 'El precio es obligatorio.',
            'precio.numeric' => 'El precio debe ser un número.',
            'precio.min' => 'El precio no puede ser negativo.',
        ];
    }
}
