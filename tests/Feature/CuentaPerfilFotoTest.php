<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\UsuarioFoto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Edicion del perfil propio y foto de perfil privada.
 *
 * Reglas que se cubren:
 *   - Telefono y correo se editan; el saldo de Roosters NO.
 *   - Cambiar el correo exige la contrasena actual (el correo ES el login).
 *   - Una cuenta creada con Google no puede cambiar su correo.
 *   - La foto vive en BD y solo la ve su dueno autenticado.
 */
class CuentaPerfilFotoTest extends TestCase
{
    use DatabaseTransactions;

    private const INSTANCIA = 1;

    private const PASSWORD = 'Rooster2026';

    // ── Perfil ────────────────────────────────────────────────────────────

    public function test_cliente_actualiza_su_telefono(): void
    {
        $user = $this->crearCliente();
        Sanctum::actingAs($user);

        $this->putJson('/api/cuenta/perfil', ['telefono' => '8888-9999'])
            ->assertStatus(200)
            ->assertJsonPath('data.telefono', '8888-9999');

        $this->assertSame('8888-9999', $user->fresh()->telefono);
    }

    public function test_telefono_vacio_se_guarda_como_null(): void
    {
        $user = $this->crearCliente(['telefono' => '8888-1111']);
        Sanctum::actingAs($user);

        $this->putJson('/api/cuenta/perfil', ['telefono' => ''])->assertStatus(200);

        $this->assertNull($user->fresh()->telefono);
    }

    public function test_cambia_el_correo_con_la_password_correcta(): void
    {
        $user = $this->crearCliente();
        Sanctum::actingAs($user);

        $nuevo = 'nuevo'.uniqid().'@rooster.com';

        $this->putJson('/api/cuenta/perfil', [
            'email' => $nuevo,
            'password_actual' => self::PASSWORD,
        ])->assertStatus(200)->assertJsonPath('data.email', $nuevo);

        $this->assertSame($nuevo, $user->fresh()->email);
    }

