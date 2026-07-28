<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notificacion;
use App\Models\Pedido;
use App\Repositories\NotificacionRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Logica de negocio del modulo de notificaciones (bandeja de admin).
 */
final class NotificacionService
{
    /** Etiquetas legibles de la modalidad del pedido. */
    private const MODALIDAD_LABEL = [
        'comer_aqui' => 'Comer aquí',
        'para_llevar' => 'Para llevar',
    ];

    public function __construct(
        private readonly NotificacionRepository $notificaciones,
        private readonly ConfiguracionService $configuracion,
    ) {
    }

    /**
     * Crea la notificacion "pedido nuevo" para los admins de la instancia del pedido.
     * Se guarda con el instancia_id del pedido (no depende de la sesion) para que
     * el aislamiento sea correcto aunque quien la dispare sea el cliente.
     */
    public function notificarPedidoNuevo(Pedido $pedido): ?Notificacion
    {
        // Respeta el toggle de Configuracion de la instancia: si el admin apagó
        // "Nuevos pedidos", no se crea la notificacion.
        if (! $this->configuracion->notificacionActiva((int) $pedido->instancia_id, 'notif_nuevos_pedidos')) {
            return null;
        }

        $nombre = $pedido->nombre_cliente
            ?: (optional($pedido->cliente)->nombre ?? 'Cliente');

        $modalidad = self::MODALIDAD_LABEL[$pedido->modalidad] ?? $pedido->modalidad;

        return $this->notificaciones->crear([
            'instancia_id' => $pedido->instancia_id,
            'tipo' => 'pedido_nuevo',
            'pedido_id' => $pedido->id,
            'titulo' => "Nuevo pedido #{$pedido->codigo}",
            'mensaje' => "{$nombre} — {$modalidad}",
            'data' => [
                'codigo' => $pedido->codigo,
                'nombre_cliente' => $nombre,
                'estado_inicial' => $pedido->estado,
                'modalidad' => $pedido->modalidad,
            ],
            'leida' => false,
        ]);
    }

    /** @return Collection<int, Notificacion> */
    public function listar(bool $soloNoLeidas = false): Collection
    {
        return $this->notificaciones->listar($soloNoLeidas);
    }

    public function contarNoLeidas(): int
    {
        return $this->notificaciones->contarNoLeidas();
    }

    public function marcarLeida(int $id): Notificacion
    {
        $notificacion = $this->notificaciones->buscarPorId($id);

        if ($notificacion === null) {
            throw ValidationException::withMessages([
                'id' => ['La notificación no existe.'],
            ]);
        }

        return $this->notificaciones->marcarLeida($notificacion);
    }

    /** Marca todas las no leidas de la instancia. Devuelve cuantas cambiaron. */
    public function marcarTodasLeidas(): int
    {
        return $this->notificaciones->marcarTodasLeidas();
    }

    /** Elimina una notificación de la bandeja del admin. */
    public function eliminar(int $id): void
    {
        $notificacion = $this->notificaciones->buscarPorId($id);

        if ($notificacion === null) {
            throw ValidationException::withMessages([
                'id' => ['La notificación no existe.'],
            ]);
        }

        $this->notificaciones->eliminar($notificacion);
    }
}
