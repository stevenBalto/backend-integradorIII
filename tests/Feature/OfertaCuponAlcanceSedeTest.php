<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Cupon;
use App\Models\Oferta;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Alcance por sede en ofertas y cupones: una oferta/cupon del negocio (nivel
 * instancia, visible en todas las sedes) puede restringirse para que solo se
 * pueda CANJEAR en sedes especificas. El resto del negocio la sigue viendo
 * (queda documentada como "Disponible en: X, Y" en la vista cliente), pero
 * canjearla en una sede no listada se bloquea.
 */
class OfertaCuponAlcanceSedeTest extends TestCase
{
    use DatabaseTransactions;

    private const INSTANCIA = 1;

    public function test_oferta_alcance_todas_aplica_en_cualquier_sede(): void
    {
        $sedeA = $this->crearSede('Sede Alcance A');
        $sedeB = $this->crearSede('Sede Alcance B');
        $oferta = $this->crearOferta('Oferta Todas Test');

        $this->assertTrue($oferta->aplicaEnSucursal($sedeA->id));
        $this->assertTrue($oferta->aplicaEnSucursal($sedeB->id));
    }

    public function test_oferta_alcance_especifica_solo_aplica_en_las_sedes_asignadas(): void
    {
        $sedePermitida = $this->crearSede('Sede Permitida Oferta');
        $sedeAjena = $this->crearSede('Sede Ajena Oferta');
        $oferta = $this->crearOferta('Oferta Especifica Test', alcanceSedes: 'especifica');
        $oferta->sucursales()->sync([$sedePermitida->id]);
        $oferta->refresh()->load('sucursales');

        $this->assertTrue($oferta->aplicaEnSucursal($sedePermitida->id));
        $this->assertFalse($oferta->aplicaEnSucursal($sedeAjena->id));
    }

    public function test_cupon_alcance_especifica_solo_aplica_en_las_sedes_asignadas(): void
    {
        $sedePermitida = $this->crearSede('Sede Permitida Cupon');
        $sedeAjena = $this->crearSede('Sede Ajena Cupon');
        $cupon = $this->crearCupon('ALCTEST'.substr(uniqid(), -5), alcanceSedes: 'especifica');
        $cupon->sucursales()->sync([$sedePermitida->id]);
        $cupon->refresh()->load('sucursales');

        $this->assertTrue($cupon->aplicaEnSucursal($sedePermitida->id));
        $this->assertFalse($cupon->aplicaEnSucursal($sedeAjena->id));
    }

    public function test_crear_oferta_por_api_guarda_alcance_sedes_y_sucursales(): void
    {
        Sanctum::actingAs(User::where('email', 'admin@rooster.com')->firstOrFail());
        $sede = $this->crearSede('Sede API Oferta');

        $response = $this->postJson('/api/admin/ofertas', [
            'nombre' => 'Oferta API Test '.uniqid(),
            'tipo_descuento' => 'porcentaje',
            'valor' => 10,
            'alcance_sedes' => 'especifica',
            'sucursal_ids' => [$sede->id],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.alcance_sedes', 'especifica')
            ->assertJsonPath('data.sucursales.0.id', $sede->id);
    }

    public function test_crear_cupon_por_api_guarda_alcance_sedes_y_sucursales(): void
    {
        Sanctum::actingAs(User::where('email', 'admin@rooster.com')->firstOrFail());
        $sede = $this->crearSede('Sede API Cupon');

        $response = $this->postJson('/api/admin/cupones', [
            'codigo' => 'APITEST'.substr(uniqid(), -5),
            'tipo' => 'porcentaje',
            'valor' => 15,
            'alcance_sedes' => 'especifica',
            'sucursal_ids' => [$sede->id],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.alcance_sedes', 'especifica')
            ->assertJsonPath('data.sucursales.0.id', $sede->id);
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

    private function crearOferta(string $nombre, string $alcanceSedes = 'todas'): Oferta
    {
        $oferta = new Oferta([
            'nombre' => $nombre.' '.uniqid(),
            'tipo_descuento' => 'porcentaje',
            'valor' => 10,
            'activa' => true,
            'alcance' => 'todos',
            'alcance_sedes' => $alcanceSedes,
        ]);
        $oferta->instancia_id = self::INSTANCIA;
        $oferta->save();

        return $oferta;
    }

    private function crearCupon(string $codigo, string $alcanceSedes = 'todas'): Cupon
    {
        $cupon = new Cupon([
            'codigo' => $codigo,
            'tipo' => 'porcentaje',
            'valor' => 15,
            'usos_actuales' => 0,
            'activo' => true,
            'alcance' => 'todos',
            'alcance_sedes' => $alcanceSedes,
        ]);
        $cupon->instancia_id = self::INSTANCIA;
        $cupon->save();

        return $cupon;
    }
}
