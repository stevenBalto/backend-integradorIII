<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
Illuminate\Support\Facades\Facade::setFacadeApplication($app);
use Illuminate\Support\Facades\DB;

$routes = [
    'api/admin/pedidos',
    'api/admin/resenas',
    'api/admin/clientes',
    'api/resenas/pendientes',
];

foreach ($routes as $path) {
    $row = DB::table('request_timings')
        ->selectRaw('count(*) as cnt, round(avg(duration_ms)::numeric,2) as avg_ms, min(duration_ms) as min_ms, max(duration_ms) as max_ms')
        ->where('path', $path)
        ->first();

    $cnt = $row->cnt ?? 0;
    $avg = $row->avg_ms ?? 'NA';
    $min = $row->min_ms ?? 'NA';
    $max = $row->max_ms ?? 'NA';

    echo "{$path} -> count={$cnt}, avg={$avg}ms, min={$min}ms, max={$max}ms\n";
}
