<?php

declare(strict_types=1);

namespace App\Http\Requests\Sucursal;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion de alta de sucursal. La autorizacion por rol la aplica el middleware de ruta.
 * El instancia_id NUNCA se acepta del request: lo asigna el trait PerteneceAInstancia.
 */
class StoreSucursalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Una sede nace siempre operativa: `activa` NO se acepta del request (ver
     * comentario en el modelo). El correo del admin si, porque al crear la sede
     * se crea su administrador con credenciales temporales.
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
            'correo_admin' => ['required', 'email', 'max:150', 'unique:users,email'],
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
            'correo_admin.required' => 'El correo del administrador de la sede es obligatorio.',
            'correo_admin.email' => 'Ingresá un correo válido (ej. liberia@rooster.com).',
            'correo_admin.unique' => 'Ese correo ya está en uso por otro usuario.',
        ];
    }
}
