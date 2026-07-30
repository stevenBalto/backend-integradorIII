<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Configuracion;
use App\Models\Instancia;
use App\Repositories\ConfiguracionRepository;

/**
 * Ajustes del Home cliente (curacion): que oferta se muestra como "destacada"
 * cuando hay varias vigentes. El resto del contenido del Home (destacados,
 * ofertas y cupones vigentes) se deriva automaticamente de Productos/Ofertas/Cupones.
 */
final class ConfiguracionService
{
    private const CLAVE_OFERTA_HERO = 'home_oferta_hero_id';

    /** Ajustes generales del panel (clave => valor por defecto) por instancia. */
    private const AJUSTES = [
        // Informacion del negocio (copia editable por el admin; la sucursal/instancia
        // las administra el superadmin, por eso aqui se guarda aparte).
        'negocio_nombre' => 'Rooster',
        'negocio_telefono' => '',
        'negocio_direccion' => '',
        'negocio_sitio_web' => '',
        'negocio_maps_url' => '',

        // Operacion: controlan de verdad si se puede pedir y como.
        'pedidos_activos' => '1',
        'modalidad_comer_aqui' => '1',
        'modalidad_para_llevar' => '1',
        'pedido_monto_minimo' => '0',

        // Horario: si `horario_activo`, fuera de rango NO se aceptan pedidos.
        'horario_activo' => '1',
        'horario_apertura' => '11:00',
        'horario_cierre' => '22:00',
        'cerrado_temporalmente' => '0',

        // Programa de Roosters (puntos): antes el 5% estaba quemado en PedidoService.
        'roosters_activo' => '1',
        'roosters_porcentaje' => '5',

        // Reseñas: moderacion previa y umbral para sugerir destacados (antes el
        // umbral estaba quemado en 4 estrellas en el panel de Menu).
        'resenas_moderacion' => '0',
        'resenas_umbral_destacado' => '4',

        // Notificaciones: un interruptor por cada evento del modulo.
        'notif_nuevos_pedidos' => '1',
        'notif_resenas_nuevas' => '1',
        'notif_stock_bajo' => '1',
        'notif_producto_nuevo' => '1',
        'notif_cliente_nuevo' => '1',
        'notif_usuario_nuevo' => '1',
    ];

    /** Claves booleanas (se guardan como '1'/'0'). */
    private const AJUSTES_BOOL = [
        'pedidos_activos',
        'modalidad_comer_aqui',
        'modalidad_para_llevar',
        'horario_activo',
        'cerrado_temporalmente',
        'roosters_activo',
        'resenas_moderacion',
        'notif_nuevos_pedidos',
        'notif_resenas_nuevas',
        'notif_stock_bajo',
        'notif_producto_nuevo',
        'notif_cliente_nuevo',
        'notif_usuario_nuevo',
    ];

    /** Claves numericas (se exponen como number, no string). */
    private const AJUSTES_NUM = [
        'pedido_monto_minimo',
        'roosters_porcentaje',
        'resenas_umbral_destacado',
    ];

    public function __construct(
        private readonly ConfiguracionRepository $configuraciones,
    ) {
    }

    /**
     * Ajustes generales de la instancia actual (con defaults para lo no guardado).
     *
     * @return array<string, mixed>
     */
    public function obtenerAjustes(): array
    {
        $mapa = $this->configuraciones->obtenerMapa();
        $out = [];
        foreach (self::AJUSTES as $clave => $default) {
            $out[$clave] = $mapa[$clave] ?? $default;
        }
        foreach (self::AJUSTES_BOOL as $clave) {
            $out[$clave] = (int) $out[$clave] === 1;
        }
        foreach (self::AJUSTES_NUM as $clave) {
            $out[$clave] = (float) $out[$clave];
        }

        return $out;
    }

    /**
     * Guarda los ajustes recibidos (solo las claves conocidas) y devuelve el estado final.
     *
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    public function guardarAjustes(array $datos): array
    {
        $pares = [];
        foreach (self::AJUSTES as $clave => $default) {
            if (! array_key_exists($clave, $datos)) {
                continue;
            }
            $valor = $datos[$clave];
            if (in_array($clave, self::AJUSTES_BOOL, true)) {
                $valor = filter_var($valor, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            } elseif ($valor !== null) {
                $valor = (string) $valor;
            }
            $pares[$clave] = $valor;
        }
        $this->configuraciones->guardarVarias($pares);

        return $this->obtenerAjustes();
    }

    /**
     * True si una notificacion esta activa para la instancia (por defecto sí).
     * Lo usan los disparadores de notificaciones para respetar Configuracion.
     */
    public function notificacionActiva(int $instanciaId, string $clave): bool
    {
        $valor = $this->configuraciones->valorDeInstancia($instanciaId, $clave);

        return $valor === null ? true : ((int) $valor === 1);
    }

    /**
     * True si la instancia revisa las reseñas antes de publicarlas. Por defecto
     * NO (se publican solas), para no esconder reseñas sin que nadie lo pida.
     */
    public function moderacionResenasActiva(int $instanciaId): bool
    {
        $valor = $this->configuraciones->valorDeInstancia($instanciaId, 'resenas_moderacion');

        return $valor !== null && (int) $valor === 1;
    }

