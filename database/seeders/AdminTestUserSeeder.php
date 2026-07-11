<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Siembra un unico usuario super_admin de prueba para desarrollo.
 * Idempotente (updateOrCreate por email). Acceso total (super_admin);
 * minimo privilegio por modulo queda pendiente para el modulo de Usuarios y roles.
 */
class AdminTestUserSeeder extends Seeder
{
    public function run(): void
    {
        $rolId = Role::where('nombre', 'super_admin')->value('id');

        if ($rolId === null) {
            $this->command?->warn('No existe el rol super_admin. Corré RolesSeeder primero.');

            return;
        }

        User::updateOrCreate(
            ['email' => 'admin@rooster.com'],
            [
                'role_id' => $rolId,
                'nombre' => 'Admin Rooster',
                'password' => 'admin123456',
                'telefono' => null,
            ],
        );
    }
}
