<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Pruebas funcionales del modulo de autenticacion (Entregable 5).
 * Usa DatabaseTransactions (NO RefreshDatabase): la BD del proyecto se
 * mantiene por SQL manual, nunca por `php artisan migrate` — cada test
 * corre en una transaccion que se revierte sola al terminar.
 */
class AuthTest extends TestCase
{
    use DatabaseTransactions;

    public function test_login_con_credenciales_validas_devuelve_token(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin@rooster.com',
            'password' => 'admin123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'email', 'rol'], 'token']);
    }

    public function test_login_con_password_incorrecta_devuelve_422(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin@rooster.com',
            'password' => 'password-incorrecta',
        ]);

        $response->assertStatus(422);
    }

    public function test_registro_de_cliente_nuevo_devuelve_201_con_token(): void
    {
        $email = 'test.entregable5.'.uniqid().'@example.com';

        $response = $this->postJson('/api/register', [
            'nombre' => 'Cliente de Prueba',
            'email' => $email,
            'password' => 'ClavePrueba1234*',
            'password_confirmation' => 'ClavePrueba1234*',
            'telefono' => '88880000',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.email', $email)
            ->assertJsonStructure(['data' => ['id', 'rol'], 'token']);
    }

    public function test_registro_con_password_debil_devuelve_422(): void
    {
        $response = $this->postJson('/api/register', [
            'nombre' => 'Cliente Debil',
            'email' => 'debil.'.uniqid().'@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_ruta_protegida_sin_token_devuelve_401(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }
}
