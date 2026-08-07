<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Resolucion del origen en el login con Google.
 *
 * Nace de un problema real: un compañero elegia su cuenta de Google y al volver
 * le salia ERR_CONNECTION_REFUSED. Causa: su front corria en un puerto que el
 * backend no tenia permitido, y el backend caia EN SILENCIO al origen del .env,
 * mandandolo a un puerto donde no habia nada escuchando.
 */
class GoogleAuthOrigenTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'test-client-id.apps.googleusercontent.com',
            'services.google.client_secret' => 'test-secret',
            'services.google.origins' => ['http://localhost:4200', 'http://localhost:8100'],
            'services.google.frontend_url' => 'http://localhost:4200',
        ]);
    }

    public function test_usa_el_origen_que_manda_el_front_si_esta_permitido(): void
    {
        $respuesta = $this->get('/api/auth/google/redirect?origen='.urlencode('http://localhost:8100'));

        $respuesta->assertRedirect();
        $destino = $respuesta->headers->get('Location');

        $this->assertStringContainsString('accounts.google.com', (string) $destino);
        $this->assertStringContainsString(
            urlencode('http://localhost:8100/api/auth/google/callback'),
            (string) $destino,
            'El redirect_uri tiene que armarse con el puerto del que pidio, no con el del .env'
        );
    }

    public function test_un_origen_no_permitido_falla_con_mensaje_claro_en_vez_de_redirigir(): void
    {
        $respuesta = $this->get('/api/auth/google/redirect?origen='.urlencode('http://localhost:9999'));

        // Lo importante: NO redirige a Google. Si redirigiera, el usuario elegiria
        // su cuenta y recien al volver descubriria que no hay nadie escuchando.
        $respuesta->assertStatus(400);
        $respuesta->assertSee('http://localhost:9999', false);
        $respuesta->assertSee('GOOGLE_ALLOWED_ORIGINS', false);
    }

    public function test_si_el_front_no_manda_origen_se_deduce_del_referer(): void
    {
        // Caso del compañero con el frontend desactualizado: su version todavia no
        // manda `origen`, pero el navegador si manda el Referer.
        $respuesta = $this->get('/api/auth/google/redirect', [
            'referer' => 'http://localhost:8100/login',
        ]);

        $respuesta->assertRedirect();
        $this->assertStringContainsString(
            urlencode('http://localhost:8100/api/auth/google/callback'),
            (string) $respuesta->headers->get('Location'),
        );
    }

    public function test_sin_origen_ni_referer_usa_el_del_env(): void
    {
        $respuesta = $this->get('/api/auth/google/redirect');

        $respuesta->assertRedirect();
        $this->assertStringContainsString(
            urlencode('http://localhost:4200/api/auth/google/callback'),
            (string) $respuesta->headers->get('Location'),
        );
    }
}
