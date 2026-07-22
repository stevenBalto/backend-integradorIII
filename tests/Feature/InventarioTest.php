<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Insumo;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pruebas funcionales del modulo de Inventario: toma fisica auditada y
 * la garantia de que `cantidad_actual` no se edita por PUT normal
 * (Entregable 5).
 */
class InventarioTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('email', 'admin@rooster.com')->firstOrFail();
    }

    public function test_toma_fisica_actualiza_cantidad_y_registra_movimiento(): void
    {
        $insumo = Insumo::firstOrFail();
        $cantidadContada = $insumo->cantidad_actual - 5;

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/admin/insumos/{$insumo->id}/toma-fisica", [
            'cantidad_contada' => $cantidadContada,
            'nota' => 'Ajuste de prueba automatizada',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.movimiento.tipo', 'toma_fisica');

        $this->assertEquals(
            (float) $cantidadContada,
            (float) $response->json('data.insumo.cantidad_actual')
        );

        $this->assertDatabaseHas('insumo_movimientos', [
            'insumo_id' => $insumo->id,
            'tipo' => 'toma_fisica',
        ]);
    }

    public function test_put_normal_no_puede_editar_cantidad_actual(): void
    {
        $insumo = Insumo::firstOrFail();
        $cantidadOriginal = $insumo->cantidad_actual;

        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/admin/insumos/{$insumo->id}", [
            'nombre' => $insumo->nombre,
            'unidad_medida' => $insumo->unidad_medida,
            'stock_minimo' => $insumo->stock_minimo ?? 0,
            'cantidad_actual' => 999999,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('insumos', [
            'id' => $insumo->id,
            'cantidad_actual' => $cantidadOriginal,
        ]);
    }
}
