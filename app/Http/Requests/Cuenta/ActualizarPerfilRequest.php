<?php

declare(strict_types=1);

namespace App\Http\Requests\Cuenta;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Edicion del propio perfil (nombre, telefono, correo).
 *
 * Todos los campos son "sometimes": la pantalla puede mandar solo lo que el
 * usuario toco. Lo que NO se acepta nunca es puntos_balance, rol, activo ni
 * sucursal_id — no estan aca a proposito, para que un payload malicioso no
 * pueda colarlos aunque el modelo los tenga en $fillable.
 */
final class ActualizarPerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'nombre' => ['sometimes', 'string', 'min:2', 'max:120'],

            // nullable: dejar el campo vacio significa "no tengo telefono".
            'telefono' => ['sometimes', 'nullable', 'string', 'max:20'],

            'email' => [
                'sometimes',
                'email:rfc',
                'max:150',
                // Ignora la fila propia: reenviar el mismo correo no debe fallar.
                Rule::unique('users', 'email')->ignore($userId),
            ],

            // Solo hace falta cuando de verdad cambia el correo. El chequeo real
            // (y el 422 si falta) vive en CuentaService::actualizarPerfil, que es
            // quien sabe si el email entrante difiere del guardado.
            'password_actual' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Ese correo ya está registrado en otra cuenta.',
            'email.email' => 'Escribí un correo válido.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
        ];
    }
}
