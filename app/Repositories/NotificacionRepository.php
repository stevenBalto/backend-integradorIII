<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Notificacion;
use Illuminate\Database\Eloquent\Collection;

/**
 * Unica capa que consulta la tabla notificaciones via Eloquent.
 * El aislamiento por instancia lo garantiza el global scope del modelo.
 */
final class NotificacionRepository
{
    /**
     * Notificaciones mas recientes de la instancia (opcionalmente solo no leidas).
     *
     * @return Collection<int, Notificacion>
     */
    public function listar(bool $soloNoLeidas = false, int $limite = 50): Collection
    {
        return Notificacion::query()
            ->when($soloNoLeidas, fn ($q) => $q->where('leida', false))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limite)
            ->get();
    }

    public function contarNoLeidas(): int
    {
        return Notificacion::query()->where('leida', false)->count();
    }

    public function buscarPorId(int $id): ?Notificacion
    {
        return Notificacion::query()->find($id);
    }

    public function crear(array $datos): Notificacion
    {
        return Notificacion::create($datos);
    }

    /** Elimina una notificación de la bandeja. */
    public function eliminar(Notificacion $notificacion): void
    {
        $notificacion->delete();
    }

    public function marcarLeida(Notificacion $notificacion): Notificacion
    {
        if (! $notificacion->leida) {
            $notificacion->update([
                'leida' => true,
                'leida_en' => now(),
            ]);
        }

        return $notificacion;
    }

    /** Marca como leidas todas las no leidas de la instancia. Devuelve cuantas cambiaron. */
    public function marcarTodasLeidas(): int
    {
        return Notificacion::query()
            ->where('leida', false)
            ->update([
                'leida' => true,
                'leida_en' => now(),
            ]);
    }
}
