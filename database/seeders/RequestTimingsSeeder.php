<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

final class RequestTimingsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now()->toDateTimeString();

        $rows = [
            ['method' => 'GET', 'path' => '/api/home-config', 'route' => 'config.show', 'duration_ms' => 3200, 'status_code' => 200, 'user_id' => null, 'ip' => null, 'created_at' => $now, 'updated_at' => $now],
            ['method' => 'GET', 'path' => '/api/productos', 'route' => 'productos.index', 'duration_ms' => 2200, 'status_code' => 200, 'user_id' => null, 'ip' => null, 'created_at' => $now, 'updated_at' => $now],
            ['method' => 'GET', 'path' => '/api/productos/1', 'route' => 'productos.show', 'duration_ms' => 1200, 'status_code' => 200, 'user_id' => null, 'ip' => null, 'created_at' => $now, 'updated_at' => $now],
            ['method' => 'POST', 'path' => '/api/login', 'route' => 'auth.login', 'duration_ms' => 450, 'status_code' => 200, 'user_id' => null, 'ip' => null, 'created_at' => $now, 'updated_at' => $now],
            ['method' => 'POST', 'path' => '/api/register', 'route' => 'auth.register', 'duration_ms' => 900, 'status_code' => 201, 'user_id' => null, 'ip' => null, 'created_at' => $now, 'updated_at' => $now],
            ['method' => 'POST', 'path' => '/api/forgot-password', 'route' => 'password.forgot', 'duration_ms' => 1100, 'status_code' => 200, 'user_id' => null, 'ip' => null, 'created_at' => $now, 'updated_at' => $now],
            ['method' => 'POST', 'path' => '/api/pedidos', 'route' => 'pedidos.store', 'duration_ms' => 800, 'status_code' => 201, 'user_id' => 1, 'ip' => null, 'created_at' => $now, 'updated_at' => $now],
            ['method' => 'GET', 'path' => '/api/pedidos/mios', 'route' => 'pedidos.mios', 'duration_ms' => 1200, 'status_code' => 200, 'user_id' => 1, 'ip' => null, 'created_at' => $now, 'updated_at' => $now],
            ['method' => 'GET', 'path' => '/api/ofertas', 'route' => 'ofertas.index', 'duration_ms' => 1000, 'status_code' => 200, 'user_id' => null, 'ip' => null, 'created_at' => $now, 'updated_at' => $now],
            ['method' => 'GET', 'path' => '/api/cupones', 'route' => 'cupones.index', 'duration_ms' => 850, 'status_code' => 200, 'user_id' => null, 'ip' => null, 'created_at' => $now, 'updated_at' => $now],
            ['method' => 'GET', 'path' => '/api/resenas/pendientes', 'route' => 'resenas.pendientes', 'duration_ms' => 900, 'status_code' => 200, 'user_id' => 1, 'ip' => null, 'created_at' => $now, 'updated_at' => $now],
            ['method' => 'GET', 'path' => '/api/admin/dashboard', 'route' => 'admin.dashboard', 'duration_ms' => 3000, 'status_code' => 200, 'user_id' => null, 'ip' => null, 'created_at' => $now, 'updated_at' => $now],
            ['method' => 'GET', 'path' => '/api/admin/analiticas', 'route' => 'admin.analiticas', 'duration_ms' => 3500, 'status_code' => 200, 'user_id' => null, 'ip' => null, 'created_at' => $now, 'updated_at' => $now],
            ['method' => 'GET', 'path' => '/api/admin/resenas', 'route' => 'admin.resenas.index', 'duration_ms' => 1200, 'status_code' => 200, 'user_id' => null, 'ip' => null, 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('request_timings')->insert($rows);
    }
}