    /**
     * Ajustes de operacion de una instancia ESPECIFICA (sin depender de la sesion):
     * los necesita PedidoService, donde quien pide es el cliente pero las reglas
     * son las del negocio.
     *
     * @return array{
     *     pedidos_activos: bool, cerrado_temporalmente: bool, horario_activo: bool,
     *     horario_apertura: string, horario_cierre: string,
     *     modalidad_comer_aqui: bool, modalidad_para_llevar: bool,
     *     pedido_monto_minimo: float, roosters_activo: bool, roosters_porcentaje: float
     * }
     */
    public function operacionDeInstancia(int $instanciaId): array
    {
        $leer = fn (string $clave) => $this->configuraciones->valorDeInstancia($instanciaId, $clave)
            ?? self::AJUSTES[$clave];
        $bool = fn (string $clave) => (int) $leer($clave) === 1;

        return [
            'pedidos_activos' => $bool('pedidos_activos'),
            'cerrado_temporalmente' => $bool('cerrado_temporalmente'),
            'horario_activo' => $bool('horario_activo'),
            'horario_apertura' => (string) $leer('horario_apertura'),
            'horario_cierre' => (string) $leer('horario_cierre'),
            'modalidad_comer_aqui' => $bool('modalidad_comer_aqui'),
            'modalidad_para_llevar' => $bool('modalidad_para_llevar'),
            'pedido_monto_minimo' => (float) $leer('pedido_monto_minimo'),
            'roosters_activo' => $bool('roosters_activo'),
            'roosters_porcentaje' => (float) $leer('roosters_porcentaje'),
        ];
    }

    /**
     * Lista publica de restaurantes: una entrada por cada instancia ACTIVA,
     * armada con su "Informacion del negocio" (Configuracion). Es la fuente de
     * la pantalla Restaurantes del cliente: al crear una instancia nueva y
     * llenar sus datos, aparece sola — sin tocar codigo.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarRestaurantes(): array
    {
        $instancias = Instancia::query()
            ->where('estado', 'activa')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'correo_principal']);

        if ($instancias->isEmpty()) {
            return [];
        }

        // Una sola consulta para los datos de negocio de todas las instancias
        // (sin el global scope: aqui se listan TODAS, no solo la del usuario).
        $claves = ['negocio_nombre', 'negocio_direccion', 'negocio_telefono', 'negocio_sitio_web', 'negocio_maps_url'];
        $porInstancia = Configuracion::withoutGlobalScope('instancia')
            ->whereIn('instancia_id', $instancias->pluck('id'))
            ->whereIn('clave', $claves)
            ->get(['instancia_id', 'clave', 'valor'])
            ->groupBy('instancia_id')
            ->map(fn ($filas) => $filas->pluck('valor', 'clave'));

        return $instancias->map(function (Instancia $instancia) use ($porInstancia) {
            $cfg = $porInstancia->get($instancia->id, collect());
            $valor = fn (string $clave) => trim((string) ($cfg[$clave] ?? ''));

            return [
                'instancia_id' => $instancia->id,
                // Si la sucursal aun no personalizo el nombre, se usa el de la instancia.
                'nombre' => $valor('negocio_nombre') !== '' ? $valor('negocio_nombre') : $instancia->nombre,
                'direccion' => $valor('negocio_direccion') ?: null,
                'telefono' => $valor('negocio_telefono') ?: null,
                'sitio_web' => $valor('negocio_sitio_web') ?: null,
                'maps_url' => $valor('negocio_maps_url') ?: null,
            ];
        })->values()->all();
    }

    /**
     * Motivo por el que la instancia NO acepta pedidos ahora, o null si sí acepta.
     * Centraliza la regla para que el mensaje al cliente sea claro y consistente.
     */
    public function motivoCierre(array $operacion, ?\DateTimeInterface $ahora = null): ?string
    {
        if (! $operacion['pedidos_activos']) {
            return 'En este momento no estamos recibiendo pedidos. Intentá más tarde.';
        }

        if ($operacion['cerrado_temporalmente']) {
            return 'El restaurante está cerrado temporalmente. Intentá más tarde.';
        }

        if ($operacion['horario_activo']) {
            $apertura = $operacion['horario_apertura'];
            $cierre = $operacion['horario_cierre'];

            if ($apertura !== '' && $cierre !== '' && ! $this->dentroDeHorario($apertura, $cierre, $ahora)) {
                return "Estamos cerrados. Nuestro horario es de {$apertura} a {$cierre}.";
            }
        }

        return null;
    }

    /**
     * True si `$ahora` cae dentro del horario. Soporta horarios que cruzan la
     * medianoche (ej. 18:00 a 02:00).
     */
    private function dentroDeHorario(string $apertura, string $cierre, ?\DateTimeInterface $ahora = null): bool
    {
        $hora = ($ahora ?? now())->format('H:i');

        // Horario normal (11:00 - 22:00): debe estar entre ambos.
        if ($apertura <= $cierre) {
            return $hora >= $apertura && $hora <= $cierre;
        }

        // Horario que cruza medianoche (18:00 - 02:00): vale si es despues de
        // abrir O antes de cerrar.
        return $hora >= $apertura || $hora <= $cierre;
    }

    /** @return array{oferta_hero_id: int|null} */
    public function obtenerHomeConfig(): array
    {
        $config = $this->configuraciones->obtenerPorClave(self::CLAVE_OFERTA_HERO);
        $valor = $config?->valor;

        return ['oferta_hero_id' => $valor !== null ? (int) $valor : null];
    }

    /** @return array{oferta_hero_id: int|null} */
    public function actualizarHomeConfig(?int $ofertaHeroId): array
    {
        $this->configuraciones->guardar(
            self::CLAVE_OFERTA_HERO,
            $ofertaHeroId !== null ? (string) $ofertaHeroId : null,
            'Oferta destacada (hero) en el Home del cliente.',
        );

        return $this->obtenerHomeConfig();
    }
}
