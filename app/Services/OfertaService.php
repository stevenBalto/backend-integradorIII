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

    public function crear(CrearOfertaDTO $dto): Oferta
    {
        $this->validarFechas($dto->fechaInicio, $dto->fechaFin);
        $this->validarProductosExisten($dto->productoIds);

        return $this->ofertas->crear($dto->toArray(), $dto->productoIds);
    }

    public function actualizar(int $id, ActualizarOfertaDTO $dto): Oferta
    {
        $oferta = $this->buscarPorId($id);

        $this->validarFechas($dto->fechaInicio, $dto->fechaFin);
        $this->validarProductosExisten($dto->productoIds);

        return $this->ofertas->actualizar($oferta, $dto->toArray(), $dto->productoIds);
    }

    public function eliminar(int $id): void
    {
        $oferta = $this->buscarPorId($id);

        $this->ofertas->eliminar($oferta);
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
