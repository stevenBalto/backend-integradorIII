# Antierrores — Backend

Catálogo de errores del backend. Cada vez que se corrige un error, se documenta aquí para que NO se repita en la próxima sesión.

Cómo se llena: una entrada por error corregido, con la regla a no romper.

Formato sugerido por entrada:
```
### EB-01 — <título corto>
- Qué pasó: <descripción del error>
- Causa: <por qué pasó>
- Regla: <qué hacer siempre / nunca para no repetirlo>
- Fecha: YYYY-MM-DD
```

### EB-01 — personal_access_tokens debe ser polimórfica (Sanctum)
- Qué pasó: el esquema reconstruido tenía `personal_access_tokens.user_id` (FK a `users`), pero Laravel Sanctum requiere columnas polimórficas `tokenable_type` + `tokenable_id`. Con `user_id`, `createToken()` falla.
- Causa: la tabla del ERD no siguió el formato estándar de Sanctum (polimórfico).
- Regla: para auth con Sanctum, `personal_access_tokens` SIEMPRE va polimórfica (`tokenable_type`/`tokenable_id`, sin FK a `users`). No modelarla con `user_id`.
- Fecha: 2026-06-28 (migración en `bd-doc/migracion_2026-06-28_sanctum_personal_access_tokens.sql`)

### EB-02 — Instancia de BD "fresca" sin el fix EB-01 aplicado ni roles sembrados
- Qué pasó: al retomar el proyecto en otra máquina/instancia de Postgres, `.env` apuntaba a una BD inexistente (`rooster_pizza_grill` en vez de `rooster_pizza`) y sin `DB_PASSWORD`. Al corregir eso, aparecieron dos problemas más en cascada: (1) `roles` y `users` estaban completamente vacíos (ni siquiera el login normal funcionaba), y (2) `personal_access_tokens` de ESA instancia todavía tenía el esquema viejo con `user_id` — el fix de EB-01 se había aplicado en otra BD, no en esta. Un CRUD nuevo (productos) parecía "no persistir" cuando en realidad ninguna request autenticada llegaba a completarse.
- Causa: la BD se carga por SQL manual (`rooster_pizza_bd.sql`), no por migraciones versionadas — cada instancia nueva de la BD requiere reaplicar a mano tanto el seed de roles como cualquier parche posterior (como el de EB-01). No hay una única fuente de verdad ejecutable que garantice que todas las instancias queden alineadas.
- Regla: al levantar el proyecto en una BD nueva (o sospechar que el entorno está desalineado), verificar en este orden ANTES de asumir que hay un bug de código: (1) `DB_DATABASE`/`DB_PASSWORD` correctos en `.env` (confirmar con `\dt` o consulta a `information_schema.tables`); (2) `roles` y al menos un usuario admin sembrados (`php artisan db:seed --class=RolesSeeder` + `AdminTestUserSeeder`); (3) columnas de `personal_access_tokens` son `tokenable_id`/`tokenable_type` (si no, reaplicar el forward de `migracion_2026-06-28_sanctum_personal_access_tokens.sql` a mano vía `DB::statement`). Si el login funciona pero cualquier request protegido devuelve 401/500 encriptado, sospechar de este checklist antes de tocar controllers/services.
- Fecha: 2026-07-10

### EB-03 — POST /api/register 500 tras la migración multi-tenant (instancia_id NOT NULL)
- Qué pasó: al probar el flujo de checkout de Pedidos, cualquier registro de cliente nuevo tiraba 500 (`Not null violation: instancia_id`). El registro llevaba semanas roto sin que nadie lo hubiera detectado porque los clientes de prueba ya existían de antes.
- Causa: `UserRepository::crearCliente()` nunca fue actualizado cuando el compañero migró `users.instancia_id` a NOT NULL (fase F0 del multi-tenant). El `User::create([...])` seguía sin esa columna.
- Regla: cualquier columna NOT NULL nueva en una tabla con `create()`/inserts existentes en el código hay que rastrearla contra TODOS los sitios que insertan en esa tabla, no solo los que se estén tocando en la tarea actual. `crearCliente()` ahora asigna `instancia_id = 1` (constante `UserRepository::INSTANCIA_DEFAULT`) porque hoy solo existe un negocio real; si en el futuro el registro público necesita elegir instancia, hay que rediseñar esto (no hay forma de que un cliente anónimo sepa a qué tenant pertenece sin un subdominio/selector).
- Fecha: 2026-07-16

### EB-04 — CACHE_STORE=database sin tabla `cache` rompía TODO throttle (403/500 silencioso)
- Qué pasó: al agregar `throttle:10,1` al nuevo endpoint público `GET /pedidos/buscar`, cualquier request tiraba 500 (`Undefined table: cache`). Investigando, resultó que `/forgot-password`/`/reset-password` (ya existentes, con `throttle:6,1` desde antes) tenían EXACTAMENTE el mismo problema — no era un bug nuevo, ya estaba roto.
- Causa: `.env` tenía `CACHE_STORE=database` pero nunca se creó la tabla `cache` (Laravel usa el cache para contar intentos del rate limiter). Sin esa tabla, cualquier ruta con `throttle:` explota en vez de simplemente no limitar.
- Regla: igual que `SESSION_DRIVER` (ver nota en `HiloActualBack.md` sesión 2026-06-29), este proyecto NO tiene tablas de `cache`/`jobs` — mantener `CACHE_STORE=file` y `QUEUE_CONNECTION=sync` en `.env` (ya está así en `.env.example`, pero el `.env` local se había desviado). Antes de agregar `throttle:` a una ruta nueva, probar esa ruta primero — si tira 500 con "Undefined table: cache", es este problema, no el código nuevo.
- Fecha: 2026-07-16

### Hallazgo (no corregido en esta sesión) — GET /api/productos público mezcla instancias sin sesión
- Qué pasó: al revisar el catálogo público para el carrito, `GET /api/productos` sin token de auth devuelve productos de TODAS las instancias mezclados, porque `PerteneceAInstancia` solo filtra si hay un usuario autenticado (`Auth::user()`) — un visitante anónimo no tiene `instancia_id`, así que el scope simplemente no aplica.
- Causa: decisión de arquitectura del multi-tenant del compañero — el catálogo público nunca se diseñó pensando en múltiples instancias con catálogos propios navegables sin login.
- Por qué no se arregla aquí: hoy es inofensivo (solo la instancia 1 tiene productos reales), y arreglarlo implica decidir un modelo de acceso público multi-tenant (¿subdominio por negocio? ¿selector de negocio en el home?) que le corresponde a quien diseñó el multi-tenant, no a esta tarea de Pedidos.
- Fecha: 2026-07-16
