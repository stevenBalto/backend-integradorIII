<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PushToken;
use Illuminate\Database\Eloquent\Collection;

/**
 * Unica capa que consulta la tabla push_tokens via Eloquent.
 */
final class PushTokenRepository
{
    /**
     * Registra (o refresca) el token de un dispositivo.
     *
     * Es un upsert por el par user_id+token porque la app reenvia su token en cada
     * arranque: si ya existe solo se toca `updated_at`, y asi la fecha sirve para
     * saber que dispositivos siguen vivos. La UNIQUE de la tabla es el respaldo
     * real contra duplicados si dos peticiones entran a la vez.
     */
    public function registrar(int $userId, string $token, string $plataforma): void
    {
        PushToken::query()->updateOrCreate(
            ['user_id' => $userId, 'token' => $token],
            ['plataforma' => $plataforma],
        );
    }

    /**
     * Borra un token de todos los usuarios que lo tengan.
     *
     * No filtra por user_id a proposito: el caso de uso es el logout, y si dos
     * cuentas se loguearon en el mismo aparato, FCM le da el MISMO token a las
     * dos. Borrarlo entero evita que al que cerro sesion le sigan llegando los
     * pedidos del otro.
     */
    public function eliminar(string $token): void
    {
        PushToken::query()->where('token', $token)->delete();
    }

    /** @return Collection<int, PushToken> */
    public function tokensDeUsuario(int $userId): Collection
    {
        return PushToken::query()
            ->where('user_id', $userId)
            ->get();
    }
}
