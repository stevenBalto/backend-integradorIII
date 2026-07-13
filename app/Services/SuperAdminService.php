<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\SuperAdmin\ActualizarSuperAdminDTO;
use App\DTOs\SuperAdmin\CrearSuperAdminDTO;
use App\Models\SuperAdministrador;
use App\Repositories\SuperAdminRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * CRUD de superadministradores. Solo un superadmin autenticado opera aqui
 * (la ruta ya lo garantiza con el middleware `superadmin`).
 */
final class SuperAdminService
{
    public function __construct(
        private readonly SuperAdminRepository $superadmins,
    ) {
    }

    /** @return Collection<int, SuperAdministrador> */
    public function listar(): Collection
    {
        return $this->superadmins->listar();
    }

    public function crear(CrearSuperAdminDTO $dto): SuperAdministrador
    {
        return $this->superadmins->crear($dto);
    }

    public function actualizar(int $id, ActualizarSuperAdminDTO $dto): SuperAdministrador
    {
        $superadmin = $this->obtenerOFallar($id);
        $superadmin->update($dto->soloDefinidos());

        return $superadmin;
    }

    /** Cambia (resetea) la contraseña de un superadmin. */
    public function resetPassword(int $id, string $nuevaPassword): SuperAdministrador
    {
        $superadmin = $this->obtenerOFallar($id);
        $superadmin->update(['password' => $nuevaPassword]); // cast 'hashed'

        return $superadmin;
    }

    /**
     * Desactiva un superadmin. No permite auto-desactivarse (evita quedar sin acceso).
     */
    public function desactivar(int $id, int $actorId): SuperAdministrador
    {
        $this->assertNoEsUnoMismo($id, $actorId, 'desactivar');
        $superadmin = $this->obtenerOFallar($id);
        $superadmin->update(['activo' => false]);

        return $superadmin;
    }

    /**
     * Elimina (soft delete) un superadmin. No permite auto-eliminarse.
     */
    public function eliminar(int $id, int $actorId): void
    {
        $this->assertNoEsUnoMismo($id, $actorId, 'eliminar');
        $this->obtenerOFallar($id)->delete();
    }

    private function obtenerOFallar(int $id): SuperAdministrador
    {
        $superadmin = $this->superadmins->buscarPorId($id);

        if ($superadmin === null) {
            throw ValidationException::withMessages([
                'id' => ['El superadministrador no existe.'],
            ]);
        }

        return $superadmin;
    }

    private function assertNoEsUnoMismo(int $id, int $actorId, string $accion): void
    {
        if ($id === $actorId) {
            throw ValidationException::withMessages([
                'id' => ["No podés {$accion} tu propia cuenta de superadministrador."],
            ]);
        }
    }
}
