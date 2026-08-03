<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Pedido\CrearPedidoDTO;
use App\Models\Cupon;
use App\Models\Extra;
use App\Models\Oferta;
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
use Illuminate\Support\Facades\Log;
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
        private readonly NotificacionService $notificaciones,
        private readonly CuponService $cupones,
        private readonly OfertaService $ofertas,
        private readonly ConfiguracionService $configuracion,
    ) {
    }

    /**
     * Crea un nuevo pedido con todos sus detalles.
     */
    public function crear(int $userId, CrearPedidoDTO $dto, bool $acumulaPuntos = true): Pedido
    {
        $pedido = DB::transaction(function () use ($userId, $dto, $acumulaPuntos): Pedido {
            // 1. Validar sucursal
            $sucursal = $this->sucursales->buscarPorId($dto->sucursalId);
            if ($sucursal === null || ! $sucursal->activa) {
                throw ValidationException::withMessages([
                    'sucursal_id' => ['La sucursal seleccionada no está disponible.'],
                ]);
            }

            // 1.1 Reglas de operacion del negocio (Configuracion de la instancia):
            //     recibir pedidos, cierre temporal y HORARIO. Fuera de horario no
            //     se acepta el pedido.
            $operacion = $this->configuracion->operacionDeInstancia((int) $sucursal->instancia_id);

            $motivoCierre = $this->configuracion->motivoCierre($operacion);
            if ($motivoCierre !== null) {
                throw ValidationException::withMessages(['sucursal_id' => [$motivoCierre]]);
            }

            // 1.2 Modalidad habilitada por el negocio.
            $modalidadActiva = $dto->modalidad === 'comer_aqui'
                ? $operacion['modalidad_comer_aqui']
                : $operacion['modalidad_para_llevar'];
            if (! $modalidadActiva) {
                throw ValidationException::withMessages([
                    'modalidad' => ['Esta modalidad no está disponible en este momento.'],
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

            // 2.1 Monto minimo de pedido (Configuracion del negocio).
            $montoMinimo = $operacion['pedido_monto_minimo'];
            if ($montoMinimo > 0 && $subtotalPedido < $montoMinimo) {
                throw ValidationException::withMessages([
                    'items' => ['El pedido mínimo es de ₡' . number_format($montoMinimo, 0, ',', '.') . '.'],
                ]);
            }

            // 3. Generar codigo unico
            $codigo = $this->generarCodigoUnico();

            // 4. Canje de Roosters (1 Rooster = ₡1). Solo usuarios logueados; se capa al
            //    saldo real y al subtotal para no dejar el total en negativo.
            $descuento = 0;
            if ($acumulaPuntos && $operacion['roosters_activo'] && $dto->roostersAUsar > 0) {
                $saldo = (int) User::where('id', $userId)->lockForUpdate()->value('puntos_balance');
                $descuento = max(0, min($dto->roostersAUsar, $saldo, (int) $subtotalPedido));
            }

            // 4.1 Canje de cupon (QR o checkout): se suma al descuento de Roosters,
            //     siempre topado para no dejar el total en negativo. Se valida contra
            //     el subtotal ANTES de restar Roosters (monto_minimo se mide sobre el
            //     subtotal real de la compra, no sobre lo que ya se descontó).
            /** @var Cupon|null $cupon */
            $cupon = null;
            if ($dto->cuponCodigo !== null) {
                $cupon = $this->cupones->buscarActivoPorCodigo($dto->cuponCodigo);

                if ($cupon === null) {
                    throw ValidationException::withMessages([
                        'cupon_codigo' => ['Este cupón no existe, está vencido, inactivo o ya agotó sus usos.'],
                    ]);
                }

                $descuentoCupon = $this->cupones->calcularDescuento($cupon, $subtotalPedido);
                $descuento = min($descuento + $descuentoCupon, $subtotalPedido);
            }

            // 4.2 Canje de oferta (QR o pedido de mostrador): el descuento solo se
            //     calcula sobre el subtotal de los productos que SI pertenecen a la
            //     oferta (no sobre todo el pedido). Se agrega a `notas` para dejar
            //     trazabilidad sin necesitar una columna nueva en `pedidos`.
            /** @var Oferta|null $oferta */
            $oferta = null;
            $notasFinal = $dto->notas;
            if ($dto->ofertaId !== null) {
                $oferta = $this->ofertas->buscarActivaPorId($dto->ofertaId);

                if ($oferta === null) {
                    throw ValidationException::withMessages([
                        'oferta_id' => ['Esta oferta no existe, está vencida o inactiva.'],
                    ]);
                }

                $descuentoOferta = $this->ofertas->calcularDescuento($oferta, $itemsProcesados);
                $descuento = min($descuento + $descuentoOferta, $subtotalPedido);
                $notasFinal = trim("[Oferta: {$oferta->nombre}] " . ($notasFinal ?? ''));
            }

            $total = $subtotalPedido - $descuento;

            // 5. Roosters ganados: % del total pagado, configurable por el negocio
            //    (Configuración → Programa de Roosters). El invitado (no logueado)
            //    no acumula, y si el programa está apagado tampoco.
            $puntosGanados = ($acumulaPuntos && $operacion['roosters_activo'])
                ? (int) floor($total * ($operacion['roosters_porcentaje'] / 100))
                : 0;

            // 6. Crear el pedido
            $datosPedido = [
                'cliente_id' => $userId,
                'sucursal_id' => $dto->sucursalId,
                'cupon_id' => $cupon?->id,
                'modalidad' => $dto->modalidad,
                'nombre_cliente' => $dto->nombreCliente,
                'estado' => 'pendiente',
                'subtotal' => $subtotalPedido,
                'descuento' => $descuento,
                'total' => $total,
                'puntos_ganados' => $puntosGanados,
                'notas' => $notasFinal,
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

            // 7. Crear primer registro de historial
            $this->historial->crear([
                'pedido_id' => $pedido->id,
                'estado' => 'pendiente',
                'comentario' => null,
                'cambiado_por' => null, // Creado por el cliente mismo
            ]);

            // 8. Movimientos de Roosters: primero el canje (resta), luego la ganancia.
            if ($descuento > 0) {
                $this->puntos->crear([
                    'user_id' => $userId,
                    'pedido_id' => $pedido->id,
                    'tipo' => 'canjeado',
                    'puntos' => -$descuento,
                    'descripcion' => "Canje en pedido {$codigo}",
                ]);

                User::where('id', $userId)->decrement('puntos_balance', $descuento);
            }

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

            // 9. Registrar el uso del cupon (si aplico) — dentro de la transaccion:
            //    si algo falla arriba, el cupon no se descuenta.
            if ($cupon !== null) {
                $this->cupones->registrarUso($cupon);
            }

            return $pedido->load([
                'sucursal',
                'cupon',
                'detalles.producto',
                'detalles.extras.extra',
            ]);
        });

        // Notificar a los admins de la instancia (fuera de la transaccion: un
        // fallo de notificacion NUNCA debe tumbar la confirmacion del pedido).
        try {
            $this->notificaciones->notificarPedidoNuevo($pedido);
        } catch (\Throwable $e) {
            Log::warning('No se pudo crear la notificacion de pedido nuevo', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $pedido;
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
                'cupon',
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
                'cupon',
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
            'cupon',
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

    /** @return Collection<int, Pedido>|\Illuminate\Contracts\Pagination\LengthAwarePaginator */
    public function listarAdmin(array $filtros, ?int $porPagina = null, int $pagina = 1)
    {
        return $this->pedidos->listarAdmin($filtros, $porPagina, $pagina);
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
