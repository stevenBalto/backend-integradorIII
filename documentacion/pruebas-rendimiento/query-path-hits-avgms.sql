-- Reporte "path / hits / avg_ms" sobre request_timings (middleware LogRequestTiming).
-- Reemplazo manual de Telescope/Pulse: agregación simple, sin tablas propias.
-- Uso: psql -h 127.0.0.1 -U postgres -d rooster_pizza -f query-path-hits-avgms.sql

SELECT
    path,
    COUNT(*)                      AS hits,
    ROUND(AVG(duration_ms), 1)    AS avg_ms
FROM request_timings
GROUP BY path
ORDER BY hits DESC;

-- Variante: solo lo lento (candidatos a mirar con k6/profiling)
-- SELECT path, COUNT(*) AS hits, ROUND(AVG(duration_ms), 1) AS avg_ms
-- FROM request_timings
-- GROUP BY path
-- HAVING AVG(duration_ms) > 100
-- ORDER BY avg_ms DESC;

-- Variante: por rango de fecha (para no mezclar corridas de distintos días)
-- ... WHERE created_at >= now() - interval '1 day' ...
