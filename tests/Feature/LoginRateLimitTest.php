<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Limite de intentos en el login (defensa contra fuerza bruta).
 *
 * Son dos capas y se prueban por separado:
 *   1. Middleware `throttle:login` — frena el VOLUMEN de peticiones, acierte o
 *      falle el que las manda.
 *   2. Contador de intentos FALLIDOS en AuthController — mas fino: se borra al
 *      entrar bien, para no castigar a quien se equivoco una vez y despues acerto.
 */
class LoginRateLimitTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Los contadores viven en cache y sobreviven entre tests: sin esto, el
        // segundo test arrancaria con los intentos del primero ya gastados.
        RateLimiter::clear($this->claveFallidos('atacante@rooster.com'));
        RateLimiter::clear($this->claveFallidos('admin@rooster.com'));
    }

    private function claveFallidos(string $email): string
    {
        return 'login-fallidos:'.sha1(mb_strtolower($email).'|127.0.0.1');
    }

    private function intentar(string $email, string $password = 'password-incorrecta')
    {
        return $this->postJson('/api/login', ['email' => $email, 'password' => $password]);
    }

    public function test_bloquea_tras_cinco_intentos_fallidos(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->intentar('atacante@rooster.com')
                ->assertStatus(422, "El intento $i deberia ser credencial invalida, no bloqueo");
        }

        // El sexto ya no llega a comprobar la contrasena: corta antes.
        $respuesta = $this->intentar('atacante@rooster.com');

        $respuesta->assertStatus(429)
            ->assertJsonStructure(['message', 'retry_after']);

        $this->assertStringContainsString(
            'Demasiados intentos',
            (string) $respuesta->json('message'),
            'El mensaje 429 tiene que estar en castellano y ser entendible'
        );
    }

    public function test_un_login_exitoso_borra_los_intentos_fallidos(): void
    {
        // Cuatro fallos: queda uno antes del bloqueo.
        for ($i = 1; $i <= 4; $i++) {
            $this->intentar('admin@rooster.com')->assertStatus(422);
        }

        // Entra bien: el contador se limpia.
        $this->intentar('admin@rooster.com', 'admin123456')->assertStatus(200);

        // Si NO se hubiera limpiado, con dos fallos mas llegariamos al bloqueo.
        // Como se limpio, vuelven a ser credenciales invalidas normales.
        $this->intentar('admin@rooster.com')->assertStatus(422);
        $this->intentar('admin@rooster.com')->assertStatus(422);
    }

    public function test_el_bloqueo_es_por_cuenta_y_no_afecta_a_otro_usuario(): void
    {
        // Un cliente del local machaca su cuenta hasta bloquearse...
        for ($i = 1; $i <= 5; $i++) {
            $this->intentar('atacante@rooster.com')->assertStatus(422);
        }
        $this->intentar('atacante@rooster.com')->assertStatus(429);

        // ...y otro usuario, desde la MISMA IP (wifi del local), sigue entrando.
        // Si el limite fuera solo por IP, este test fallaria.
        $this->intentar('admin@rooster.com', 'admin123456')->assertStatus(200);
    }
}
