<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Pedido\CrearPedidoDTO;
use App\Models\Extra;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProductoExtra;
use App\Models\ProductoTamano;
use App\Models\User;
use App\Repositories\PedidoHistorialRepository;
use App\Repositories\PedidoRepository;
use App\Repositories\PuntosMovimientoRepository;
use App\Repositories\SucursalRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Logica de negocio del modulo de pedidos.
 */
final class PedidoService
{
    /** Alfabeto para generar codigos (sin 0/O/1/I/L para evitar confusion visual). */
    private const CODIGO_ALFABETO = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    /** Transiciones de estado validas: estado_actual => [estados_permitidos]. */
    private const TRANSICIONES_ESTADO = [
        'pendiente' => ['en_proceso', 'cancelado'],
        'en_proceso' => ['listo', 'cancelado'],
        'listo' => ['entregado', 'cancelado'],
        'entregado' => [],
        'cancelado' => [],
    ];

    public function __construct(
        private readonly PedidoRepository $pedidos,
        private readonly PedidoHistorialRepository $historial,
        private readonly PuntosMovimientoRepository $puntos,
        private readonly SucursalRepository $sucursales,
    ) {
    }

    /**
     * Crea un nuevo pedido con todos sus detalles.
     */
    public function crear(int $userId, CrearPedidoDTO $dto): Pedido
    {
        return DB::transaction(function () use ($userId, $dto): Pedido {
            // 1. Validar sucursal
            if (! $this->sucursales->existeYActiva($dto->sucursalId)) {
                throw ValidationException::withMessages([
                    'sucursal_id' => ['La sucursal seleccionada no está disponible.'],
                ]);
            }

            // 2. Procesar items y calcular totales
            $itemsProcesados = [];
            $subtotalPedido = 0.0;

            foreach ($dto->items as $item) {
                $itemProcesado = $this->procesarItem($item);
                $itemsProcesados[] = $itemProcesado;
                $subtotalPedido += $itemProcesado['subtotal_con_extras'];
            }

            // 3. Generar codigo unico
            $codigo = $this->generarCodigoUnico();

            // 4. Calcular puntos ganados (1 punto por cada 1000 colones)
            $puntosGanados = intdiv((int) $subtotalPedido, 1000);

            // 5. Crear el pedido
            $datosPedido = [
                'cliente_id' => $userId,
                'sucursal_id' => $dto->sucursalId,
                'cupon_id' => null, // No hay integracion de cupones en este pass
                'modalidad' => $dto->modalidad,
                'nombre_cliente' => $dto->nombreCliente,
                'estado' => 'pendiente',
                'subtotal' => $subtotalPedido,
                'descuento' => 0,
                'total' => $subtotalPedido,
                'puntos_ganados' => $puntosGanados,
                'notas' => $dto->notas,
                'codigo' => $codigo,
                'pagado' => false,
                'pagado_en' => null,
            ];

            // Preparar items para el repositorio
            $itemsParaRepo = array_map(function ($item) {
                return [
                    'producto_id' => $item['producto_id'],
                    'producto_tamano_id' => $item['producto_tamano_id'],
                    'tamano_nombre' => $item['tamano_nombre'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['subtotal'],
                    'notas' => $item['notas'],
                    'extras' => $item['extras'],
                ];
            }, $itemsProcesados);

            $pedido = $this->pedidos->crear($datosPedido, $itemsParaRepo);

            // 6. Crear primer registro de historial
            $this->historial->crear([
                'pedido_id' => $pedido->id,
                'estado' => 'pendiente',
                'comentario' => null,
                'cambiado_por' => null, // Creado por el cliente mismo
            ]);

            // 7. Registrar puntos ganados si aplica
            if ($puntosGanados > 0) {
                $this->puntos->crear([
                    'user_id' => $userId,
                    'pedido_id' => $pedido->id,
                    'tipo' => 'ganado',
                    'puntos' => $puntosGanados,
                    'descripcion' => "Pedido {$codigo}",
                ]);

                User::where('id', $userId)->increment('puntos_balance', $puntosGanados);
            }

            return $pedido->load([
                'sucursal',
                'detalles.producto',
                'detalles.extras.extra',
            ]);
        });
    }

    /**
     * Procesa un item del pedido: valida producto, tamano, extras y calcula precios.
     *
     * @param array{producto_id: int, cantidad: int, producto_tamano_id: ?int, extra_ids: int[], notas: ?string} $item
     * @return array Item procesado con todos los datos calculados.
     */
    private function procesarItem(array $item): array
    {
        // Cargar producto
        $producto = Producto::query()
            ->where('disponible', true)
            ->find($item['producto_id']);

        if ($producto === null) {
            throw ValidationException::withMessages([
                'items' => ["El producto '{$item['producto_id']}' ya no está disponible."],
            ]);
        }

        // Determinar precio unitario y nombre del tamano
        $precioUnitario = (float) $producto->precio_base;
        $tamanoNombre = null;
        $productoTamanoId = null;

        if (! empty($item['producto_tamano_id'])) {
            $tamano = ProductoTamano::query()
                ->where('producto_id', $producto->id)
                ->where('activo', true)
                ->find($item['producto_tamano_id']);

            if ($tamano === null) {
                throw ValidationException::withMessages([
                    'items' => ['El tamaño seleccionado no es válido para este producto.'],
                ]);
            }

            $precioUnitario = (float) $tamano->precio;
            $tamanoNombre = $tamano->nombre;
            $productoTamanoId = $tamano->id;
        }

        // Calcular subtotal de la linea (sin extras)
        $cantidad = (int) $item['cantidad'];
        $subtotalLinea = $precioUnitario * $cantidad;

        // Procesar extras
        $extrasData = [];
        $totalExtras = 0.0;

        foreach ($item['extra_ids'] ?? [] as $extraId) {
            // Una extra es valida para este producto si es general, si es de su
            // categoria, o si fue asignada puntualmente via producto_extras.
            $extra = Extra::query()
                ->where('disponible', true)
                ->where(function ($query) use ($producto): void {
                    $query->where('es_general', true)
                        ->orWhere('categoria_id', $producto->categoria_id)
                        ->orWhereIn('id', ProductoExtra::query()
                            ->where('producto_id', $producto->id)
                            ->select('extra_id'));
                })
                ->find($extraId);

            if ($extra === null) {
                throw ValidationException::withMessages([
                    'items' => ["El extra '{$extraId}' no está disponible para este producto."],
                ]);
            }

            $precioExtra = (float) $extra->precio;
            $totalExtras += $precioExtra * $cantidad; // Extras se multiplican por cantidad

            $extrasData[] = [
                'extra_id' => $extra->id,
                'precio' => $precioExtra,
            ];
        }

        return [
            'producto_id' => $producto->id,
            'producto_tamano_id' => $productoTamanoId,
            'tamano_nombre' => $tamanoNombre,
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'subtotal' => $subtotalLinea,
            'subtotal_con_extras' => $subtotalLinea + $totalExtras,
            'notas' => $item['notas'] ?? null,
            'extras' => $extrasData,
        ];
    }

    /**
     * Genera un codigo unico de 8 caracteres en formato XXXX-XXXX.
     */
    private function generarCodigoUnico(): string
    {
        $maxIntentos = 5;
        $alfabeto = self::CODIGO_ALFABETO;
        $longitudAlfabeto = strlen($alfabeto);

        for ($intento = 0; $intento < $maxIntentos; $intento++) {
            $codigo = '';
            for ($i = 0; $i < 8; $i++) {
                $codigo .= $alfabeto[random_int(0, $longitudAlfabeto - 1)];
            }

            // Formatear como XXXX-XXXX
            $codigoFormateado = substr($codigo, 0, 4) . '-' . substr($codigo, 4);

            if (! $this->pedidos->existeCodigo($codigoFormateado)) {
                return $codigoFormateado;
            }
        }

        throw new RuntimeException('No se pudo generar un código único para el pedido después de varios intentos.');
    }

    /**
     * Cambia el estado de un pedido siguiendo la maquina de estados.
     */
    public function cambiarEstado(int $pedidoId, string $nuevoEstado, ?string $comentario, ?int $cambiadoPor): Pedido
    {
        return DB::transaction(function () use ($pedidoId, $nuevoEstado, $comentario, $cambiadoPor): Pedido {
            $pedido = $this->pedidos->buscarPorId($pedidoId);

            if ($pedido === null) {
                throw ValidationException::withMessages([
                    'id' => ['El pedido no existe.'],
                ]);
            }

            $estadoActual = $pedido->estado;
            $transicionesPermitidas = self::TRANSICIONES_ESTADO[$estadoActual] ?? [];

            if (! in_array($nuevoEstado, $transicionesPermitidas, true)) {
                throw ValidationException::withMessages([
                    'estado' => ["No se puede cambiar el pedido de '{$estadoActual}' a '{$nuevoEstado}'."],
                ]);
            }

            // Actualizar estado
            $this->pedidos->actualizarEstado($pedido, $nuevoEstado);

            // Registrar en historial
            $this->historial->crear([
                'pedido_id' => $pedido->id,
                'estado' => $nuevoEstado,
                'comentario' => $comentario,
                'cambiado_por' => $cambiadoPor,
            ]);

            return $pedido->fresh([
                'cliente',
                'sucursal',
                'detalles.producto',
                'detalles.extras.extra',
                'historial.cambiadoPor',
            ]);
        });
    }

    /**
     * Revierte manualmente un pedido a un estado por el que YA paso (accion administrativa
     * de "deshacer", distinta de cambiarEstado y su maquina de transiciones normal).
     * No borra ni edita el historial: agrega una entrada nueva y auditable al final.
     */
    public function revertirEstado(int $pedidoId, string $estadoObjetivo, int $adminId): Pedido
    {
        return DB::transaction(function () use ($pedidoId, $estadoObjetivo, $adminId): Pedido {
            $pedido = $this->pedidos->buscarPorId($pedidoId);

            if ($pedido === null) {
                throw ValidationException::withMessages([
                    'id' => ['El pedido no existe.'],
                ]);
            }

            $estadoActual = $pedido->estado;

            if ($estadoObjetivo === $estadoActual) {
                throw ValidationException::withMessages([
                    'estado' => ['El pedido ya está en ese estado.'],
                ]);
            }

            // El estado objetivo debe existir en el historial: no se puede "revertir" a
            // un estado por el que el pedido nunca pasó.
            $estuvoEnEseEstado = $pedido->historial
                ->contains(fn ($fila) => $fila->estado === $estadoObjetivo);

            if (! $estuvoEnEseEstado) {
                throw ValidationException::withMessages([
                    'estado' => ['Este pedido nunca estuvo en ese estado.'],
                ]);
            }

            // Deshacer una entrega ya pagada implica que el pago registrado post-entrega
            // deja de aplicar.
            if ($estadoActual === 'entregado' && $pedido->pagado) {
                $this->pedidos->revertirPago($pedido);
            }

            $this->pedidos->actualizarEstado($pedido, $estadoObjetivo);

            $this->historial->crear([
                'pedido_id' => $pedido->id,
                'estado' => $estadoObjetivo,
                'comentario' => 'Revertido manualmente por el admin',
                'cambiado_por' => $adminId,
            ]);

            return $pedido->fresh([
                'cliente',
                'sucursal',
                'detalles.producto',
                'detalles.extras.extra',
                'historial.cambiadoPor',
            ]);
        });
    }

    /**
     * Registra el pago de un pedido (solo si esta entregado).
     */
    public function registrarPago(int $pedidoId): Pedido
    {
        $pedido = $this->pedidos->buscarPorId($pedidoId);

        if ($pedido === null) {
            throw ValidationException::withMessages([
                'id' => ['El pedido no existe.'],
            ]);
        }

        if ($pedido->estado !== 'entregado') {
            throw ValidationException::withMessages([
                'estado' => ['Solo se puede registrar el pago de un pedido ya entregado.'],
            ]);
        }

        if ($pedido->pagado) {
            throw ValidationException::withMessages([
                'pagado' => ['Este pedido ya tiene el pago registrado.'],
            ]);
        }

        return $this->pedidos->registrarPago($pedido)->fresh([
            'cliente',
            'sucursal',
            'detalles.producto',
            'detalles.extras.extra',
            'historial.cambiadoPor',
        ]);
    }

    /** @return Collection<int, Pedido> */
    public function listarDeCliente(int $userId): Collection
    {
        return $this->pedidos->listarDeCliente($userId);
    }

    public function buscarDeCliente(int $userId, int $pedidoId): Pedido
    {
        $pedido = $this->pedidos->buscarDeCliente($userId, $pedidoId);

        if ($pedido === null) {
            throw ValidationException::withMessages([
                'id' => ['El pedido no existe.'],
            ]);
        }

        return $pedido;
    }

    /** El cliente busca uno de SUS pedidos por codigo (detalle completo). */
    public function buscarDeClientePorCodigo(int $userId, string $codigo): Pedido
    {
        $pedido = $this->pedidos->buscarDeClientePorCodigo($userId, $codigo);

        if ($pedido === null) {
            throw ValidationException::withMessages([
                'codigo' => ['No encontramos un pedido con ese código a tu nombre.'],
            ]);
        }

        return $pedido;
    }

    /** @return Collection<int, Pedido> */
    public function listarAdmin(array $filtros): Collection
    {
        return $this->pedidos->listarAdmin($filtros);
    }

    public function buscarPorId(int $id): Pedido
    {
        $pedido = $this->pedidos->buscarPorId($id);

        if ($pedido === null) {
            throw ValidationException::withMessages([
                'id' => ['El pedido no existe.'],
            ]);
        }

        return $pedido;
    }

    public function buscarPorCodigo(string $codigo): ?Pedido
    {
        return $this->pedidos->buscarPorCodigo($codigo);
    }
}
