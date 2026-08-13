<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use Illuminate\Support\Str;

/**
 * Genera las credenciales TEMPORALES de un administrador recien creado
 * (instancia o sede). Vive aparte porque las usan dos servicios y deben
 * comportarse igual en ambos: mismo formato de usuario y misma fuerza de
 * password, que es la que despues exige el cambio obligatorio al entrar.
 *
 * Requiere que la clase tenga una propiedad `$usuarios` (UserRepository).
 */
trait GeneraCredenciales
{
    /** Usuario legible derivado del nombre + sufijo numerico, garantizado unico. */
    private function generarUsuarioUnico(string $nombre): string
    {
        $base = Str::slug($nombre, '');
        $base = $base === '' ? 'admin' : Str::lower(Str::substr($base, 0, 20));

        do {
            $usuario = $base.'_'.random_int(100, 999);
        } while ($this->usuarios->existeUsuario($usuario));

        return $usuario;
    }

    /**
     * Password de 14 caracteres con mayuscula, minuscula, numero y simbolo
     * garantizados (sin caracteres ambiguos: 0/O, 1/l/I), para que se pueda
     * dictar o copiar sin errores.
     */
    private function generarPassword(): string
    {
        $may = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $min = 'abcdefghijkmnpqrstuvwxyz';
        $num = '23456789';
        $sim = '#$%&*!?@';
        $todos = $may.$min.$num.$sim;

        $pass = $may[random_int(0, strlen($may) - 1)]
            .$min[random_int(0, strlen($min) - 1)]
            .$num[random_int(0, strlen($num) - 1)]
            .$sim[random_int(0, strlen($sim) - 1)];

        for ($i = 0; $i < 10; $i++) {
            $pass .= $todos[random_int(0, strlen($todos) - 1)];
        }

        return str_shuffle($pass);
    }
}
