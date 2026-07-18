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

### EB-03 — Gotchas de Eloquent en seeders (fillable, trait, autoasignacion de instancia_id)
- Qué pasó: al crear `ClientesDemoSeeder` (para poblar BD con datos de prueba del modulo Clientes), aparecieron 3 bloqueantes inesperados que NO se habian encontrado en seeders previos (`RolesSeeder`, `AdminTestUserSeeder`): (1) `Pedido::create()` tiraba error de mass-assignment porque `$fillable = []`, (2) `User::create()` no persistia `puntos_balance` aunque el campo existe en BD, (3) `Sucursal::create()` tiraba error de constraint NOT NULL en `instancia_id` aunque otros modelos lo autoasignan.
- Causa:
  - `Pedido` tiene `$fillable = []` a proposito (solo lectura del modulo Clientes) — necesita `forceFill()` para crear filas.
  - `User::$fillable` NO incluye `puntos_balance` — hay que asignarlo por propiedad + `save()` despues del `create()`.
  - `Sucursal::$fillable` NO incluye `instancia_id` y **el modelo `Sucursal` NO usa el trait `PerteneceAInstancia`** — hay que asignar `instancia_id` manualmente.
  - El trait `PerteneceAInstancia` (que si usan `User`/`Pedido`/`Producto`/`Insumo`/`Oferta`) autoasigna `instancia_id` leyendo `Auth::user()` en el hook `creating()` — en un seeder/comando de consola no hay usuario autenticado, asi que esto NO se autoasigna solo. Cualquier seeder que cree filas de estos modelos tiene que setear `instancia_id` manualmente.
- Regla:
  - Antes de escribir un seeder/comando que cree filas de un modelo, verificar: (1) si `$fillable` esta vacio o falta el campo que necesitas → usar `forceFill()` o asignacion por propiedad + `save()`; (2) si el modelo usa `PerteneceAInstancia` → setear `instancia_id` manualmente porque `Auth::user()` devuelve `null` fuera de requests web; (3) si el modelo es `Sucursal` → setear `instancia_id` manualmente porque no tiene el trait.
  - NO asumir que `Model::create($data)` funciona para todos los modelos — algunos tienen `$fillable` restringido a proposito.
- Fecha: 2026-07-18
