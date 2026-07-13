<?php

declare(strict_types=1);

namespace App\Http\Requests\Instancia;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion para crear una instancia. El correo principal sera el email del
 * administrador inicial que se crea automaticamente, por eso debe ser unico.
 */
class StoreInstanciaRequest extends FormRequest
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
            'nombre'           => ['required', 'string', 'max:120', 'unique:instancias,nombre'],
            'correo_principal' => ['required', 'email', 'max:150', 'unique:users,email'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe una instancia con ese nombre.',
            'correo_principal.unique' => 'Ese correo ya está en uso por otro usuario.',
        ];
    }
}
