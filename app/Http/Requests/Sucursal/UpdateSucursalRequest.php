<?php

declare(strict_types=1);

namespace App\Http\Requests\Sucursal;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion de edicion de sucursal. La autorizacion por rol la aplica el middleware de ruta.
 * El instancia_id NUNCA se acepta del request: el aislamiento lo garantiza el global scope.
 */
class UpdateSucursalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `activa` NO se acepta: una sede no se desactiva (ver comentario en el modelo).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            // direccion es NOT NULL en la BD: obligatoria (aunque telefono si es opcional).
            'direccion' => ['required', 'string', 'max:200'],
            'telefono' => ['nullable', 'string', 'max:20'],
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
            'direccion.required' => 'La dirección es obligatoria.',
            'direccion.max' => 'La dirección no puede superar los 200 caracteres.',
            'telefono.max' => 'El teléfono no puede superar los 20 caracteres.',
        ];
    }
}
