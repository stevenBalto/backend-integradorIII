<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoApiTransport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Transporte de correo por la API HTTP de Brevo (recomendado por Brevo,
        // evita los problemas de certificado/IP del relay SMTP).
        Mail::extend('brevo', function (array $config): BrevoApiTransport {
            return new BrevoApiTransport((string) $config['key']);
        });
    }
}
