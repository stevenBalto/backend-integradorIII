<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$threshold = '2026-08-02 21:21:15';
$paths = [
    '/api/home-config',
    '/api/productos',
    '/api/productos/1',
    '/api/ofertas',
    '/api/cupones',
    '/api/login',
    '/api/register',
    '/api/forgot-password',
    '/api/pedidos',
    '/api/pedidos/mios',
    '/api/resenas/pendientes',
    '/api/admin/resenas',
    '/api/admin/dashboard',
    '/api/admin/analiticas',
];

printf("%-35s %-25s %-12s\n", 'ruta', 'created_at', 'duration_ms');
printf("%-35s %-25s %-12s\n", str_repeat('-', 35), str_repeat('-', 25), str_repeat('-', 12));

foreach ($paths as $p) {
    $row = DB::table('request_timings')
        ->where('path', $p)
        ->where('created_at', '<', $threshold)
        ->orderBy('id', 'desc')
        ->first();

    if ($row) {
        printf("%-35s %-25s %-12s\n", $p, $row->created_at, $row->duration_ms);
    } else {
        printf("%-35s %-25s %-12s\n", $p, 'NO_EXISTE', 'NA');
    }
}
