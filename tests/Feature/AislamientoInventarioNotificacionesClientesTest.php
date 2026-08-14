<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Insumo;
use App\Models\Notificacion;
use App\Models\Pedido;
use App\Models\Role;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Regla: en un negocio con varias sedes, SOLO productos/ofertas/cupones se
 * comparten entre todas. Inventario, notificaciones y la lista de clientes
 * (con sus estadisticas de compra) son exclusivos de cada sede.
 */
class AislamientoInventarioNotificacionesClientesTest extends TestCase
{
    use DatabaseTransactions;

    private const INSTANCIA = 1;

    public function test_admin_de_sede_solo_ve_los_insumos_de_su_sede(): void
    {
        $sedePropia = $this->crearSede('Insumos Sede Propia');
        $sedeAjena = $this->crearSede('Insumos Sede Ajena');

        $this->crearInsumo($sedePropia->id, 'Queso Propio '.uniqid());
        $this->crearInsumo($sedeAjena->id, 'Queso Ajeno '.uniqid());

        Sanctum::actingAs($this->crearAdminDeSede($sedePropia));

        $response = $this->getJson('/api/admin/insumos');
        $response->assertStatus(200);

        $nombres = array_column($response->json('data'), 'nombre');
        $this->assertTrue(collect($nombres)->contains(fn ($n) => str_starts_with($n, 'Queso Propio')));
        $this->assertFalse(collect($nombres)->contains(fn ($n) => str_starts_with($n, 'Queso Ajeno')));
    }

    public function test_crear_insumo_como_admin_sede_ignora_la_sucursal_enviada_y_fuerza_la_propia(): void
    {
        $sedePropia = $this->crearSede('Insumo Nuevo Propia');
        $sedeAjena = $this->crearSede('Insumo Nuevo Ajena');

        Sanctum::actingAs($this->crearAdminDeSede($sedePropia));

        $response = $this->postJson('/api/admin/insumos', [
            'nombre' => 'Harina Test '.uniqid(),
            'unidad_medida' => 'kg',
            'sucursal_id' => $sedeAjena->id, // intenta colarse en otra sede
        ]);

        $response->assertStatus(201);
        $this->assertSame($sedePropia->id, $response->json('data.sucursal_id'));
    }

    public function test_crear_insumo_como_admin_general_requiere_sucursal_id(): void
    {
        Sanctum::actingAs(User::where('email', 'admin@rooster.com')->firstOrFail());

        $response = $this->postJson('/api/admin/insumos', [
            'nombre' => 'Sin Sede Test '.uniqid(),
            'unidad_medida' => 'kg',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['sucursal_id']);
    }

    public function test_admin_de_sede_no_ve_notificaciones_de_otra_sede_pero_si_las_globales(): void
    {
        $sedePropia = $this->crearSede('Notif Sede Propia');
        $sedeAjena = $this->crearSede('Notif Sede Ajena');

        $propia = Notificacion::create([
            'instancia_id' => self::INSTANCIA, 'sucursal_id' => $sedePropia->id,
            'tipo' => 'pedido_nuevo', 'titulo' => 'Notif Propia '.uniqid(), 'mensaje' => 'x', 'leida' => false,
        ]);
        $ajena = Notificacion::create([
            'instancia_id' => self::INSTANCIA, 'sucursal_id' => $sedeAjena->id,
            'tipo' => 'pedido_nuevo', 'titulo' => 'Notif Ajena '.uniqid(), 'mensaje' => 'x', 'leida' => false,
        ]);
        $global = Notificacion::create([
            'instancia_id' => self::INSTANCIA, 'sucursal_id' => null,
            'tipo' => 'producto_nuevo', 'titulo' => 'Notif Global '.uniqid(), 'mensaje' => 'x', 'leida' => false,
        ]);

        Sanctum::actingAs($this->crearAdminDeSede($sedePropia));

        $response = $this->getJson('/api/admin/notificaciones');
        $response->assertStatus(200);

        $ids = array_column($response->json('data'), 'id');
        $this->assertContains($propia->id, $ids);
        $this->assertContains($global->id, $ids);
        $this->assertNotContains($ajena->id, $ids);
    }

    public function test_admin_de_sede_solo_ve_clientes_que_compraron_en_su_sede(): void
    {
        $sedePropia = $this->crearSede('Clientes Sede Propia');
        $sedeAjena = $this->crearSede('Clientes Sede Ajena');

        $clientePropio = $this->crearCliente('Cliente Propio Test');
        $clienteAjeno = $this->crearCliente('Cliente Ajeno Test');

        $this->crearPedido($sedePropia, $clientePropio, 'P'.substr(uniqid(), -9));
        $this->crearPedido($sedeAjena, $clienteAjeno, 'A'.substr(uniqid(), -9));

        Sanctum::actingAs($this->crearAdminDeSede($sedePropia));

        $response = $this->getJson('/api/admin/clientes');
        $response->assertStatus(200);

        $ids = array_column($response->json('data'), 'id');
        $this->assertContains($clientePropio->id, $ids);
        $this->assertNotContains($clienteAjeno->id, $ids);
    }

    private function crearSede(string $nombre): Sucursal
    {
        $sucursal = new Sucursal(['nombre' => $nombre.' '.uniqid(), 'direccion' => 'Direccion de prueba']);
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
            'email' => 'admin.aislamiento.'.uniqid().'@example.com',
            'password' => 'ClavePrueba1234*',
            'activo' => true,
        ]);
    }

    private function crearInsumo(int $sucursalId, string $nombre): Insumo
    {
        $insumo = new Insumo(['nombre' => $nombre, 'unidad_medida' => 'kg']);
        $insumo->instancia_id = self::INSTANCIA;
        $insumo->sucursal_id = $sucursalId;
        $insumo->save();

        return $insumo;
    }

    private function crearCliente(string $nombre): User
    {
        return User::create([
            'role_id' => Role::where('nombre', 'cliente')->firstOrFail()->id,
            'instancia_id' => self::INSTANCIA,
            'nombre' => $nombre.' '.uniqid(),
            'email' => 'cliente.aislamiento.'.uniqid().'@example.com',
            'password' => 'ClavePrueba1234*',
        ]);
    }

    private function crearPedido(Sucursal $sucursal, User $cliente, string $codigo): Pedido
    {
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
            'nombre_cliente' => $cliente->nombre,
        ]);
        $pedido->instancia_id = self::INSTANCIA;
        $pedido->save();

        return $pedido;
    }
}
