<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\Role;
use App\Models\Sucursal;
use App\Models\SuperAdministrador;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Sedes (sucursales) de un mismo negocio: alta con su administrador y
 * aislamiento de la operacion diaria entre sedes.
 *
 * La regla que se protege aca: un admin_sede ve UNICAMENTE los pedidos de su
 * sede, y el filtro vive en el servidor (global scope), no en el request.
 */
class SedeAislamientoTest extends TestCase
{
    use DatabaseTransactions;

    private const INSTANCIA = 1;

    public function test_admin_de_sede_solo_ve_los_pedidos_de_su_sede(): void
    {
        $sedePropia = $this->crearSede('Sede Propia Test');
        $sedeAjena = $this->crearSede('Sede Ajena Test');

        $pedidoPropio = $this->crearPedido($sedePropia, 'P'.substr(uniqid(), -9));
        $pedidoAjeno = $this->crearPedido($sedeAjena, 'A'.substr(uniqid(), -9));

        Sanctum::actingAs($this->crearAdminDeSede($sedePropia));

        $response = $this->getJson('/api/admin/pedidos');

        $response->assertStatus(200);
        $codigos = array_column($response->json('data'), 'codigo');

        $this->assertContains($pedidoPropio->codigo, $codigos);
        $this->assertNotContains($pedidoAjeno->codigo, $codigos);
    }

    public function test_admin_general_ve_los_pedidos_de_todas_las_sedes(): void
    {
        $sedeA = $this->crearSede('Sede A Test');
        $sedeB = $this->crearSede('Sede B Test');

        $pedidoA = $this->crearPedido($sedeA, 'X'.substr(uniqid(), -9));
        $pedidoB = $this->crearPedido($sedeB, 'Y'.substr(uniqid(), -9));

        // El admin general es el que NO tiene sucursal asignada.
        Sanctum::actingAs(User::where('email', 'admin@rooster.com')->firstOrFail());

        $response = $this->getJson('/api/admin/pedidos');

        $response->assertStatus(200);
        $codigos = array_column($response->json('data'), 'codigo');

        $this->assertContains($pedidoA->codigo, $codigos);
        $this->assertContains($pedidoB->codigo, $codigos);
    }

    public function test_admin_de_sede_no_puede_ver_otra_sede_pidiendola_en_el_request(): void
    {
        $sedePropia = $this->crearSede('Sede Filtro Propia');
        $sedeAjena = $this->crearSede('Sede Filtro Ajena');
        $pedidoAjeno = $this->crearPedido($sedeAjena, 'F'.substr(uniqid(), -9));

        Sanctum::actingAs($this->crearAdminDeSede($sedePropia));

        // Aunque pida explicitamente la sede ajena, el scope manda.
        $response = $this->getJson('/api/admin/pedidos?sucursal_id='.$sedeAjena->id);

        $response->assertStatus(200);
        $this->assertNotContains(
            $pedidoAjeno->codigo,
            array_column($response->json('data'), 'codigo'),
        );
    }

    public function test_crear_sede_devuelve_credenciales_y_deja_al_admin_atado_a_ella(): void
    {
        // El alta de sedes vive en el panel de superadmin, no en el de admin.
        Sanctum::actingAs(SuperAdministrador::firstOrFail());

        $correo = 'sede.test.'.uniqid().'@example.com';

        $response = $this->postJson('/api/superadmin/sedes', [
            'nombre' => 'Sede Con Admin Test',
            'direccion' => 'Direccion de prueba 123',
            'correo_admin' => $correo,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'nombre'], 'credenciales' => ['usuario', 'password']]);

        $adminNuevo = User::where('email', $correo)->firstOrFail();

