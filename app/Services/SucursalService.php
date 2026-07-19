<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Sucursal\ActualizarSucursalDTO;
use App\DTOs\Sucursal\CrearSucursalDTO;
use App\Models\Sucursal;
use App\Repositories\SucursalRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Logica de negocio de sucursales.
 */
final class SucursalService
{
    public function __construct(
        private readonly SucursalRepository $sucursales,
    ) {
    }

    /**
     * Sucursales de la instancia actual, incluyendo inactivas (el admin las gestiona).
     *
     * @return Collection<int, Sucursal>
     */
    public function listarPropias(): Collection
    {
        return $this->sucursales->listarTodas();
    }

    public function crear(CrearSucursalDTO $dto): Sucursal
    {
        // El instancia_id lo asigna el trait PerteneceAInstancia desde el usuario autenticado.
        return $this->sucursales->crear($dto->toArray());
    }

    public function actualizar(int $id, ActualizarSucursalDTO $dto): Sucursal
    {
        $sucursal = $this->sucursales->buscarPorId($id);

        if ($sucursal === null) {
            throw ValidationException::withMessages([
                'id' => ['La sucursal no existe.'],
            ]);
        }

        return $this->sucursales->actualizar($sucursal, $dto->toArray());
    }
}
