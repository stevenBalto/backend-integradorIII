<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Auth\CredencialesDTO;
use App\DTOs\Auth\RegistrarUsuarioDTO;
use App\Models\User;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Logica de negocio de autenticacion: registro, login y logout con Sanctum.
 */
final class AuthService
{
    public function __construct(
        private readonly UserRepository $usuarios,
        private readonly RoleRepository $roles,
        private readonly NotificacionService $notificaciones,
    ) {
    }

    /**
     * Registra un cliente nuevo y devuelve el usuario + token.
     *
     * @return array{user: User, token: string}
     */
    public function registrar(RegistrarUsuarioDTO $dto): array
    {
        $rolClienteId = $this->roles->idPorNombre('cliente');
        if ($rolClienteId === null) {
            throw new RuntimeException('El rol "cliente" no existe. Ejecuta RolesSeeder.');
        }

        $user = $this->usuarios->crearCliente($dto, $rolClienteId);
        $token = $user->createToken('auth')->plainTextToken;
        $user->load('role');

        // Avisar a los admins de la instancia que se registró un cliente nuevo.
        try {
            $this->notificaciones->notificarClienteNuevo($user);
        } catch (\Throwable $e) {
            Log::warning('No se pudo crear la notificacion de cliente nuevo', ['error' => $e->getMessage()]);
        }

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Valida credenciales y devuelve el usuario + token O indica que debe cambiar password.
     *
     * Si el usuario esta activo, credenciales correctas, pero password vencida
     * (password_expira_en en el pasado o cambio_password_obligatorio=true), NO
     * emite token normal; devuelve debe_cambiar_password=true con motivo.
     *
     * @return array{user: User, token: string}|array{debe_cambiar_password: bool, motivo: string, usuario: string, email: string}
     *
     * @throws ValidationException credenciales invalidas o cuenta inactiva
     */
    public function login(CredencialesDTO $dto): array
    {
        $user = $this->usuarios->buscarPorEmail($dto->email);

        if ($user === null || ! Hash::check($dto->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales inválidas.'],
            ]);
        }

        if (! $user->activo) {
            throw ValidationException::withMessages([
                'email' => ['La cuenta está inactiva.'],
            ]);
        }

        // Aislamiento multi-tenant: si la instancia no está activa, nadie de ella entra.
        $user->load('instancia');
        if ($user->instancia !== null && ! $user->instancia->estaActiva()) {
            throw ValidationException::withMessages([
                'email' => ['La instancia asociada está inactiva. Contactá al administrador.'],
            ]);
        }

        // ITEM 19: Verificar si la password esta vencida o requiere cambio obligatorio.
        // En ese caso NO emitimos token; el frontend debe redirigir al flujo de cambio.
        if ($user->debeCambiarPassword()) {
            $motivo = $this->determinarMotivoExpiracion($user);

            return [
                'debe_cambiar_password' => true,
                'motivo' => $motivo,
                'usuario' => (string) $user->usuario,
                'email' => $user->email,
            ];
        }

        // Login exitoso: crear token y actualizar ultimo_acceso_en.
        $token = $user->createToken('auth')->plainTextToken;
        $user->update(['ultimo_acceso_en' => now()]);
        $user->load('role');

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Cambia la password de un usuario cuya password esta vencida (flujo self-service).
     *
     * @return array{user: User, token: string}
     *
     * @throws ValidationException credenciales invalidas, cuenta inactiva, o password incorrecta
     */
    public function cambiarPasswordExpirada(
        string $login,
        string $passwordActual,
        string $passwordNueva,
    ): array {
        // Buscar por email o por usuario.
        $user = $this->usuarios->buscarPorEmail($login)
            ?? $this->usuarios->buscarPorUsuario($login);

        if ($user === null) {
            throw ValidationException::withMessages([
                'login' => ['Credenciales inválidas.'],
            ]);
        }

        if (! $user->activo) {
            throw ValidationException::withMessages([
                'login' => ['La cuenta está inactiva.'],
            ]);
        }

        if (! Hash::check($passwordActual, $user->password)) {
            throw ValidationException::withMessages([
                'password_actual' => ['La contraseña actual no es correcta.'],
            ]);
        }

        if (Hash::check($passwordNueva, $user->password)) {
            throw ValidationException::withMessages([
                'password_nueva' => ['La nueva contraseña no puede ser igual a la actual.'],
            ]);
        }

        // Recalcular password_expira_en segun dias_expiracion_password del usuario.
        $dias = $user->dias_expiracion_password ?? 30;
        $user->update([
            'password' => $passwordNueva, // cast 'hashed'
            'password_temporal' => false,
            'cambio_password_obligatorio' => false,
            'password_expira_en' => now()->addDays($dias)->toDateString(),
            'ultimo_acceso_en' => now(),
        ]);

        // Emitir token normal.
        $token = $user->createToken('auth')->plainTextToken;
        $user->load('role');

        return ['user' => $user, 'token' => $token];
    }

    /** Invalida el token actual del usuario. */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    /**
     * Determina el motivo por el cual el usuario debe cambiar su password.
     */
    private function determinarMotivoExpiracion(User $user): string
    {
        if ($user->cambio_password_obligatorio) {
            return 'obligatorio';
        }

        if ($user->password_temporal) {
            return 'temporal';
        }

        if ($user->password_expira_en !== null && $user->password_expira_en->isPast()) {
            return 'expirada';
        }

        return 'expirada';
    }
}