        $this->assertSame($response->json('data.id'), $adminNuevo->sucursal_id);
        $this->assertTrue($adminNuevo->esAdminSede());
        // Nace con password temporal: debe cambiarla al primer ingreso.
        $this->assertTrue((bool) $adminNuevo->cambio_password_obligatorio);
    }

    public function test_editar_los_datos_de_la_sede_no_cambia_si_esta_abierta(): void
    {
        Sanctum::actingAs(SuperAdministrador::firstOrFail());

        $sede = $this->crearSede('Sede Siempre Activa');

        // El cierre tiene su propio endpoint: no se cuela por la edicion de datos.
        $this->putJson('/api/superadmin/sedes/'.$sede->id, [
            'nombre' => $sede->nombre,
            'direccion' => $sede->direccion,
            'activa' => false,
        ])->assertStatus(200);

        $this->assertTrue((bool) $sede->fresh()->activa);
    }

    public function test_cerrar_una_sede_la_saca_del_listado_del_cliente_y_reabrirla_la_devuelve(): void
    {
        Sanctum::actingAs(SuperAdministrador::firstOrFail());
        $sede = $this->crearSede('Sede Que Cierra');

        $this->postJson('/api/superadmin/sedes/'.$sede->id.'/estado', ['activa' => false])
            ->assertStatus(200);
        $this->assertFalse((bool) $sede->fresh()->activa);

        $visibles = array_column($this->getJson('/api/sucursales')->json('data'), 'id');
        $this->assertNotContains($sede->id, $visibles);

        // Vuelve a abrir: todo sigue como antes, sin recrear nada.
        $this->postJson('/api/superadmin/sedes/'.$sede->id.'/estado', ['activa' => true])
            ->assertStatus(200);
        $this->assertTrue((bool) $sede->fresh()->activa);

        $visibles = array_column($this->getJson('/api/sucursales')->json('data'), 'id');
        $this->assertContains($sede->id, $visibles);
    }

    public function test_admin_de_sede_cerrada_puede_consultar_pero_no_modificar(): void
    {
        $sede = $this->crearSede('Sede Cerrada Lectura');
        $pedido = $this->crearPedido($sede, 'C'.substr(uniqid(), -9));
        $admin = $this->crearAdminDeSede($sede);

        $sede->activa = false;
        $sede->save();

        Sanctum::actingAs($admin);

        // Lectura: sigue viendo su historial completo.
        $respuesta = $this->getJson('/api/admin/pedidos');
        $respuesta->assertStatus(200);
        $this->assertContains($pedido->codigo, array_column($respuesta->json('data'), 'codigo'));

        // Escritura: bloqueada mientras la sede esté cerrada.
        $this->postJson('/api/admin/productos', [
            'nombre' => 'Producto Sede Cerrada',
            'precio' => 5000,
        ])->assertStatus(403)->assertJsonPath('solo_lectura', true);
    }

    public function test_al_reabrir_la_sede_su_admin_recupera_la_escritura(): void
    {
        $sede = $this->crearSede('Sede Reabre');
        $admin = $this->crearAdminDeSede($sede);

        $sede->activa = false;
        $sede->save();

        Sanctum::actingAs($admin);
        $this->postJson('/api/admin/productos', [])->assertStatus(403);

        $sede->activa = true;
        $sede->save();

        // Ya no lo frena el modo lectura: ahora falla por validacion (422), no por 403.
        $this->postJson('/api/admin/productos', [])->assertStatus(422);
    }

    public function test_el_admin_general_no_se_ve_afectado_por_una_sede_cerrada(): void
    {
        $sede = $this->crearSede('Sede Cerrada Ajena');
        $sede->activa = false;
        $sede->save();

        Sanctum::actingAs(User::where('email', 'admin@rooster.com')->firstOrFail());

        // El admin general no tiene sucursal asignada: conserva la escritura.
        $this->postJson('/api/admin/productos', [])->assertStatus(422);
    }

    private function crearSede(string $nombre): Sucursal
    {
        $sucursal = new Sucursal([
            'nombre' => $nombre.' '.uniqid(),
            'direccion' => 'Direccion de prueba',
        ]);
        $sucursal->instancia_id = self::INSTANCIA;
        $sucursal->save();

        return $sucursal->refresh();
    }

    private function crearAdminDeSede(Sucursal $sucursal): User
    {
        return User::create([
            'role_id' => Role::where('nombre', 'admin_sede')->firstOrFail()->id,
            'instancia_id' => self::INSTANCIA,
            'sucursal_id' => $sucursal->id,
            'nombre' => 'Admin '.$sucursal->nombre,
            'email' => 'admin.sede.'.uniqid().'@example.com',
            'password' => 'ClavePrueba1234*',
            'activo' => true,
        ]);
    }

    private function crearPedido(Sucursal $sucursal, string $codigo): Pedido
    {
        $cliente = User::create([
            'role_id' => Role::where('nombre', 'cliente')->firstOrFail()->id,
            'instancia_id' => self::INSTANCIA,
            'nombre' => 'Cliente Sede Test',
            'email' => 'cliente.sede.'.uniqid().'@example.com',
            'password' => 'ClavePrueba1234*',
        ]);

        // instancia_id esta fuera de $fillable (anti tenant-hopping): se asigna a mano.
        $pedido = new Pedido([
            'cliente_id' => $cliente->id,
            'sucursal_id' => $sucursal->id,
            'codigo' => $codigo,
            'estado' => 'pendiente',
            'modalidad' => 'para_llevar',
            'subtotal' => 5000,
            'descuento' => 0,
            'total' => 5000,
            'puntos_ganados' => 0,
            'nombre_cliente' => 'Cliente Sede Test',
        ]);
        $pedido->instancia_id = self::INSTANCIA;
        $pedido->save();

        return $pedido;
    }
}
