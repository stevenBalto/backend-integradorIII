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

    public function __construct(
        private readonly ConfiguracionRepository $configuraciones,
    ) {
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