    public function test_no_cambia_el_correo_sin_la_password(): void
    {
        $user = $this->crearCliente();
        $original = $user->email;
        Sanctum::actingAs($user);

        $this->putJson('/api/cuenta/perfil', ['email' => 'otro'.uniqid().'@rooster.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password_actual');

        $this->assertSame($original, $user->fresh()->email);
    }

    public function test_no_cambia_el_correo_con_password_incorrecta(): void
    {
        $user = $this->crearCliente();
        $original = $user->email;
        Sanctum::actingAs($user);

        $this->putJson('/api/cuenta/perfil', [
            'email' => 'otro'.uniqid().'@rooster.com',
            'password_actual' => 'la-que-no-es',
        ])->assertStatus(422)->assertJsonValidationErrors('password_actual');

        $this->assertSame($original, $user->fresh()->email);
    }

    public function test_cuenta_de_google_no_puede_cambiar_su_correo(): void
    {
        $user = $this->crearCliente(['google_id' => 'g-'.uniqid()]);
        $original = $user->email;
        Sanctum::actingAs($user);

        $this->putJson('/api/cuenta/perfil', [
            'email' => 'otro'.uniqid().'@rooster.com',
            'password_actual' => self::PASSWORD,
        ])->assertStatus(422)->assertJsonValidationErrors('email');

        $this->assertSame($original, $user->fresh()->email);
    }

    public function test_cuenta_de_google_si_puede_cambiar_su_telefono(): void
    {
        $user = $this->crearCliente(['google_id' => 'g-'.uniqid()]);
        Sanctum::actingAs($user);

        $this->putJson('/api/cuenta/perfil', ['telefono' => '7000-0000'])->assertStatus(200);

        $this->assertSame('7000-0000', $user->fresh()->telefono);
    }

    public function test_no_se_puede_editar_el_saldo_de_roosters(): void
    {
        $user = $this->crearCliente();
        $user->forceFill(['puntos_balance' => 500])->save();
        Sanctum::actingAs($user);

        $this->putJson('/api/cuenta/perfil', [
            'telefono' => '6000-0000',
            'puntos_balance' => 999999,
        ])->assertStatus(200);

        $this->assertSame(500, $user->fresh()->puntos_balance);
    }

    public function test_rechaza_un_correo_ya_usado_por_otra_cuenta(): void
    {
        $otro = $this->crearCliente();
        $user = $this->crearCliente();
        Sanctum::actingAs($user);

        $this->putJson('/api/cuenta/perfil', [
            'email' => $otro->email,
            'password_actual' => self::PASSWORD,
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_me_expone_es_google_y_tiene_foto(): void
    {
        $user = $this->crearCliente();
        Sanctum::actingAs($user);

        $this->getJson('/api/me')
            ->assertStatus(200)
            ->assertJsonPath('data.es_google', false)
            ->assertJsonPath('data.tiene_foto', false);
    }

    public function test_me_no_expone_el_google_id(): void
    {
        $user = $this->crearCliente(['google_id' => 'g-'.uniqid()]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/me')->assertStatus(200);

        $this->assertArrayNotHasKey('google_id', $response->json('data'));
        $this->assertTrue($response->json('data.es_google'));
    }

    // ── Foto ──────────────────────────────────────────────────────────────

    public function test_sube_la_foto_y_la_devuelve_a_su_dueno(): void
    {
        $user = $this->crearCliente();
        Sanctum::actingAs($user);

        $this->subirFoto(UploadedFile::fake()->image('yo.jpg', 900, 600))->assertStatus(200);

        $foto = UsuarioFoto::query()->find($user->id);
        $this->assertNotNull($foto);
        $this->assertSame('image/jpeg', $foto->mime);
        $this->assertGreaterThan(0, $foto->tamano_bytes);

        $response = $this->get('/api/cuenta/foto')->assertStatus(200);
        $this->assertSame('image/jpeg', $response->headers->get('Content-Type'));
        // Cache privada: ningun proxy compartido debe guardar la cara de alguien.
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
    }

    public function test_la_foto_se_normaliza_a_512_cuadrada(): void
    {
        $user = $this->crearCliente();
        Sanctum::actingAs($user);

        $this->subirFoto(UploadedFile::fake()->image('ancha.jpg', 1600, 400))->assertStatus(200);

        $binario = $this->contenidoDeFoto($user->id);
        $medidas = getimagesizefromstring($binario);

        $this->assertSame(512, $medidas[0]);
        $this->assertSame(512, $medidas[1]);
    }

    public function test_subir_otra_foto_reemplaza_la_anterior_sin_duplicar(): void
    {
        $user = $this->crearCliente();
        Sanctum::actingAs($user);

        $this->subirFoto(UploadedFile::fake()->image('a.jpg', 300, 300))->assertStatus(200);
        $this->subirFoto(UploadedFile::fake()->image('b.jpg', 400, 400))->assertStatus(200);

        $this->assertSame(1, UsuarioFoto::query()->where('user_id', $user->id)->count());
    }

    public function test_cada_usuario_recibe_su_propia_foto_y_no_la_de_otro(): void
    {
        $ana = $this->crearCliente();
        $beto = $this->crearCliente();

        // Imagenes de colores distintos a proposito: las de UploadedFile::fake()
        // son rectangulos planos y, ya normalizadas a 512x512, salen byte a byte
        // identicas -> el assert de aislamiento no probaria nada.
        Sanctum::actingAs($ana);
        $this->subirFoto($this->imagenDeColor('ana.jpg', 220, 20, 40))->assertStatus(200);

        Sanctum::actingAs($beto);
        $this->subirFoto($this->imagenDeColor('beto.jpg', 20, 90, 220))->assertStatus(200);

        // El endpoint resuelve por el token, no por un id de la URL: cada quien
        // solo puede nombrar su propia foto.
        $deBeto = $this->get('/api/cuenta/foto')->assertStatus(200)->getContent();

        Sanctum::actingAs($ana);
        $deAna = $this->get('/api/cuenta/foto')->assertStatus(200)->getContent();

        $this->assertNotSame($deAna, $deBeto);
        $this->assertSame($this->contenidoDeFoto($ana->id), $deAna);
    }

    public function test_sin_sesion_no_se_puede_ver_ninguna_foto(): void
    {
        $this->getJson('/api/cuenta/foto')->assertStatus(401);
        $this->postJson('/api/cuenta/foto', [])->assertStatus(401);
        $this->deleteJson('/api/cuenta/foto')->assertStatus(401);
    }

    public function test_devuelve_404_cuando_el_usuario_no_tiene_foto(): void
    {
        Sanctum::actingAs($this->crearCliente());

        $this->getJson('/api/cuenta/foto')->assertStatus(404);
    }

    public function test_rechaza_un_archivo_que_no_es_imagen(): void
    {
        Sanctum::actingAs($this->crearCliente());

        $this->subirFoto(UploadedFile::fake()->create('virus.pdf', 100, 'application/pdf'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('foto');
    }

    public function test_elimina_la_foto(): void
    {
        $user = $this->crearCliente();
        Sanctum::actingAs($user);

        $this->subirFoto(UploadedFile::fake()->image('x.jpg', 300, 300))->assertStatus(200);

        $this->deleteJson('/api/cuenta/foto')->assertStatus(200);

        $this->assertNull(UsuarioFoto::query()->find($user->id));
        $this->getJson('/api/cuenta/foto')->assertStatus(404);
    }

    public function test_me_reporta_tiene_foto_en_true_cuando_hay_foto(): void
    {
        Sanctum::actingAs($this->crearCliente());

        $this->subirFoto(UploadedFile::fake()->image('x.jpg', 300, 300))->assertStatus(200);

        $this->getJson('/api/me')->assertStatus(200)->assertJsonPath('data.tiene_foto', true);
    }

    public function test_borrar_el_usuario_se_lleva_su_foto(): void
    {
        $user = $this->crearCliente();
        Sanctum::actingAs($user);

        $this->subirFoto(UploadedFile::fake()->image('x.jpg', 300, 300))->assertStatus(200);

        // forceDelete: el soft delete no dispara el ON DELETE CASCADE de Postgres.
        $user->forceDelete();

        $this->assertNull(UsuarioFoto::query()->find($user->id));
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Sube la foto por multipart.
     *
     * No se usa postJson: JSON no puede transportar binario y el cuerpo del
     * archivo revienta con "Malformed UTF-8". El header Accept se manda igual
     * para que los errores de validacion vuelvan como JSON y no como redirect.
     */
    private function subirFoto(UploadedFile $archivo): TestResponse
    {
        return $this->post('/api/cuenta/foto', ['foto' => $archivo], ['Accept' => 'application/json']);
    }

    /** JPEG de un color solido, para tener imagenes distinguibles entre si. */
    private function imagenDeColor(string $nombre, int $r, int $g, int $b, int $lado = 400): UploadedFile
    {
        $img = imagecreatetruecolor($lado, $lado);
        imagefilledrectangle($img, 0, 0, $lado, $lado, imagecolorallocate($img, $r, $g, $b));

        $ruta = tempnam(sys_get_temp_dir(), 'foto').'.jpg';
        imagejpeg($img, $ruta);
        imagedestroy($img);

        // El ultimo true = modo test: salta la comprobacion de is_uploaded_file.
        return new UploadedFile($ruta, $nombre, 'image/jpeg', null, true);
    }

    /** @param  array<string, mixed>  $extra */
    private function crearCliente(array $extra = []): User
    {
        $rol = Role::query()->where('nombre', 'cliente')->firstOrFail();
        $sufijo = uniqid();

        return User::query()->create(array_merge([
            'role_id' => $rol->id,
            'instancia_id' => self::INSTANCIA,
            'nombre' => 'Cliente Prueba',
            'usuario' => 'cliente.'.$sufijo,
            'email' => 'cliente.'.$sufijo.'@rooster.test',
            'password' => self::PASSWORD,
            'activo' => true,
            'password_temporal' => false,
            'cambio_password_obligatorio' => false,
        ], $extra));
    }

    /** Binario de la foto (el cast del modelo ya resuelve el stream del driver). */
    private function contenidoDeFoto(int $userId): string
    {
        return UsuarioFoto::query()->findOrFail($userId)->contenido;
    }
}
