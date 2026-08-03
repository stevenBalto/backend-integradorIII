<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
Illuminate\Support\Facades\Facade::setFacadeApplication($app);
use Illuminate\Support\Facades\DB;

$logFile = __DIR__ . '/../storage/logs/laravel.log';
if (!file_exists($logFile)) {
    echo "Log file not found: $logFile\n";
    exit(1);
}

$fh = fopen($logFile, 'r');
if (!$fh) {
    echo "Unable to open log file\n";
    exit(1);
}

$inserted = 0;
$skipped = 0;
while (($line = fgets($fh)) !== false) {
    if (strpos($line, 'request.timing') === false) {
        continue;
    }

    if (preg_match('/\[(.*?)\].*request.timing\s+(\{.*\})/i', $line, $m)) {
        $ts = $m[1];
        $json = $m[2];
        $data = json_decode($json, true);
        if (!is_array($data)) {
            continue;
        }

        $route = $data['route'] ?? null;
        $path = $data['path'] ?? null;
        $method = $data['method'] ?? null;
        $duration = isset($data['duration_ms']) ? (int) round($data['duration_ms']) : null;
        $ip = $data['ip'] ?? null;
        $createdAt = date('Y-m-d H:i:s', strtotime($ts));

        if (!$route || !$path || !$method || $duration === null) {
            continue;
        }

        $exists = DB::table('request_timings')
            ->where('route', $route)
            ->where('path', $path)
            ->where('method', $method)
            ->where('duration_ms', $duration)
            ->where('created_at', $createdAt)
            ->exists();

        if ($exists) {
            $skipped++;
            echo "SKIP {$createdAt} {$route} {$duration}ms\n";
            continue;
        }

        DB::table('request_timings')->insert([
            'method' => $method,
            'path' => $path,
            'route' => $route,
            'duration_ms' => $duration,
            'status_code' => 200,
            'user_id' => null,
            'ip' => $ip,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $inserted++;
        echo "INSERT {$createdAt} {$route} {$duration}ms\n";
    }
}
fclose($fh);

echo "Done. Inserted: {$inserted}, Skipped: {$skipped}\n";
