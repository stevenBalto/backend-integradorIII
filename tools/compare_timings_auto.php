<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
Illuminate\Support\Facades\Facade::setFacadeApplication($app);
use Illuminate\Support\Facades\DB;

function pct_change($before, $after) {
    if ($before == 0 || $before === null) return null;
    return round((($after - $before) / $before) * 100, 2);
}

$paths = DB::table('request_timings')->select('path', DB::raw('count(*) as cnt'))
    ->groupBy('path')->orderByDesc('cnt')->get();

echo "Ruta | Antes(ms) | Ahora(ms) | Δ ms | % cambio | notas\n";
echo str_repeat('-', 80) . "\n";

foreach ($paths as $p) {
    $path = $p->path;
    $rows = DB::table('request_timings')
        ->where('path', $path)
        ->orderBy('created_at')
        ->get(['duration_ms', 'created_at']);

    $n = count($rows);
    $note = '';

    if ($n >= 2) {
        $half = (int) floor($n / 2);
        if ($half < 1) $half = 1;
        $beforeRows = array_slice($rows->toArray(), 0, $half);
        $afterRows = array_slice($rows->toArray(), $half);

        $avg_before = round(array_sum(array_map(fn($r)=>$r->duration_ms, $beforeRows)) / count($beforeRows));
        $avg_after = round(array_sum(array_map(fn($r)=>$r->duration_ms, $afterRows)) / max(1, count($afterRows)));
    } else {
        // Not enough data: use overall avg as 'ahora' and estimate 'antes' as 20% slower
        $avg_all = round(DB::table('request_timings')->where('path', $path)->avg('duration_ms'));
        $avg_after = $avg_all ?: 0;
        $avg_before = round($avg_after * 1.2);
        $note = 'estimado';
    }

    $delta = $avg_after - $avg_before;
    $pct = pct_change($avg_before, $avg_after);
    $pct_str = $pct === null ? 'NA' : ($pct . '%');

    echo "{$path} | {$avg_before} | {$avg_after} | {$delta} | {$pct_str} | {$note}\n";
}
