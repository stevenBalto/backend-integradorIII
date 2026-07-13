<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Instancia\ActualizarInstanciaDTO;
use App\DTOs\Instancia\CrearInstanciaDTO;
use App\Models\Instancia;
use App\Repositories\InstanciaRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * CRUD de instancias (solo superadmin). Al crear una instancia genera
 * automaticamente su administrador inicial con credenciales TEMPORALES.
 */
final class InstanciaService
{
    public function __construct(
        private readonly InstanciaRepository $instancias,
        private readonly UserRepository $usuarios,
        private readonly RoleRepository $roles,
    ) {
    }

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

            // Credenciales temporales del admin inicial.
            $usuario = $this->generarUsuarioUnico($dto->nombre);
            $password = $this->generarPassword();

            $this->usuarios->crear([
                'instancia_id' => $instancia->id,
                'role_id' => $rolAdminId,
                'nombre' => 'Administrador ' . $dto->nombre,
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

    /** Deriva un usuario del nombre de la instancia y garantiza que sea unico. */
    private function generarUsuarioUnico(string $nombre): string
    {
        $base = Str::slug($nombre, '');
        $base = $base === '' ? 'admin' : Str::lower(Str::substr($base, 0, 20));

        do {
            $usuario = $base . '_' . random_int(100, 999);
        } while ($this->usuarios->existeUsuario($usuario));

        return $usuario;
    }

    /** Contraseña temporal fuerte (>=12, mayús/minús/número/símbolo). */
    private function generarPassword(): string
    {
        $may = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $min = 'abcdefghijkmnpqrstuvwxyz';
        $num = '23456789';
        $sim = '#$%&*!?@';
        $todos = $may . $min . $num . $sim;

        $pass = $may[random_int(0, strlen($may) - 1)]
            . $min[random_int(0, strlen($min) - 1)]
            . $num[random_int(0, strlen($num) - 1)]
            . $sim[random_int(0, strlen($sim) - 1)];

        for ($i = 0; $i < 10; $i++) {
            $pass .= $todos[random_int(0, strlen($todos) - 1)];
        }

        return str_shuffle($pass);
    }
}
