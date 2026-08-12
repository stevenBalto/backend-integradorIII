<?php

declare(strict_types=1);

namespace App\Http\Requests\SuperAdmin;

use App\Models\SuperAdministrador;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validacion para crear un superadministrador. Password fuerte obligatorio.
 */
class StoreSuperAdminRequest extends FormRequest
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
            // 60 y no los 120 de la columna: un nombre real no llega ni cerca, y el
            // tope alto solo servia para meter cadenas basura que rompen la tabla.
            'nombre'   => ['required', 'string', 'min:3', 'max:60', 'regex:/^[\pL\s\'-]+$/u'],
            'usuario'  => ['required', 'string', 'min:3', 'max:60', 'regex:/^[A-Za-z0-9._-]+$/', $this->disponible('usuario', 'usuario', 'Ese usuario')],
            // La parte antes de la @ no puede pasar de 64 (RFC 5321): sin esto
            // entraban correos de relleno con una local-part larguisima.
            'email'    => ['required', 'email', 'max:120', 'regex:/^[^@]{1,64}@/', $this->disponible('email', 'correo', 'Ese correo')],
            // max:72 = tope real de bcrypt; mas alla los caracteres se ignoran en
            // silencio y el usuario creeria tener una contraseña mas larga.
            'password' => ['required', 'confirmed', 'max:72', Password::min(12)->mixedCase()->numbers()->symbols()],
        ];
    }

    /**
     * Comprueba que el valor no esté tomado, mirando TAMBIEN las cuentas eliminadas.
     *
     * Eliminar un superadmin es un soft delete y el indice UNIQUE de la tabla no
     * distingue eliminados, asi que un usuario/correo de una cuenta borrada sigue
     * ocupado aunque ya no aparezca en la lista. El mensaje lo dice explicitamente
     * para que no parezca un error: sin esto se leia "ya está en uso" señalando a
     * un registro que el panel no muestra por ningun lado.
     *
     * @param  string  $columna   Columna a comprobar en la tabla.
     * @param  string  $etiqueta  Como nombrar el dato dentro de la frase.
     * @param  string  $sujeto    Inicio de la frase ("Ese usuario" / "Ese correo").
     */
    private function disponible(string $columna, string $etiqueta, string $sujeto): Closure
    {
        return function (string $atributo, mixed $valor, Closure $fail) use ($columna, $etiqueta, $sujeto): void {
            $existente = SuperAdministrador::withTrashed()->where($columna, $valor)->first();

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

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'nombre.required'  => 'El nombre completo es obligatorio.',
            'nombre.min'       => 'El nombre debe tener al menos 3 caracteres.',
            'nombre.max'       => 'El nombre no puede pasar de 60 caracteres.',
            'usuario.required' => 'El usuario es obligatorio.',
            'usuario.min'      => 'El usuario debe tener al menos 3 caracteres.',
            'usuario.max'      => 'El usuario no puede pasar de 60 caracteres.',
            'email.required'   => 'El correo es obligatorio.',
            'email.email'      => 'Ingresá un correo válido (ej. ana@rooster.com).',
            'email.max'        => 'El correo no puede pasar de 120 caracteres.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.max'      => 'La contraseña no puede pasar de 72 caracteres.',
            'nombre.regex'  => 'El nombre solo admite letras, espacios y guiones.',
            'usuario.regex' => 'El usuario solo admite letras, números, punto, guion y guion bajo.',
            'email.regex'   => 'La parte antes de la @ no puede pasar de 64 caracteres.',
        ];
    }
}
