<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Cupon\ActualizarCuponDTO;
use App\DTOs\Cupon\CrearCuponDTO;
use App\Models\Cupon;
use App\Repositories\CuponRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Logica de negocio de cupones.
 */
final class CuponService
{
    public function __construct(
        private readonly CuponRepository $cupones,
    ) {
    }

    /** @return Collection<int, Cupon> */
    public function listarTodos(): Collection
    {
        return $this->cupones->listarTodos();
    }

    /** @return Collection<int, Cupon> */
    public function listarActivos(?int $clienteId = null): Collection
    {
        return $this->cupones->listarActivos($clienteId);
    }

    public function buscarPorId(int $id): Cupon
    {
        $cupon = $this->cupones->buscarPorId($id);

        if ($cupon === null) {
            throw ValidationException::withMessages([
                'id' => ['El cupon no existe.'],
            ]);
        }

        return $cupon;
    }

    /** $imagenUrl: si se subio una imagen nueva a Cloudinary, su secure_url. */
    public function crear(CrearCuponDTO $dto, ?string $imagenUrl = null): Cupon
    {
        $this->validarFechas($dto->fechaInicio, $dto->fechaFin);
        $this->validarCodigoUnico($dto->codigo);

        $datos = $dto->toArray();
        $datos['imagen_url'] = $imagenUrl;

        return $this->cupones->crear($datos, $dto->clienteIds);
    }

    /** $imagenUrl: si es null, se conserva la imagen actual del cupon (no se pisa). */
    public function actualizar(int $id, ActualizarCuponDTO $dto, ?string $imagenUrl = null): Cupon
    {
        $cupon = $this->buscarPorId($id);

        $this->validarFechas($dto->fechaInicio, $dto->fechaFin);
        $this->validarCodigoUnico($dto->codigo, $cupon->id);

        $datos = $dto->toArray();
        if ($imagenUrl !== null) {
            $datos['imagen_url'] = $imagenUrl;
        }

        return $this->cupones->actualizar($cupon, $datos, $dto->clienteIds);
    }

    public function eliminar(int $id): void
    {
        $cupon = $this->buscarPorId($id);

        $this->cupones->eliminar($cupon);
    }

    /**
     * Busca un cupon vigente por codigo (activo, dentro de fechas, con usos disponibles).
     * Usado por el canje via QR/pedido de mostrador y por el checkout normal.
     */
    public function buscarActivoPorCodigo(string $codigo, ?int $clienteId = null): ?Cupon
    {
        return $this->cupones->buscarActivoPorCodigo($codigo, $clienteId);
    }

    /**
     * Calcula el descuento de un cupon sobre un subtotal, validando el monto minimo.
     * Porcentaje o monto fijo, siempre topado al subtotal (nunca deja el total en negativo).
     */
    public function calcularDescuento(Cupon $cupon, float $subtotal): float
    {
        if ($cupon->monto_minimo !== null && $subtotal < (float) $cupon->monto_minimo) {
            throw ValidationException::withMessages([
                'cupon_codigo' => ["Este cupón requiere una compra mínima de ₡{$cupon->monto_minimo}."],
            ]);
        }

        $descuento = $cupon->tipo === 'porcentaje'
            ? $subtotal * ((float) $cupon->valor / 100)
            : (float) $cupon->valor;

        return min($descuento, $subtotal);
    }

    /** Marca el cupon como usado (incrementa el contador de canjes). */
    public function registrarUso(Cupon $cupon): void
    {
        $this->cupones->incrementarUso($cupon);
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
     * Valida unicidad de codigo (capa extra de seguridad sobre el UNIQUE de BD).
     */
    private function validarCodigoUnico(string $codigo, ?int $ignorarId = null): void
    {
        $existente = $this->cupones->buscarPorCodigo($codigo);

        if ($existente !== null && $existente->id !== $ignorarId) {
            throw ValidationException::withMessages([
                'codigo' => ['El codigo del cupon ya esta en uso.'],
            ]);
        }
    }
}
