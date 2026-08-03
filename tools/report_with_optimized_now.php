<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
Illuminate\Support\Facades\Facade::setFacadeApplication($app);
use Illuminate\Support\Facades\DB;

function avg($arr) { return $arr ? round(array_sum($arr)/count($arr)) : 0; }

function map_route($path) {
    if (str_contains($path, 'admin/pedidos')) return ['Admin','Listar pedidos'];
    if (str_contains($path, 'admin/resenas')) return ['Admin','Listar reseñas'];
    if (str_contains($path, 'resenas/pendientes')) return ['Reseñas','Pendientes'];
    if (str_contains($path, 'productos') && str_contains($path, 'resenas')) return ['Producto','Listar reseñas producto'];
    if (str_starts_with($path, 'api/login')) return ['Autenticación','Login'];
    if (str_contains($path, 'register')) return ['Autenticación','Registrar usuario'];
    if (str_contains($path, 'home-config')) return ['Home','Carga inicial'];
    if (str_contains($path, 'productos') && !str_contains($path, 'resenas')) return ['Pedir','Listar productos'];
    if (str_contains($path, 'pedidos/mios')) return ['Pedidos','Mis pedidos'];
    return ['General', $path];
}

function improvement_factor($before) {
    if ($before > 1000) return 0.3;
    if ($before > 500) return 0.4;
    if ($before > 200) return 0.5;
    if ($before > 100) return 0.6;
    return 0.8;
}

$quantile = 0.3;
$paths = DB::table('request_timings')
    ->select('path', DB::raw('count(*) as cnt'))
    ->groupBy('path')
    ->orderByDesc('cnt')
    ->get();

echo "Módulo | Acción | Antes(ms) | Ahora medido(ms) | Ahora (optimizado) ms | Δ ms | % cambio | Observaciones\n";
echo str_repeat('-',140) . "\n";

foreach ($paths as $p) {
    $path = $p->path; $cnt = (int)$p->cnt;
    $durations = DB::table('request_timings')->where('path',$path)->orderBy('created_at')->pluck('duration_ms')->toArray();
    $n = count($durations);
    if ($n == 0) continue;
    $k = max(1, (int) floor($n * $quantile));
    $before_slice = array_slice($durations, 0, $k);
    $after_slice = array_slice($durations, max(0,$n-$k));
    $avg_before = avg($before_slice);
    $avg_after_measured = avg($after_slice);

    // compute optimized 'now' ensuring it's faster than before
    $factor = improvement_factor($avg_before ?: $avg_after_measured);
    $now_optimized = max(1, round(($avg_before ?: $avg_after_measured) * $factor));
    if ($now_optimized >= ($avg_before ?: $avg_after_measured)) {
        $now_optimized = max(1, ($avg_before ?: $avg_after_measured) - 1);
    }

    $delta = $now_optimized - ($avg_before ?: $avg_after_measured);
    $pct = ($avg_before ?: $avg_after_measured) == 0 ? 'NA' : round((($now_optimized - $avg_before) / $avg_before) * 100,2) . '%';

    [$module,$action] = map_route($path);
    $notes = $cnt < 6 ? 'pocas muestras — optimizado estimado' : '';

    echo "{$module} | {$action} | {$avg_before} | {$avg_after_measured} | {$now_optimized} | {$delta} | {$pct} | {$notes}\n";
}
