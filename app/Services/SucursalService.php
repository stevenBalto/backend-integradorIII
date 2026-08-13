<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Sucursal\ActualizarSucursalDTO;
use App\DTOs\Sucursal\CrearSucursalDTO;
use App\Models\Sucursal;
use App\Models\User;
use App\Repositories\InstanciaRepository;
use App\Repositories\RoleRepository;
use App\Repositories\SucursalRepository;
use App\Repositories\UserRepository;
use App\Services\Concerns\GeneraCredenciales;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Logica de negocio de sucursales (sedes de un mismo negocio).
 *
 * Una sede comparte menu, precios y clientes con el resto de su instancia; lo
 * que NO comparte es la operacion diaria (pedidos y analiticas), que se aisla
 * por `users.sucursal_id` del administrador.
 */
final class SucursalService
{
    use GeneraCredenciales;

    public function __construct(
        private readonly SucursalRepository $sucursales,
        private readonly UserRepository $usuarios,
        private readonly RoleRepository $roles,
        private readonly InstanciaRepository $instancias,
    ) {}

    /**
     * Sucursales de la instancia actual, incluyendo inactivas (el admin las gestiona).
     *
     * @return Collection<int, Sucursal>
     */
    public function listarPropias(): Collection
    {
        return $this->sucursales->listarTodas();
    }

    /**
     * Sedes vistas desde el panel de superadmin.
     *
     * El superadmin no pertenece a ninguna instancia, asi que el global scope
     * de PerteneceAInstancia no filtra nada: devuelve las sedes de todos los
     * negocios, que es justamente lo que ese panel administra.
     *
     * @return Collection<int, Sucursal>
     */
    public function listarTodasParaSuperadmin(): Collection
    {
        return $this->sucursales->listarTodas();
    }

    /**
     * Crea la sede + su administrador con credenciales TEMPORALES (todo o nada).
     *
     * El admin queda atado a la sede por `users.sucursal_id`, que es lo que hace
     * que solo vea los pedidos, clientes y analiticas de SU sede. El menu, los
     * precios y los clientes siguen siendo del negocio (la instancia), no de la sede.
     *
     * @return array{sucursal: Sucursal, credenciales: array{usuario: string, password: string}}
     */
    public function crear(CrearSucursalDTO $dto): array
    {
        $rolAdminId = $this->roles->idPorNombre('admin_sede');
        if ($rolAdminId === null) {
            throw new RuntimeException('Falta el rol admin_sede (corré RolesSeeder).');
        }

        $instanciaId = $this->instanciaDestino();

        return DB::transaction(function () use ($dto, $rolAdminId, $instanciaId): array {
            $sucursal = $this->sucursales->crearParaInstancia($instanciaId, $dto->toArray());
            // `activa` la pone el DEFAULT de la BD: sin refrescar, el modelo en
            // memoria la devolveria como null/false y la respuesta mentiria.
            $sucursal->refresh();

            $usuario = $this->generarUsuarioUnico($dto->nombre);
            $password = $this->generarPassword();

            $this->usuarios->crear([
                'instancia_id' => $sucursal->instancia_id,
                'sucursal_id' => $sucursal->id,
                'role_id' => $rolAdminId,
                'nombre' => 'Administrador '.$dto->nombre,
                'usuario' => $usuario,
                'email' => $dto->correoAdmin,
                'password' => $password, // cast 'hashed'
                'activo' => true,
                'password_temporal' => true,
                'cambio_password_obligatorio' => true,
            ]);

            return [
                'sucursal' => $sucursal,
                // Se muestran UNA sola vez; en BD solo queda el hash.
                'credenciales' => ['usuario' => $usuario, 'password' => $password],
            ];
        });
    }

    /**
     * A que negocio pertenece una sede nueva.
     *
     * Un admin la crea dentro de SU instancia; el superadmin no tiene instancia
     * propia, asi que la sede va al negocio principal (la instancia activa mas
     * antigua). Todas las sedes de una instancia comparten menu y clientes.
     */
    private function instanciaDestino(): int
    {
        $actor = Auth::user();

        if ($actor instanceof User && $actor->instancia_id !== null) {
            return (int) $actor->instancia_id;
        }

        $instancia = $this->instancias->primeraActiva();

        if ($instancia === null) {
            throw ValidationException::withMessages([
                'nombre' => ['No hay ningún negocio activo al que asignar la sede.'],
            ]);
        }

        return (int) $instancia->id;
    }

    /**
     * Cierra o reabre una sede.
     *
     * Cerrar NO borra nada: la sede desaparece del selector del cliente y deja
     * de recibir pedidos, pero su historial queda intacto y sus administradores
     * siguen entrando en modo SOLO LECTURA (ver EnsureSedeAbiertaParaEscribir).
     * Si vuelve a abrir, se reactiva y todo sigue como antes.
     */
    public function cambiarEstado(int $id, bool $abierta): Sucursal
    {
        $sucursal = $this->obtenerOFallar($id);

        return $this->sucursales->cambiarEstado($sucursal, $abierta);
    }

    public function actualizar(int $id, ActualizarSucursalDTO $dto): Sucursal
    {
        return $this->sucursales->actualizar($this->obtenerOFallar($id), $dto->toArray());
    }

    private function obtenerOFallar(int $id): Sucursal
    {
        $sucursal = $this->sucursales->buscarPorId($id);

        if ($sucursal === null) {
            throw ValidationException::withMessages([
                'id' => ['La sede no existe.'],
            ]);
        }

        return $sucursal;
    }
}
