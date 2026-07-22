<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pruebas funcionales del catalogo de productos: acceso publico y
 * proteccion por rol del panel admin (Entregable 5).
 */
class ProductoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_listado_publico_de_productos_devuelve_200(): void
    {
        $response = $this->getJson('/api/productos');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'nombre', 'precio_base', 'disponible']]]);
    }

    public function test_listado_admin_de_productos_sin_autenticar_devuelve_401(): void
    {
        $response = $this->getJson('/api/admin/productos');

        $response->assertStatus(401);
    }

    public function test_listado_admin_de_productos_con_rol_cliente_devuelve_403(): void
    {
        $rolCliente = Role::where('nombre', 'cliente')->firstOrFail();
        $cliente = User::create([
            'role_id' => $rolCliente->id,
            'instancia_id' => 1,
            'nombre' => 'Cliente Test 403',
            'email' => 'cliente.403.'.uniqid().'@example.com',
            'password' => 'ClavePrueba1234*',
        ]);

        Sanctum::actingAs($cliente);

        $response = $this->getJson('/api/admin/productos');

        $response->assertStatus(403);
    }

    public function test_crear_producto_como_super_admin_devuelve_201(): void
    {
        $admin = User::where('email', 'admin@rooster.com')->firstOrFail();
        $categoria = Categoria::firstOrFail();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/productos', [
            'categoria_id' => $categoria->id,
            'nombre' => 'Producto de prueba entregable 5',
            'descripcion' => 'Creado por test automatizado',
            'precio_base' => 3500,
            'disponible' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.nombre', 'Producto de prueba entregable 5');

        $this->assertDatabaseHas('productos', [
            'nombre' => 'Producto de prueba entregable 5',
            'precio_base' => 3500,
        ]);
    }
}
