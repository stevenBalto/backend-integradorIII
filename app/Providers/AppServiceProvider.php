<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
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

        $this->registrarLimitesDePeticiones();
    }

    /**
     * Limites de peticiones (rate limiting) de las puertas de entrada.
     *
     * Sin esto, un atacante puede probar contrasenas a la velocidad que aguante
     * el servidor. Con un limite, un ataque de fuerza bruta pasa de horas a
     * anios, que es todo el punto.
     *
     * Se usan DOS cubetas por peticion (Laravel aplica la mas restrictiva):
     *   - por email+IP: frena a quien machaca UNA cuenta.
     *   - por IP sola:  frena a quien prueba MUCHAS cuentas desde el mismo lado
     *                   (rociado de contrasenas), que la primera cubeta no ve.
     *
     * La IP sola nunca alcanza como unica defensa: en el local todos salen por
     * la misma IP publica, asi que un limite bajo por IP echaria a usuarios
     * legitimos. Por eso la cubeta fina es email+IP y la de IP es mas holgada.
     *
     * Estos numeros son a proposito MAS ALTOS que los 5 intentos fallidos que
     * cuenta AuthController: si fueran iguales, el middleware cortaria primero y
     * el contador fino nunca actuaria — y con el se pierde lo importante, que es
     * distinguir el fallo del acierto (el middleware cuenta las dos cosas por
     * igual). Aca: el middleware frena inundaciones; el contador fino frena la
     * fuerza bruta.
     */
    private function registrarLimitesDePeticiones(): void
    {
        RateLimiter::for('login', function (Request $request): array {
            $email = strtolower((string) $request->input('email'));

            return [
                Limit::perMinute(10)->by('login:'.sha1($email.'|'.$request->ip()))
                    ->response($this->respuesta429(...)),
                Limit::perMinute(30)->by('login-ip:'.$request->ip())
                    ->response($this->respuesta429(...)),
            ];
        });

        // Registro: evita que alguien llene la tabla de usuarios con cuentas
        // basura. Es por IP porque todavia no hay identidad que limitar.
        RateLimiter::for('registro', fn (Request $request) => Limit::perMinute(5)
            ->by('registro:'.$request->ip())
            ->response($this->respuesta429(...)));
    }

    /**
     * Respuesta 429 en castellano y con los segundos que faltan.
     *
     * Por defecto Laravel responde "Too Many Attempts." en ingles y sin contexto;
     * el frontend muestra `message` tal cual, asi que el usuario veria eso.
     */
    private function respuesta429(Request $request, array $headers)
    {
        $segundos = (int) ($headers['Retry-After'] ?? 60);

        return response()->json([
            'message' => "Demasiados intentos. Espera {$segundos} segundos y volve a probar.",
            'retry_after' => $segundos,
        ], 429, $headers);
    }
}
