# Pruebas de rendimiento — Rooster Pizza & Grill

Medición y optimización de los tiempos de respuesta del API en las 3 perspectivas
(usuario/cliente, admin, superadmin) + Core Web Vitals del frontend.

## Herramientas
- **k6** v2.1.0 (binario portable) — carga y tiempos del API por rol.
- **Lighthouse** 13 (ya en `node_modules` del front) — Core Web Vitals.
- **request_timings** + middleware `LogRequestTiming` — timing server-side por request.
- (Laravel Pulse/Telescope quedaron descartados: exigían tablas propias; no hicieron
  falta porque el cuello no eran queries.)

## Hallazgo principal
No había endpoints con queries lentas: TODOS rendían ~200ms **uniforme**. La causa era
**OPcache apagado** (PHP recompilaba toda la app en cada request) + `php artisan serve`
mono-proceso. Segundo problema: el middleware `LogRequestTiming` fallaba el INSERT en
cada request porque la tabla `request_timings` no existía (excepción por request).

## Optimizaciones aplicadas
1. **OPcache habilitado** en `C:\xampp\php\php.ini` (ver "Setup OPcache" abajo). — win principal.
2. **Tabla `request_timings` creada** vía `bd-doc/migracion_2026-08-09_request_timings.sql`
   (el middleware ya la escribe; se elimina la excepción por request).

## Resultados (media por rol, k6, 30 iteraciones)
| Perspectiva | Antes (sin OPcache) | Ahora (con OPcache) | Mejora |
|---|---|---|---|
| Cliente (usuario) | 198 ms | 66 ms | −67 % |
| Admin | 248 ms | 68 ms | −73 % |
| Superadmin | 221 ms | 56 ms | −75 % |

- p50 ~50 ms · p95 ~165–177 ms · 0 % de errores.
- Server-side puro (middleware): p. ej. `GET /admin/dashboard` = ~14 ms.

### Frontend — Core Web Vitals (Lighthouse, desktop)
| Perspectiva | Performance | LCP | CLS | TBT | Accesibilidad |
|---|---|---|---|---|---|
| Usuario (cliente) | 98 | 557 ms | 0.000 | 0 ms | 95 |
| Admin | 98 | 610 ms | 0.000 | 0 ms | 90 |
| Superadmin | 97 | 470 ms | 0.000 | 0 ms | 83 |

Todos los CWV en verde (LCP < 2.5 s, CLS < 0.1). El front ya estaba fino.

## Setup OPcache (cada dev debe hacerlo en su máquina — NO va en git)
En `C:\xampp\php\php.ini`:
```
zend_extension=opcache
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=1
opcache.revalidate_freq=2
```
Reiniciar el server: matar el `php artisan serve` y `php artisan serve --host=127.0.0.1 --port=8000`.
Verificar: `php -v` debe decir "with Zend OPcache".

## Cómo reproducir la medición
1. Backend arriba en `127.0.0.1:8000` (`php artisan serve`).
2. k6 (binario) — por rol, secuencial y en caliente:
   ```
   k6 run --no-usage-report -e ONLY=cliente    --out csv=raw_cliente.csv    api-load.js
   k6 run --no-usage-report -e ONLY=admin      --out csv=raw_admin.csv      api-load.js
   k6 run --no-usage-report -e ONLY=superadmin --out csv=raw_superadmin.csv api-load.js
   ```
   (Sin `ONLY` corre los 3 a la vez = escenario concurrente.)
3. `node aggregate.mjs` → genera `resultados.xls` (Excel) y `resultados.csv`.
   Si existe `antes_*.csv`, agrega columna de comparación antes/después.
4. Frontend CWV: `ng serve` arriba; el runner de Lighthouse loguea por rol e inyecta el
   token en `sessionStorage` (auth_token / sa_token) y mide `/tabs/home`, `/admin/dashboard`,
   `/superadmin/panel`.

## Archivos
- `api-load.js` — script k6 (3 escenarios, GET/lectura, etiquetado por interacción).
- `aggregate.mjs` — agrega los CSV de k6 → Excel/CSV (p50/p95/media, antes/después).
- `resultados.xls` / `resultados.csv` — el Excel entregable (interacción, API, tiempo).
- `lighthouse.json` — CWV por perspectiva.
