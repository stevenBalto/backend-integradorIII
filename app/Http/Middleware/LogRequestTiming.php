<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LogRequestTiming
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = (microtime(true) - $start) * 1000; // ms

        $route = optional($request->route())->getName() ?: $request->method() . ' ' . $request->path();

        Log::info('request.timing', [
            'route' => $route,
            'path' => $request->path(),
            'method' => $request->method(),
            'duration_ms' => round($duration, 2),
            'ip' => $request->ip(),
        ]);

        // Persistir en BD sin afectar la respuesta (try/catch silencioso).
        try {
            DB::table('request_timings')->insert([
                'method' => $request->method(),
                'path' => $request->path(),
                'route' => $route,
                'duration_ms' => (int) round($duration),
                'status_code' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null,
                'user_id' => $request->user()?->id ?? null,
                'ip' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('request.timing.db_insert_failed', ['error' => $e->getMessage()]);
        }

        return $response;
    }
}
