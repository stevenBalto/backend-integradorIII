<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Formatea tiempos de espera para mensajes al usuario.
 *
 * Existe porque "Espera 120 segundos" se lee mal: la persona tiene que hacer la
 * cuenta. Se dice "2 minutos" y listo.
 */
final class Espera
{
    /** Ej: 45 → "45 segundos"; 60 → "1 minuto"; 120 → "2 minutos". */
    public static function legible(int $segundos): string
    {
        if ($segundos < 60) {
            return "$segundos segundos";
        }

        $minutos = (int) ceil($segundos / 60);

        return $minutos === 1 ? '1 minuto' : "$minutos minutos";
    }
}
