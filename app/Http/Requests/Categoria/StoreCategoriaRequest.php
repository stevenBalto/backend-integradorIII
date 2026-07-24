<?php

declare(strict_types=1);

namespace App\Http\Requests\Categoria;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion de alta de categoria. La autorizacion por rol la aplica el middleware
 * de ruta (auth:sanctum + password.valida + role:super_admin,admin_sede).
 *
 * `orden` y `activa` NO se aceptan del request: el `orden` se calcula server-side
 * (MAX de la instancia + 1) y `activa` siempre arranca en true. Ver CategoriaRepository::crear.
 */
class StoreCategoriaRequest extends FormRequest
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
        // max alineado a la columna real (categorias.nombre varchar(60), descripcion varchar(150)).
        // Sin regla `unique`: el proyecto no resuelve unicidad por instancia en ningun modulo
        // y la `unique` plana de Laravel ignora el global scope multi-tenant (PerteneceAInstancia).
        return [
            'nombre' => ['required', 'string', 'max:60'],
            'descripcion' => ['nullable', 'string', 'max:150'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 60 caracteres.',
            'descripcion.max' => 'La descripción no puede superar los 150 caracteres.',
        ];
    }
}
