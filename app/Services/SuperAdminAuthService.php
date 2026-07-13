<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\SuperAdmin\CredencialesSuperAdminDTO;
use App\Models\SuperAdministrador;
use App\Repositories\SuperAdminRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Logica de autenticacion del panel de superadministracion (aislada de users).
 */
final class SuperAdminAuthService
{
    public function __construct(
        private readonly SuperAdminRepository $superadmins,
    ) {
    }

    /**
     * Valida credenciales y devuelve el superadmin + token del guard aislado.
     *
     * @return array{superadmin: SuperAdministrador, token: string}
     *
     * @throws ValidationException credenciales invalidas o cuenta inactiva
     */
    public function login(CredencialesSuperAdminDTO $dto): array
    {
        $superadmin = $this->superadmins->buscarPorLogin($dto->login);

        if ($superadmin === null || ! Hash::check($dto->password, $superadmin->password)) {
            throw ValidationException::withMessages([
                'login' => ['Credenciales inválidas.'],
            ]);
        }

        if (! $superadmin->activo) {
            throw ValidationException::withMessages([
                'login' => ['La cuenta está inactiva.'],
            ]);
        }

        $superadmin->forceFill(['ultimo_acceso_en' => now()])->save();

        // Token del guard aislado: nombre 'superadmin' para distinguirlo.
        $token = $superadmin->createToken('superadmin')->plainTextToken;

        return ['superadmin' => $superadmin, 'token' => $token];
    }

    /**
     * Intenta autenticar como superadmin SIN lanzar error si no coincide.
     * Sirve para el login unificado: si devuelve null, el caller sigue con users.
     *
     * @return array{superadmin: SuperAdministrador, token: string}|null
     *
     * @throws ValidationException solo si el superadmin existe pero está inactivo
     */
    public function intentarLogin(string $login, string $password): ?array
    {
        $superadmin = $this->superadmins->buscarPorLogin($login);

        if ($superadmin === null || ! Hash::check($password, $superadmin->password)) {
            return null;
        }

        if (! $superadmin->activo) {
            throw ValidationException::withMessages([
                'email' => ['La cuenta de superadministrador está inactiva.'],
            ]);
        }

        $superadmin->forceFill(['ultimo_acceso_en' => now()])->save();
        $token = $superadmin->createToken('superadmin')->plainTextToken;

        return ['superadmin' => $superadmin, 'token' => $token];
    }

    /** Invalida el token actual del superadmin. */
    public function logout(SuperAdministrador $superadmin): void
    {
        $superadmin->currentAccessToken()?->delete();
    }
}
