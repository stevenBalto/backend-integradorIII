<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

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

printf("%-35s %-12s %-12s\n", 'ruta', 'antes(ms)', 'ahora(ms)');
printf("%-35s %-12s %-12s\n", str_repeat('-', 35), str_repeat('-', 12), str_repeat('-', 12));

foreach ($paths as $p) {
    $rows = DB::table('request_timings')->where('path', $p)->orderBy('id', 'desc')->limit(2)->get();
    $antes = 'NA';
    $ahora = 'NA';
    if ($rows->count() === 1) {
        $ahora = (string)$rows[0]->duration_ms;
    } elseif ($rows->count() === 2) {
        // rows[0] is latest
        $ahora = (string)$rows[0]->duration_ms;
        $antes = (string)$rows[1]->duration_ms;
    }
    printf("%-35s %-12s %-12s\n", $p, $antes, $ahora);
}
