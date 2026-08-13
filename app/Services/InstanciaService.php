<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Instancia\ActualizarInstanciaDTO;
use App\DTOs\Instancia\CrearInstanciaDTO;
use App\Models\Instancia;
use App\Repositories\InstanciaRepository;
use App\Repositories\RoleRepository;
use App\Repositories\SucursalRepository;
use App\Repositories\UserRepository;
use App\Services\Concerns\GeneraCredenciales;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * CRUD de instancias (solo superadmin). Al crear una instancia genera
 * automaticamente su administrador inicial con credenciales TEMPORALES.
 */
final class InstanciaService
{
    use GeneraCredenciales;

    public function __construct(
        private readonly InstanciaRepository $instancias,
        private readonly UserRepository $usuarios,
        private readonly RoleRepository $roles,
        private readonly SucursalRepository $sucursales,
    ) {}

    /** @return Collection<int, Instancia> */
    public function listar(): Collection
    {
        return $this->instancias->listar();
    }

    /**
     * Crea la instancia + su admin inicial temporal (todo o nada).
     *
     * @return array{instancia: Instancia, credenciales: array{usuario: string, password: string}}
     */
    public function crear(CrearInstanciaDTO $dto, int $superadminId): array
    {
        $rolAdminId = $this->roles->idPorNombre('super_admin');
        if ($rolAdminId === null) {
            throw new RuntimeException('Falta el rol super_admin (corré RolesSeeder).');
        }

        return DB::transaction(function () use ($dto, $superadminId, $rolAdminId): array {
            $instancia = $this->instancias->crear([
                'nombre' => $dto->nombre,
                'correo_principal' => $dto->correoPrincipal,
                'estado' => 'activa',
                'creada_por' => $superadminId,
            ]);

            // Sede inicial de la instancia. Sin ella, el negocio no aparece en el
            // selector del cliente (que lee `sucursales`) y no puede recibir pedidos.
            // La direccion real la completa el admin desde Configuracion.
            $this->sucursales->crearParaInstancia($instancia->id, [
                'nombre' => $dto->nombre,
                'direccion' => 'Pendiente de completar',
            ]);

            // Credenciales temporales del admin inicial.
            $usuario = $this->generarUsuarioUnico($dto->nombre);
            $password = $this->generarPassword();

            $this->usuarios->crear([
                'instancia_id' => $instancia->id,
                'role_id' => $rolAdminId,
                'nombre' => 'Administrador '.$dto->nombre,
                'usuario' => $usuario,
                'email' => $dto->correoPrincipal,
                'password' => $password, // cast 'hashed'
                'activo' => true,
                'password_temporal' => true,
                'cambio_password_obligatorio' => true,
            ]);

            $instancia->loadCount('users');

            return [
                'instancia' => $instancia,
                // Se muestran UNA sola vez; en BD solo queda el hash.
                'credenciales' => ['usuario' => $usuario, 'password' => $password],
            ];
        });
    }

    public function actualizar(int $id, ActualizarInstanciaDTO $dto): Instancia
    {
        $instancia = $this->obtenerOFallar($id);

        return $this->instancias->actualizar($instancia, $dto->soloDefinidos());
    }

    public function cambiarEstado(int $id, string $estado): Instancia
    {
        if (! in_array($estado, ['activa', 'inactiva', 'suspendida'], true)) {
            throw ValidationException::withMessages([
                'estado' => ['Estado no válido.'],
            ]);
        }

        $instancia = $this->obtenerOFallar($id);

        return $this->instancias->actualizar($instancia, ['estado' => $estado]);
    }

    public function eliminar(int $id): void
    {
        $this->instancias->eliminar($this->obtenerOFallar($id));
    }

    private function obtenerOFallar(int $id): Instancia
    {
        $instancia = $this->instancias->buscarPorId($id);

        if ($instancia === null) {
            throw ValidationException::withMessages([
                'id' => ['La instancia no existe.'],
            ]);
        }

        return $instancia;
    }
}
