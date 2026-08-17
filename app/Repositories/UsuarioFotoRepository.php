<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\UsuarioFoto;

/**
 * Unica capa que consulta la tabla usuario_fotos via Eloquent.
 */
final class UsuarioFotoRepository
{
    /**
     * Foto de un usuario, con el blob incluido. Solo llamar cuando de verdad
     * se va a servir la imagen (GET /cuenta/foto).
     */
    public function buscarDeUsuario(int $userId): ?UsuarioFoto
    {
        return UsuarioFoto::query()->find($userId);
    }

    /**
     * Crea o reemplaza la foto del usuario. Al ser user_id la PK, updateOrCreate
     * garantiza que nunca queden dos filas para la misma persona.
     *
     * @param  array{contenido: string, mime: string, tamano_bytes: int}  $datos
     */
    public function guardar(int $userId, array $datos): UsuarioFoto
    {
        return UsuarioFoto::updateOrCreate(['user_id' => $userId], $datos);
    }

    /** Borra la foto del usuario. Devuelve true si habia algo que borrar. */
    public function eliminar(int $userId): bool
    {
        return UsuarioFoto::query()->where('user_id', $userId)->delete() > 0;
    }
}
