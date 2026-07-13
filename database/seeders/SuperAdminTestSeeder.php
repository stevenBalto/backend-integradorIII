<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SuperAdministrador;
use Illuminate\Database\Seeder;

/**
 * Siembra un superadministrador de prueba para desarrollo.
 * Idempotente (updateOrCreate por email).
 *   login (usuario o email): super  /  super@rooster.com
 *   password: Super#Rooster2026
 */
class SuperAdminTestSeeder extends Seeder
{
    public function run(): void
    {
        SuperAdministrador::updateOrCreate(
            ['email' => 'super@rooster.com'],
            [
                'nombre' => 'Super Rooster',
                'usuario' => 'super',
                'password' => 'Super#Rooster2026',
                'activo' => true,
            ],
        );
    }
}
