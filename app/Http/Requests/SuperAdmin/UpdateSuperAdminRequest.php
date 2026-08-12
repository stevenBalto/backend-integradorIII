<?php

declare(strict_types=1);

namespace App\Http\Requests\SuperAdmin;

use App\Models\SuperAdministrador;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion para actualizar un superadministrador (patch parcial).
 * El password NO se cambia aqui; va por el endpoint reset-password.
 */
class UpdateSuperAdminRequest extends FormRequest
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
        $id = (int) $this->route('id');

        return [
            'nombre'  => ['sometimes', 'string', 'min:3', 'max:60', 'regex:/^[\pL\s\'-]+$/u'],
            'usuario' => ['sometimes', 'string', 'min:3', 'max:60', 'regex:/^[A-Za-z0-9._-]+$/', $this->disponible('usuario', 'usuario', 'Ese usuario', $id)],
            'email'   => ['sometimes', 'email', 'max:120', 'regex:/^[^@]{1,64}@/', $this->disponible('email', 'correo', 'Ese correo', $id)],
            'activo'  => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.min'   => 'El nombre debe tener al menos 3 caracteres.',
            'nombre.max'   => 'El nombre no puede pasar de 60 caracteres.',
            'usuario.min'  => 'El usuario debe tener al menos 3 caracteres.',
            'usuario.max'  => 'El usuario no puede pasar de 60 caracteres.',
            'email.email'  => 'Ingresá un correo válido (ej. ana@rooster.com).',
            'email.max'    => 'El correo no puede pasar de 120 caracteres.',
            'nombre.regex'  => 'El nombre solo admite letras, espacios y guiones.',
            'usuario.regex' => 'El usuario solo admite letras, números, punto, guion y guion bajo.',
            'email.regex'   => 'La parte antes de la @ no puede pasar de 64 caracteres.',
        ];
    }

    /**
     * Igual que al crear: el indice UNIQUE de la tabla tampoco distingue eliminados,
     * asi que un usuario/correo de una cuenta borrada sigue ocupado. El mensaje lo
     * aclara en vez de señalar un registro que el panel no muestra.
     */
    private function disponible(string $columna, string $etiqueta, string $sujeto, int $ignorarId): Closure
    {
        return function (string $atributo, mixed $valor, Closure $fail) use ($columna, $etiqueta, $sujeto, $ignorarId): void {
            $existente = SuperAdministrador::withTrashed()
                ->where($columna, $valor)
                ->where('id', '!=', $ignorarId)
                ->first();

            if ($existente === null) {
                return;
            }

            if ($existente->trashed()) {
                $fail("{$sujeto} pertenece a una cuenta eliminada y no puede reutilizarse. Usá otro {$etiqueta}.");

                return;
            }

            $fail("{$sujeto} ya está en uso.");
        };
    }
}
