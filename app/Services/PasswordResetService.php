<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\RestablecerPasswordMail;
use App\Models\User;
use App\Repositories\SuperAdminRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Flujo "¿Olvidaste tu contraseña?" para AMBAS identidades (users y
 * superadministradores). El token viaja en claro en el correo y se guarda
 * hasheado. Vence en 60 minutos.
 */
final class PasswordResetService
{
    private const VIGENCIA_MINUTOS = 60;

    public function __construct(
        private readonly UserRepository $usuarios,
        private readonly SuperAdminRepository $superadmins,
    ) {
    }

    /**
     * Genera token y envía el correo. NO revela si el email existe o no
     * (siempre "ok" desde afuera) para no filtrar cuentas.
     */
    public function solicitar(string $email): void
    {
        [$actorType, $nombre] = $this->resolverCuenta($email);

        if ($actorType === null) {
            return; // email no existe: silencio (anti enumeración de cuentas)
        }

        $plain = Str::random(64);

        DB::table('password_reset_tokens')->where('email', $email)->delete();
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => Hash::make($plain),
            'actor_type' => $actorType,
            'created_at' => now(),
        ]);

        $url = rtrim((string) config('app.frontend_url'), '/')
            . '/reset-password?token=' . $plain . '&email=' . urlencode($email);

        Mail::to($email)->send(new RestablecerPasswordMail($nombre, $url));
    }

    /**
     * Valida el token y cambia la contraseña de la cuenta correspondiente.
     *
     * @throws ValidationException token inválido/expirado
     */
    public function restablecer(string $email, string $token, string $password): void
    {
        $fila = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->orderByDesc('created_at')
            ->first();

        if ($fila === null || ! Hash::check($token, $fila->token)) {
            throw ValidationException::withMessages([
                'token' => ['El enlace de restablecimiento no es válido.'],
            ]);
        }

        if (Carbon::parse($fila->created_at)->addMinutes(self::VIGENCIA_MINUTOS)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            throw ValidationException::withMessages([
                'token' => ['El enlace de restablecimiento expiró. Solicitá uno nuevo.'],
            ]);
        }

        $this->cambiarPassword($email, (string) $fila->actor_type, $password);

        DB::table('password_reset_tokens')->where('email', $email)->delete();
    }

    /**
     * @return array{0: 'User'|'SuperAdministrador'|null, 1: string}
     */
    private function resolverCuenta(string $email): array
    {
        $user = $this->usuarios->buscarPorEmail($email);
        if ($user !== null) {
            return ['User', $user->nombre];
        }

        $sa = $this->superadmins->buscarPorEmail($email);
        if ($sa !== null) {
            return ['SuperAdministrador', $sa->nombre];
        }

        return [null, ''];
    }

    private function cambiarPassword(string $email, string $actorType, string $password): void
    {
        if ($actorType === 'SuperAdministrador') {
            $sa = $this->superadmins->buscarPorEmail($email);
            $sa?->update(['password' => $password]); // cast 'hashed'

            return;
        }

        $user = $this->usuarios->buscarPorEmail($email);
        if ($user instanceof User) {
            // Al fijar su propia contraseña, deja de ser temporal/obligatoria.
            $user->update([
                'password' => $password,
                'password_temporal' => false,
                'cambio_password_obligatorio' => false,
            ]);
        }
    }
}
