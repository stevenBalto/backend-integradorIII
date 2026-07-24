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

### EB-05 — Sucursales sin CRUD: cualquier instancia nueva queda con selector de sucursal vacío
- Qué pasó: el usuario reportó que el selector de sucursal del carrito "no muestra sucursales acorde a la instancia". Al investigar, `sucursales` solo tenía 1 fila (sembrada a mano para la instancia 1 en la migración de Pedidos) y `SucursalController` solo tenía `index()` (lectura). Cualquier instancia nueva (había una segunda, "CHRISTIAN", creada por el compañero vía superadmin) queda con CERO sucursales sin forma de crear una — el selector del carrito para esos usuarios está vacío por diseño, no por un bug de filtrado (el filtrado por `instancia_id` vía `PerteneceAInstancia` siempre funcionó correctamente).
- Causa: al construir el módulo Pedidos (sesión 2026-07-16) se sembró 1 sucursal solo para destrabar el flujo de la instancia 1, y el CRUD completo de Sucursales quedó explícitamente fuera de alcance ("módulo futuro", ver `HiloActualBack.md`). Nadie conectó ese pendiente con el hecho de que el multi-tenant del compañero ya permitía crear instancias nuevas sin sucursal propia.
- Regla: cuando se agrega la capacidad de crear tenants/instancias nuevas (multi-tenant), revisar qué tablas "de arranque" (sucursales, categorías, etc.) dependen de un seed manual y no tienen su propio flujo de alta — un tenant nuevo debe poder autoabastecerse de esos datos básicos sin intervención manual en la BD. Se agregó `POST`/`PUT /admin/sucursales[/{id}]` (CRUD mínimo, ver sesión 2026-07-17) para cerrar este hueco.
- Fecha: 2026-07-17

### Hallazgo (no corregido en esta sesión) — GET /api/productos público mezcla instancias sin sesión
- Qué pasó: al revisar el catálogo público para el carrito, `GET /api/productos` sin token de auth devuelve productos de TODAS las instancias mezclados, porque `PerteneceAInstancia` solo filtra si hay un usuario autenticado (`Auth::user()`) — un visitante anónimo no tiene `instancia_id`, así que el scope simplemente no aplica.
- Causa: decisión de arquitectura del multi-tenant del compañero — el catálogo público nunca se diseñó pensando en múltiples instancias con catálogos propios navegables sin login.
- Por qué no se arregla aquí: hoy es inofensivo (solo la instancia 1 tiene productos reales), y arreglarlo implica decidir un modelo de acceso público multi-tenant (¿subdominio por negocio? ¿selector de negocio en el home?) que le corresponde a quien diseñó el multi-tenant, no a esta tarea de Pedidos.
- Fecha: 2026-07-16

### EB-06 — Gotchas de Eloquent en seeders (fillable, trait, autoasignacion de instancia_id)
- Qué pasó: al crear `ClientesDemoSeeder` (para poblar BD con datos de prueba del modulo Clientes), aparecieron 3 bloqueantes inesperados que NO se habian encontrado en seeders previos (`RolesSeeder`, `AdminTestUserSeeder`): (1) `Pedido::create()` tiraba error de mass-assignment porque `$fillable = []` (en la rama donde se escribió este seeder, `Pedido` era de solo lectura para el módulo Clientes — al mergear con el módulo Pedidos, `Pedido::$fillable` ya tiene las columnas reales, ver `HiloActualBack.md`), (2) `User::create()` no persistia `puntos_balance` aunque el campo existe en BD, (3) `Sucursal::create()` tiraba error de constraint NOT NULL en `instancia_id` porque, EN ESE MOMENTO, `Sucursal` no usaba el trait `PerteneceAInstancia` (eso se agregó en paralelo en la sesión de Pedidos del 2026-07-16 — tras el merge, `Sucursal` YA autoasigna `instancia_id`, este workaround puntual puede no ser necesario si se vuelve a correr el seeder desde cero).
- Causa:
  - `User::$fillable` NO incluye `puntos_balance` — hay que asignarlo por propiedad + `save()` despues del `create()`.
  - El trait `PerteneceAInstancia` (que usan `User`/`Pedido`/`Producto`/`Insumo`/`Oferta`/`Sucursal`/`Extra`) autoasigna `instancia_id` leyendo `Auth::user()` en el hook `creating()` — en un seeder/comando de consola no hay usuario autenticado, asi que esto NO se autoasigna solo. Cualquier seeder que cree filas de estos modelos tiene que setear `instancia_id` manualmente.
