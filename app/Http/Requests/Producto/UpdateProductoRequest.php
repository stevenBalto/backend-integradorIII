<?php

declare(strict_types=1);

namespace App\Http\Requests\Producto;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion de edicion de producto. La autorizacion por rol la aplica el middleware de ruta.
 */
class UpdateProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza booleanos (llegan como string "1"/"0" en multipart/form-data)
     * y decodifica tamanos que viene como JSON string en multipart.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('destacado')) {
            $this->merge(['destacado' => filter_var($this->input('destacado'), FILTER_VALIDATE_BOOLEAN)]);
        }
        if ($this->has('popular')) {
            $this->merge(['popular' => filter_var($this->input('popular'), FILTER_VALIDATE_BOOLEAN)]);
        }
        if ($this->has('nuevo')) {
            $this->merge(['nuevo' => filter_var($this->input('nuevo'), FILTER_VALIDATE_BOOLEAN)]);
        }
        if ($this->has('disponible')) {
            $this->merge(['disponible' => filter_var($this->input('disponible'), FILTER_VALIDATE_BOOLEAN)]);
        }
        // Tamanos viene como JSON string en multipart/form-data
        if ($this->has('tamanos') && is_string($this->input('tamanos'))) {
            $decoded = json_decode($this->input('tamanos'), true);
            $this->merge(['tamanos' => is_array($decoded) ? $decoded : []]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
            'nombre' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'precio_base' => ['required', 'numeric', 'min:0'],
            'destacado' => ['nullable', 'boolean'],
            'popular' => ['nullable', 'boolean'],
            'nuevo' => ['nullable', 'boolean'],
            'disponible' => ['nullable', 'boolean'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'tamanos' => ['nullable', 'array'],
            'tamanos.*.nombre' => ['required_with:tamanos', 'string', 'max:40'],
            'tamanos.*.precio' => ['required_with:tamanos', 'numeric', 'min:0'],
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
            'precio_base.required' => 'El precio es obligatorio.',
            'precio_base.numeric' => 'El precio debe ser un número.',
            'precio_base.min' => 'El precio no puede ser negativo.',
            'tamanos.*.nombre.required_with' => 'El nombre del tamaño es obligatorio.',
            'tamanos.*.nombre.max' => 'El nombre del tamaño no puede superar los 40 caracteres.',
            'tamanos.*.precio.required_with' => 'El precio del tamaño es obligatorio.',
            'tamanos.*.precio.numeric' => 'El precio del tamaño debe ser un número.',
            'tamanos.*.precio.min' => 'El precio del tamaño no puede ser negativo.',
        ];
    }
}
