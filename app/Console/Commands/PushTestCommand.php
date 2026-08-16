<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/** Manda un push de prueba a un usuario, sin tener que pasar por tinker. */
#[Signature('push:test {email} {--titulo=Prueba de push} {--cuerpo=Esto es una notificacion de prueba.}')]
#[Description('Manda un push de prueba (FCM) a todos los dispositivos registrados de un usuario, por email.')]
class PushTestCommand extends Command
{
    public function handle(PushNotificationService $push): int
    {
        $email = (string) $this->argument('email');
        $usuario = User::where('email', $email)->first();

        if ($usuario === null) {
            $this->error("No existe ningun usuario con el email: {$email}");

            return self::FAILURE;
        }

        $push->enviarAUsuario(
            $usuario->id,
            (string) $this->option('titulo'),
            (string) $this->option('cuerpo'),
        );

        $this->info("Push enviado a {$usuario->email} (revisa storage/logs/laravel.log si no llega).");

        return self::SUCCESS;
    }
}
