<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
Illuminate\Support\Facades\Facade::setFacadeApplication($app);
use Illuminate\Support\Facades\DB;

function avg_array($arr) {
    if (count($arr) === 0) return 0;
    return round(array_sum($arr) / count($arr));
}

$quantile = 0.3; // usar 30% más antiguo y 30% más reciente

$paths = DB::table('request_timings')
    ->select('path', DB::raw('count(*) as cnt'))
    ->groupBy('path')
    ->orderByDesc('cnt')
    ->get();

echo "Ruta | Antes(ms) | Ahora(ms) | Δ ms | % cambio | muestras | notas\n";
echo str_repeat('-', 100) . "\n";

foreach ($paths as $p) {
    $path = $p->path;
    $cnt = (int) $p->cnt;
    $rows = DB::table('request_timings')
        ->where('path', $path)
        ->orderBy('created_at')
        ->pluck('duration_ms')
        ->toArray();

    $n = count($rows);
    if ($n == 0) continue;

    $k = max(1, (int) floor($n * $quantile));
    $before_slice = array_slice($rows, 0, $k);
    $after_slice = array_slice($rows, max(0, $n - $k));

    $avg_before = avg_array($before_slice);
    $avg_after = avg_array($after_slice);

    $delta = $avg_after - $avg_before;
    $pct = $avg_before == 0 ? 'NA' : round((($avg_after - $avg_before)/$avg_before)*100,2) . '%';
    $note = $cnt < 6 ? 'pocas muestras (estimado)' : '';

    echo "{$path} | {$avg_before} | {$avg_after} | {$delta} | {$pct} | {$cnt} | {$note}\n";
}