- Regla:
  - Antes de escribir un seeder/comando que cree filas de un modelo, verificar: (1) si `$fillable` esta vacio o falta el campo que necesitas → usar `forceFill()` o asignacion por propiedad + `save()`; (2) si el modelo usa `PerteneceAInstancia` → setear `instancia_id` manualmente porque `Auth::user()` devuelve `null` fuera de requests web.
  - NO asumir que `Model::create($data)` funciona para todos los modelos — algunos tienen `$fillable` restringido a proposito.
- Fecha: 2026-07-18

### EB-08 — listarDeInstancia()/buscarEnInstancia() del panel Usuarios no excluían el rol `cliente`
- Qué pasó: cualquier cliente que se registraba desde el login público aparecía mezclado en el panel admin "Usuarios y roles" junto al staff (`admin_sede`/`super_admin`).
- Causa: `UserRepository::listarDeInstancia()`/`buscarEnInstancia()` solo filtraban por `instancia_id`. Como `crearCliente()` siempre asigna `instancia_id = 1` (mismo tenant que el staff, ver EB-03), todo cliente nuevo cumplía ese filtro. El módulo "Clientes" (`ClienteRepository`) sí filtraba por rol desde el inicio — el hueco era exclusivo del módulo Usuarios.
- Regla: cuando dos módulos admin distintos leen de la misma tabla `users` pero representan conceptos de negocio distintos (staff vs. clientes), el filtro por rol tiene que estar en CADA query que alimenta cada panel, no asumir que "filtrar por instancia" ya es suficiente aislamiento — instancia y rol son dos dimensiones independientes. Cualquier método nuevo que liste/busque usuarios para el panel admin debe excluir explícitamente `cliente` (`whereHas('role', fn ($q) => $q->where('nombre', '!=', 'cliente'))`).
- Fecha: 2026-07-23

### EB-07 — listarAdmin() de Pedidos nunca cargaba `detalles` (tabla admin casi vacía)
- Qué pasó: al probar el panel de Pedidos, la tabla se veía casi vacía (modalidad, estado y pago en blanco) y los KPIs mostraban 0 pese a haber filas visibles. `GET /admin/pedidos` respondía sin la key `items` en absoluto.
- Causa: `PedidoRepository::listarAdmin()` solo hacía `->with(['cliente', 'sucursal'])`, nunca `detalles.producto`/`detalles.extras.extra`. `PedidoAdminResource` usa `PedidoDetalleResource::collection($this->whenLoaded('detalles'))` — patrón correcto de Laravel que omite la key completa cuando la relación no está cargada (no un error, comportamiento esperado de `whenLoaded()`). El frontend hace `{{ p.items.length }}` sin guardas, asumiendo que `items` siempre existe — con la key ausente, ese binding rompía el render de toda la fila. Este bug ya existía antes de esta sesión (nadie lo había notado porque nadie probó la tabla completa con datos reales en el navegador).
- Regla: cuando un Resource usa `Collection::collection($this->whenLoaded('relacion'))`, verificar SIEMPRE que el método del Repository que alimenta ese endpoint realmente hace eager-load de esa relación — el patrón es fácil de copiar entre Resources (`buscarPorId()` sí la cargaba, `listarAdmin()` no) sin que nadie note la inconsistencia hasta que el frontend intenta leer el campo. Preferir probar el LISTADO real en navegador, no solo el detalle individual, antes de dar un módulo de tabla por terminado.
- Fecha: 2026-07-19

### EB-08 — Endpoint público multi-tenant: sin sesión el trait no asigna instancia (patrón centinela)
- Qué pasó: al crear el endpoint público `POST /pedidos/invitado`, un pedido creado sin sesión iba a fallar con `instancia_id NOT NULL` — el trait `PerteneceAInstancia` lee `Auth::user()->instancia_id` y sin sesión es `null`. Además `pedidos.cliente_id` es NOT NULL, así que un invitado "sin usuario" tampoco encaja en el esquema.
- Solución (sin tocar el esquema): un usuario **centinela** "Invitado" por instancia (`User::EMAIL_INVITADO = 'invitado@rooster.local'`, `activo=false`) hace de `cliente_id`; el nombre real del cliente va en `pedidos.nombre_cliente`. En el controller, `Auth::setUser($centinela)` SOLO durante la petición (sin sesión persistente, sin cookies) para que el trait asigne la instancia a la orden y sus detalles. El centinela se resuelve con `User::withoutGlobalScope('instancia')` — el scope se quita por **NOMBRE** (`'instancia'`), no por clase (`addGlobalScope('instancia', ...)`).
- Regla: para exponer públicamente la creación de un registro tenant-scoped, NO volver la FK nullable ni el `instancia_id` nullable — preferir un **centinela + `Auth::setUser()` acotado a la petición**. Marcar el registro (ej. `es_invitado`) comparando el email del cliente con `User::EMAIL_INVITADO` vía `relationLoaded('cliente')` (sin disparar un lazy-load extra en el Resource).
- Fecha: 2026-07-24
