<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Siembra los 3 roles del sistema. Idempotente (updateOrCreate por nombre).
 */
class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['nombre' => 'super_admin', 'descripcion' => 'Acceso total: todas las sucursales y configuracion global.'],
            ['nombre' => 'admin_sede',  'descripcion' => 'Administra unicamente su sucursal asignada.'],
            ['nombre' => 'cliente',     'descripcion' => 'Cliente final: sus pedidos, cupones y perfil.'],
        ];

        foreach ($roles as $rol) {
            Role::updateOrCreate(['nombre' => $rol['nombre']], $rol);
        }
    }
}
