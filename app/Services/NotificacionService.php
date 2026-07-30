<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Insumo;
use App\Models\Notificacion;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Resena;
use App\Models\User;
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

    /**
     * Notifica una o varias reseñas recién dejadas por un cliente (1 aviso por envio).
     *
     * @param Collection<int, Resena> $resenas
     */
    public function notificarResenaNueva(Collection $resenas): ?Notificacion
    {
        $primera = $resenas->first();
        if ($primera === null) {
            return null;
        }
        if (! $this->configuracion->notificacionActiva((int) $primera->instancia_id, 'notif_resenas_nuevas')) {
            return null;
        }

        $cliente = optional($primera->user)->nombre ?? 'Un cliente';
        $total = $resenas->count();
        $promedio = round((float) $resenas->avg('calificacion'), 1);
        $detalle = $total === 1 ? "{$primera->calificacion}★" : "{$total} reseñas · prom. {$promedio}★";

        return $this->notificaciones->crear([
            'instancia_id' => $primera->instancia_id,
            'tipo' => 'resena_nueva',
            'pedido_id' => $primera->pedido_id,
            'titulo' => 'Nueva reseña',
            'mensaje' => "{$cliente} — {$detalle}",
            'data' => ['total' => $total, 'promedio' => $promedio, 'cliente' => $cliente],
            'leida' => false,
        ]);
    }

    /** Notifica que un insumo llegó a (o por debajo de) su stock minimo. */
    public function notificarStockBajo(Insumo $insumo): ?Notificacion
    {
        if (! $this->configuracion->notificacionActiva((int) $insumo->instancia_id, 'notif_stock_bajo')) {
            return null;
        }

        return $this->notificaciones->crear([
            'instancia_id' => $insumo->instancia_id,
            'tipo' => 'stock_bajo',
            'pedido_id' => null,
            'titulo' => "Stock bajo: {$insumo->nombre}",
            'mensaje' => "Quedan {$insumo->cantidad_actual} {$insumo->unidad_medida} (mínimo {$insumo->stock_minimo})",
            'data' => [
                'insumo_id' => $insumo->id,
                'cantidad_actual' => (float) $insumo->cantidad_actual,
                'stock_minimo' => (float) $insumo->stock_minimo,
            ],
            'leida' => false,
        ]);
    }

    /** Notifica que se creó un producto nuevo en el menú. */
    public function notificarProductoNuevo(Producto $producto): ?Notificacion
    {
        if (! $this->configuracion->notificacionActiva((int) $producto->instancia_id, 'notif_producto_nuevo')) {
            return null;
        }

        return $this->notificaciones->crear([
            'instancia_id' => $producto->instancia_id,
            'tipo' => 'producto_nuevo',
            'pedido_id' => null,
            'titulo' => 'Nuevo producto en el menú',
            'mensaje' => $producto->nombre,
            'data' => ['producto_id' => $producto->id, 'nombre' => $producto->nombre],
            'leida' => false,
        ]);
    }

    /** Notifica que se registró un cliente nuevo. */
    public function notificarClienteNuevo(User $cliente): ?Notificacion
    {
        if ($cliente->instancia_id === null) {
            return null;
        }
        if (! $this->configuracion->notificacionActiva((int) $cliente->instancia_id, 'notif_cliente_nuevo')) {
            return null;
        }

        return $this->notificaciones->crear([
            'instancia_id' => $cliente->instancia_id,
            'tipo' => 'cliente_nuevo',
            'pedido_id' => null,
            'titulo' => 'Nuevo cliente registrado',
            'mensaje' => $cliente->nombre,
            'data' => ['cliente_id' => $cliente->id, 'nombre' => $cliente->nombre, 'email' => $cliente->email],
            'leida' => false,
        ]);
    }

    /** Notifica que se creó un usuario/administrador desde el panel. */
    public function notificarUsuarioNuevo(User $usuario): ?Notificacion
    {
        if ($usuario->instancia_id === null) {
            return null;
        }
        if (! $this->configuracion->notificacionActiva((int) $usuario->instancia_id, 'notif_usuario_nuevo')) {
            return null;
        }

        $rol = optional($usuario->role)->nombre;
        $rolLabel = match ($rol) {
            'super_admin', 'admin_sede' => 'administrador',
            'cliente' => 'cliente',
            default => 'usuario',
        };

        return $this->notificaciones->crear([
            'instancia_id' => $usuario->instancia_id,
            'tipo' => 'usuario_nuevo',
            'pedido_id' => null,
            'titulo' => 'Nuevo ' . $rolLabel,
            'mensaje' => $usuario->nombre,
            'data' => ['usuario_id' => $usuario->id, 'nombre' => $usuario->nombre, 'rol' => $rol],
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

    /** Borra TODAS las notificaciones de la instancia. Devuelve cuantas borró. */
    public function eliminarTodas(): int
    {
        return $this->notificaciones->eliminarTodas();
    }
}
