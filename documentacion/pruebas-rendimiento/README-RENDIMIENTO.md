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

### Frontend — Core Web Vitals (Lighthouse 13, build de PRODUCCIÓN)

Medido sobre `ng build --configuration production` servido con brotli + proxy al
backend (NO sobre `ng serve`, que manda ~9.5 MB sin minificar y da números
irreales). Perfil móvil = Slow 4G + CPU 4× (pesimista de DevTools).

| Perspectiva | Desktop | Móvil (Slow 4G+CPU 4×) | LCP desktop | LCP móvil |
|---|---|---|---|---|
| Usuario (cliente) | **99** | 77 | 0.78 s | 4.2 s |
| Admin | **99** | 62 (dashboard pesado) | 1.10 s | 5.2 s |
| Superadmin | **99** | 88 (pico 93) | 0.86 s | 3.5 s |

Best Practices 100 · **SEO 100** · CLS ≈ 0 · Accesibilidad 83-95 en las 3
perspectivas. **Desktop 99** (meta >90 cumplida). El móvil es el techo de un SPA en
el perfil throttled; en un móvil real con buena red carga mucho más rápido.

**Cliente en móvil — el número que importa** (es la vista que se usa en teléfono).
El perfil "mobile" por defecto de Lighthouse (Slow 4G ~400 kbps + CPU 4×) representa
gama baja con mala señal y da **77**. En condiciones reales de teléfono el mismo
build rinde muchísimo mejor:

| Condición de red/CPU | Performance | LCP |
|---|---|---|
| Teléfono bueno (wifi, sin throttle) | **97** | 0.24 s |
| Teléfono medio (4G decente, CPU 2×) | **100** | 1.48 s |
| Lighthouse mobile por defecto (Slow 4G + CPU 4×) | 77 | 4.5 s |

El 77 NO es la experiencia real: asume 3G lento + un CPU muy castigado. Subirlo a 90+
en ese perfil exigiría SSR (paquete nuevo, descartado). Extra: se difirió 6 s el
sondeo de `resenas/pendientes` (prompt de reseñas del shell de tabs) para sacarlo del
camino crítico de la carga inicial.

**SEO** subió de 82-83 a **100** con dos arreglos globales: (a) `<meta name="description">`
en `index.html`; (b) `robots.txt` real en la raíz (`src/robots.txt` + entry en
`angular.json`). Antes `/robots.txt` caía al fallback SPA y devolvía el `index.html`,
que el validador de robots leía como 184 errores.

**Optimizaciones de front aplicadas (sin paquetes nuevos):**
1. Quitado `PreloadAllModules` — dejó de bajar admin/superadmin/kiosko al abrir el cliente.
2. Logo del splash y watermark de home → `favicon-180` (10 KB) en vez del logo 903×922 (81 KB).
3. Splash con fuentes de sistema — saca ~76 KB de Playfair/Nunito del camino crítico del cliente.
4. `minVisible` del splash 1100 → 300 ms.
5. Brotli en el server estático (representa un host real tipo CDN).

Móvil 90+ confiable exigiría **SSR/prerender** (`@angular/ssr` = paquete nuevo +
migración de builder), descartado por la regla de "no dependencias nuevas".

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
