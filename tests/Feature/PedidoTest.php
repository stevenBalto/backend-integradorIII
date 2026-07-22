<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\Role;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pruebas funcionales del modulo de Pedidos (Entregable 5).
 */
class PedidoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_cliente_autenticado_puede_crear_pedido(): void
    {
        $rolCliente = Role::where('nombre', 'cliente')->firstOrFail();
        $cliente = User::create([
            'role_id' => $rolCliente->id,
            'instancia_id' => 1,
            'nombre' => 'Cliente Test Pedido',
            'email' => 'cliente.pedido.'.uniqid().'@example.com',
            'password' => 'ClavePrueba1234*',
        ]);
        $producto = Producto::where('disponible', true)->firstOrFail();
        $sucursal = Sucursal::where('instancia_id', 1)->firstOrFail();

        Sanctum::actingAs($cliente);

        $response = $this->postJson('/api/pedidos', [
            'sucursal_id' => $sucursal->id,
            'modalidad' => 'comer_aqui',
            'nombre_cliente' => 'Cliente Test Pedido',
            'notas' => 'Pedido de prueba automatizada',
            'items' => [
                ['producto_id' => $producto->id, 'cantidad' => 2],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.nombre_cliente', 'Cliente Test Pedido')
            ->assertJsonStructure(['data' => ['id', 'codigo', 'estado', 'items']]);
    }

    public function test_crear_pedido_sin_items_devuelve_422(): void
    {
        $rolCliente = Role::where('nombre', 'cliente')->firstOrFail();
        $cliente = User::create([
            'role_id' => $rolCliente->id,
            'instancia_id' => 1,
            'nombre' => 'Cliente Test Sin Items',
            'email' => 'cliente.sinitems.'.uniqid().'@example.com',
            'password' => 'ClavePrueba1234*',
        ]);
        $sucursal = Sucursal::where('instancia_id', 1)->firstOrFail();

        Sanctum::actingAs($cliente);

        $response = $this->postJson('/api/pedidos', [
            'sucursal_id' => $sucursal->id,
            'modalidad' => 'comer_aqui',
            'nombre_cliente' => 'Cliente Test Sin Items',
            'items' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }
}
