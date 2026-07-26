<?php

declare(strict_types=1);

namespace App\Services;

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
        'negocio_nombre' => 'Rooster',
        'negocio_telefono' => '',
        'negocio_direccion' => '',
        'negocio_sitio_web' => '',
        'horario_apertura' => '11:00',
        'horario_cierre' => '22:00',
        'iva_porcentaje' => '13',
        'notif_nuevos_pedidos' => '1',
        'notif_resenas_nuevas' => '1',
        'notif_stock_bajo' => '1',
    ];

    /** Claves booleanas (se guardan como '1'/'0'). */
    private const AJUSTES_BOOL = ['notif_nuevos_pedidos', 'notif_resenas_nuevas', 'notif_stock_bajo'];

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
        $out['iva_porcentaje'] = (float) $out['iva_porcentaje'];

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
