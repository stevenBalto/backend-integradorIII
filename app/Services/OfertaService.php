<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Oferta\ActualizarOfertaDTO;
use App\DTOs\Oferta\CrearOfertaDTO;
use App\Models\Oferta;
use App\Repositories\OfertaRepository;
use App\Repositories\ProductoRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Logica de negocio de ofertas.
 */
final class OfertaService
{
    public function __construct(
        private readonly OfertaRepository $ofertas,
        private readonly ProductoRepository $productos,
    ) {
    }

    /** @return Collection<int, Oferta> */
    public function listarTodos(): Collection
    {
        return $this->ofertas->listarTodos();
    }

    /** @return Collection<int, Oferta> */
    public function listarActivas(): Collection
    {
        return $this->ofertas->listarActivas();
    }

    public function buscarPorId(int $id): Oferta
    {
        $oferta = $this->ofertas->buscarPorId($id);

        if ($oferta === null) {
            throw ValidationException::withMessages([
                'id' => ['La oferta no existe.'],
            ]);
        }

        return $oferta;
    }

    /** $imagenUrl: si se subio una imagen nueva a Cloudinary, su secure_url. */
    public function crear(CrearOfertaDTO $dto, ?string $imagenUrl = null): Oferta
    {
        $this->validarFechas($dto->fechaInicio, $dto->fechaFin);
        $this->validarProductosExisten($dto->productoIds);

        $datos = $dto->toArray();
        $datos['imagen_url'] = $imagenUrl;

        return $this->ofertas->crear($datos, $dto->productoIds);
    }

    /** $imagenUrl: si es null, se conserva la imagen actual de la oferta (no se pisa). */
    public function actualizar(int $id, ActualizarOfertaDTO $dto, ?string $imagenUrl = null): Oferta
    {
        $oferta = $this->buscarPorId($id);

        $this->validarFechas($dto->fechaInicio, $dto->fechaFin);
        $this->validarProductosExisten($dto->productoIds);

        $datos = $dto->toArray();
        if ($imagenUrl !== null) {
            $datos['imagen_url'] = $imagenUrl;
        }

        return $this->ofertas->actualizar($oferta, $datos, $dto->productoIds);
    }

    public function eliminar(int $id): void
    {
        $oferta = $this->buscarPorId($id);

        $this->ofertas->eliminar($oferta);
    }

    /**
     * Busca una oferta vigente por id (activa, dentro de fechas). Usado por el
     * canje via QR/pedido de mostrador.
     */
    public function buscarActivaPorId(int $id): ?Oferta
    {
        $oferta = $this->ofertas->buscarPorId($id);

        if ($oferta === null || ! $oferta->activa) {
            return null;
        }

        $hoy = now()->toDateString();

        if ($oferta->fecha_inicio !== null && $oferta->fecha_inicio->toDateString() > $hoy) {
            return null;
        }

        if ($oferta->fecha_fin !== null && $oferta->fecha_fin->toDateString() < $hoy) {
            return null;
        }

        return $oferta;
    }

    /**
     * Calcula el descuento de una oferta sobre los items de un pedido: solo cuenta
     * el subtotal de los productos incluidos en la oferta (los demas items del
     * pedido no se ven afectados).
     *
     * @param array<int, array{producto_id: int, subtotal: float}> $itemsProcesados
     */
    public function calcularDescuento(Oferta $oferta, array $itemsProcesados): float
    {
        $productoIds = $oferta->productos->pluck('id')->all();

        $subtotalElegible = array_reduce(
            $itemsProcesados,
            fn (float $acc, array $item) => in_array($item['producto_id'], $productoIds, true)
                ? $acc + $item['subtotal']
                : $acc,
            0.0,
        );

        if ($subtotalElegible <= 0.0) {
            throw ValidationException::withMessages([
                'oferta_id' => ['Ninguno de los productos del pedido corresponde a esta oferta.'],
            ]);
        }

        $descuento = $oferta->tipo_descuento === 'porcentaje'
            ? $subtotalElegible * ((float) $oferta->valor / 100)
            : (float) $oferta->valor;

        return min($descuento, $subtotalElegible);
    }

    private function validarFechas(?string $fechaInicio, ?string $fechaFin): void
    {
        if ($fechaInicio !== null && $fechaFin !== null && $fechaFin < $fechaInicio) {
            throw ValidationException::withMessages([
                'fecha_fin' => ['La fecha de fin debe ser igual o posterior a la fecha de inicio.'],
            ]);
        }
    }

    /**
     * @param array<int> $productoIds
     */
    private function validarProductosExisten(array $productoIds): void
    {
        if (empty($productoIds)) {
            return;
        }

        foreach ($productoIds as $productoId) {
            if ($this->productos->buscarPorId($productoId) === null) {
                throw ValidationException::withMessages([
                    'producto_ids' => ["El producto con ID {$productoId} no existe."],
                ]);
            }
        }
    }
}
