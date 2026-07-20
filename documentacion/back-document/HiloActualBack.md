# Hilo Actual — Backend

Estado actual del backend. Se actualiza al cerrar cada sesión, para que el siguiente dev sepa qué hacer y qué NO tocar (porque otro ya lo está trabajando y aún no hizo push). También sirve para conocer el estado sin escanear todo el código.

Cómo se llena: al terminar una sesión, anotá qué se hizo, qué quedó pendiente y qué está reservado.

Formato sugerido:
```
## Sesión YYYY-MM-DD — <dev>
- Hecho: <qué se hizo / ajustó / borró>
- En progreso / NO tocar: <archivos o módulos que otro dev tiene>
- Pendiente: <qué sigue>
```

## Sesión 2026-06-28 — Módulo 1: Auth (backend)
- Hecho:
  - `personal_access_tokens` alineada a Sanctum (polimórfica). Ver `bd-doc/migracion_2026-06-28_sanctum_personal_access_tokens.sql` y `AntierroresBack EB-01`.
  - Modelos: `User` (campos reales + HasApiTokens + SoftDeletes + relaciones role/sucursal + helpers de rol), `Role`, `Sucursal`.
  - `RolesSeeder` (super_admin, admin_sede, cliente) — ya sembrado en la BD.
  - Capa auth: `RegisterRequest`/`LoginRequest`, `RegistrarUsuarioDTO`/`CredencialesDTO`, `UserRepository`/`RoleRepository`, `AuthService`, `AuthController`, `UserResource`.
  - Rutas: `POST /api/register`, `POST /api/login`, `POST /api/logout` (auth:sanctum), `GET /api/me`. API registrada en `bootstrap/app.php`.
  - Probado con curl: register 201, login 200, me OK, login inválido 422, password débil 422. CORS OK (origins *).
- Pendiente:
  - Localizar a español los mensajes de complejidad de password (hoy en inglés, regla nativa de Laravel).
  - "Olvidé mi contraseña" y Google OAuth (fast-follow).
  - Seed de un usuario admin/super_admin de prueba.
- NO TOCAR / nota: la BD se mantiene por SQL. NO correr `php artisan migrate` (choca con las tablas ya cargadas).

## Sesión 2026-06-29 — Ajustes de arranque + cómo correr
- Hecho:
  - `.env`: `SESSION_DRIVER=database` → `file` (no existe tabla `sessions`; la raíz `/` tiraba `Undefined table: sessions`). Aplicado `php artisan config:clear`. Ver AntierroresBack si aplica.
  - `.env.example` alineado al stack real: `pgsql`, `rooster_pizza`, `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync` (sin claves reales).
  - Creado `documentacion/COMO-CORRER.md` con los pasos puntuales (BD + backend + frontend + prueba del módulo auth).
  - Módulo 1 (auth) verificado end-to-end desde el navegador: registro → cliente en BD → login → logout.
- Pendiente: igual que la sesión anterior (localizar mensajes de password a español; "Olvidé mi contraseña"; Google OAuth fast-follow con columnas `google_id`+`auth_provider` ya pre-aprobadas; seed de admin de prueba).
- NO TOCAR / nota: `.env` está gitignored (clave de BD nunca se commitea). `SESSION_DRIVER` debe quedar en `file`.

## Sesión 2026-07-10 — Módulo 2: Catálogo de productos (CRUD + roles + Cloudinary)
- Hecho:
  - **CRUD completo de productos**, patrón Controller-Service-Repository + DTOs + Resources (mismo estilo que Auth): `Producto`/`Categoria` (modelos, `Producto` con `SoftDeletes`), `ProductoRepository`/`CategoriaRepository`, `ProductoService`, `ProductoController`/`CategoriaController`, `CrearProductoDTO`/`ActualizarProductoDTO`, `ProductoResource`/`CategoriaResource`, `StoreProductoRequest`/`UpdateProductoRequest`.
  - **Sin migraciones nuevas**: `productos`/`categorias` ya existían en el SQL base (`rooster_pizza_bd.sql`) con exactamente los campos que pedía la tarea (incluido `imagen_url` nullable, ya contemplado como placeholder). Se construyó directo contra el esquema existente.
  - **Middleware de rol** `EnsureUserHasRole` (alias `role`, registrado en `bootstrap/app.php`, Laravel 11 sin `Kernel.php`). Reutiliza `esSuperAdmin()`/`esAdminSede()`/`esCliente()` de `User`, no compara strings sueltos.
  - **Rutas**: `GET /api/productos` y `/api/categorias` públicos (filtran `disponible`/`activa`). `GET/POST/PUT|PATCH/DELETE /api/admin/productos[...]` bajo `auth:sanctum` + `role:super_admin,admin_sede`.
  - **Cloudinary**: cuenta nueva y dedicada al proyecto (decisión: separada de cuentas personales, para poder transferirla al cliente después). Paquete `cloudinary/cloudinary_php`. `CloudinaryService` sube la imagen del producto (`folder: rooster-pizza/productos`) y devuelve `secure_url`. Subida vía backend (signed), no unsigned desde el frontend — reutiliza la protección por rol ya existente. Credenciales en `config/services.php` + `.env` (gitignored) + placeholders en `.env.example`. Al editar sin adjuntar imagen nueva, se conserva la `imagen_url` existente (no se pisa con null).
  - **`AdminTestUserSeeder`** (idempotente, `updateOrCreate`) — reemplaza el usuario admin creado a mano por tinker en una sesión anterior. Registrado en `DatabaseSeeder`. Credenciales de prueba: `admin@rooster.com` / `admin123456`, rol `super_admin`, acceso total (sin mínimo privilegio por módulo todavía — queda para cuando se trabaje Usuarios y roles).
  - Verificado end-to-end por curl y desde el navegador: crear/editar/eliminar (soft delete, `deleted_at` confirmado en BD) producto con y sin imagen, filtro por categoría, Home del cliente consumiendo `GET /productos` con imágenes reales.
- Pendiente:
  - Modal de detalle de producto (admin y home) ya construido en el front, pero **falta conectar el botón "Añadir al carrito"** — no hay módulo de Carrito/Pedir real todavía (hoy muestra un toast placeholder).
  - Guard de rol real para la ruta `/admin` en el frontend (sigue sin guard; ver `HiloActualFront.md`).
  - Mínimo privilegio por módulo para roles (hoy `admin_sede` tiene el mismo acceso que `super_admin` en productos) — deferido a cuando se trabaje Usuarios y roles.
- NO TOCAR / nota: la BD se sigue manteniendo por SQL, no por `php artisan migrate` (ver AntierroresBack EB-02). Las credenciales de Cloudinary viven solo en `.env` de cada máquina — cada dev que clone el repo necesita pedirlas y completarlas a mano (no están en `.env.example` con valor real).

## Sesion 2026-07-12 — Modulo 3: Ofertas y Cupones (CRUD admin)
- Hecho:
  - **CRUD completo de Ofertas y Cupones**, patron Controller-Service-Repository + DTOs + Resources (mismo estilo que Productos y Auth):
    - Modelos: `Oferta` (relacion M-N con `productos` via pivote `oferta_producto`), `Cupon`. **Sin SoftDeletes** en ambos — el borrado es fisico real (DELETE), no soft delete (las tablas no tienen columna `deleted_at`).
    - Repositories: `OfertaRepository` (con `sync()` para productos asociados), `CuponRepository` (con `buscarPorCodigo()` para validar unicidad).
    - Services: `OfertaService` (valida fechas, valida que producto_ids existan), `CuponService` (valida fechas, fuerza codigo a mayusculas, valida unicidad de codigo).
    - DTOs: `CrearOfertaDTO`/`ActualizarOfertaDTO` (incluye `productoIds`), `CrearCuponDTO`/`ActualizarCuponDTO` (aplica `strtoupper` al codigo).
    - Form Requests: `StoreOfertaRequest`/`UpdateOfertaRequest`, `StoreCuponRequest`/`UpdateCuponRequest`. Validaciones completas: tipo_descuento in ['porcentaje','precio_fijo'], valor max:100 condicional (solo si tipo_descuento='porcentaje'), unicidad de codigo cupon, fechas validas. Mensajes en espanol.
    - Resources: `OfertaResource` (con `productos` via `whenLoaded` y `productos_count`), `CuponResource`.
    - Controllers: `OfertaController`, `CuponController` — metodos `indexAdmin`, `show`, `store`, `update`, `destroy`. Sin endpoints publicos (solo admin).
    - Rutas: `GET/POST/PUT|PATCH/DELETE /api/admin/ofertas[/{id}]` y `/api/admin/cupones[/{id}]` bajo `auth:sanctum` + `role:super_admin,admin_sede`.
  - **Sin migraciones nuevas**: `ofertas`, `oferta_producto` y `cupones` ya existian en el SQL base (`rooster_pizza_bd.sql`). Construido directo contra el esquema existente.
  - **Sin Cloudinary**: estos endpoints no manejan imagenes, van con JSON normal (no multipart/form-data).
  - Verificado end-to-end por curl: crear/editar/eliminar oferta y cupon, validaciones de fechas, validacion de porcentaje max 100, unicidad de codigo, conversion a mayusculas. **Borrado fisico confirmado** — despues de DELETE, las filas ya no existen en BD (no quedaron con deleted_at).
- Pendiente:
  - Conectar ofertas con productos reales cuando se pruebe con datos (hoy Cloudinary falla en el endpoint publico de productos por falta de credenciales, pero el CRUD de ofertas funciona independiente).
  - Modal de aplicacion de ofertas/cupones al carrito (frontend) — no hay modulo de Carrito todavia.
  - Endpoint publico para validar un cupon en el checkout (futuro modulo Pedidos).
- NO TOCAR / nota: la BD se sigue manteniendo por SQL, no por `php artisan migrate`. No agregar columna `deleted_at` a ofertas/cupones — el diseño actual es borrado fisico intencional.

## Sesion 2026-07-13 — Modulo 4: Inventario (insumos / materia prima)
- Hecho:
  - **CRUD de insumos + toma fisica auditada**, patron Controller-Service-Repository + DTOs + Resources (mismo estilo que Productos/Ofertas/Cupones). Controla materia prima (carnes, queso, harina...), NO productos del menu.
    - Modelos: `Insumo` (`SoftDeletes`, cast `cantidad_actual`/`stock_minimo` a `decimal:2`, relacion `movimientos()` hasMany, helper `bajoStock()`), `InsumoMovimiento` (log inmutable, **sin SoftDeletes**; la tabla solo tiene `created_at` → `const UPDATED_AT = null`; relaciones `insumo()` y `usuario()` via FK `user_id`).
    - Repositories: `InsumoRepository` (`listarTodos` sin eliminados orderBy nombre, `buscarPorId`, `crear`, `actualizar` solo nombre/unidad/stock_minimo, `actualizarCantidad` usado solo por el service de toma fisica, `eliminar` soft delete), `InsumoMovimientoRepository` (`crear`, `listarPorInsumo` desc con `usuario`).
    - Service: `InsumoService` — `crear`/`actualizar`/`eliminar`/`listarTodos`/`buscarPorId` (lanza `ValidationException` "El insumo no existe." si no lo encuentra), `registrarTomaFisica($insumoId,$cantidadContada,$nota,$userId)` (dentro de `DB::transaction`: guarda `cantidad_anterior`, calcula `diferencia`, fija `cantidad_actual = contada`, crea el `InsumoMovimiento` tipo `toma_fisica`; devuelve `['insumo'=>..., 'movimiento'=>...]`), `listarMovimientos`.
    - DTOs: `CrearInsumoDTO` (cantidad_actual default 0), `ActualizarInsumoDTO` (**SIN cantidad_actual** a proposito).
    - Form Requests: `StoreInsumoRequest`, `UpdateInsumoRequest` (campos `sometimes`, sin cantidad_actual), `TomaFisicaRequest` (cantidad_contada required numeric min:0, nota nullable max:255). Mensajes en espanol.
    - Resources: `InsumoResource` (incluye `bajo_stock` bool desde el helper), `InsumoMovimientoResource` (`usuario` via `whenLoaded`, solo id+nombre).
    - Controller: `InsumoController` — `index`, `show`, `store` (201), `update`, `destroy` (mensaje ES), `tomaFisica` (usa `$request->user()->id`, devuelve `{data:{insumo, movimiento}}` 200), `movimientos`.
    - Rutas: `GET/POST/PUT|PATCH/DELETE /api/admin/insumos[/{id}]` + `POST /api/admin/insumos/{id}/toma-fisica` + `GET /api/admin/insumos/{id}/movimientos`, todas bajo `auth:sanctum` + `role:super_admin,admin_sede`. **Sin endpoints publicos** (Inventario es 100% admin).
  - **Decision clave respetada**: `cantidad_actual` NUNCA se edita por PUT/PATCH normal — solo cambia via toma fisica (queda auditado en `insumo_movimientos`). El PUT ignora `cantidad_actual` en silencio (no rompe).
  - **Sin migraciones nuevas**: `insumos` e `insumo_movimientos` ya estaban aplicadas por SQL (ver `bd-doc/migracion_2026-07-13_insumos.sql`). Construido directo contra el esquema.
  - Verificado end-to-end por curl (login admin@rooster.com): crear (201, bajo_stock false), toma fisica 50→8 (diferencia -42, bajo_stock pasa a true, movimiento con usuario), PUT con cantidad_actual=999 → **ignorado** (quedo en 8, nombre/stock_minimo si cambiaron, sin error), soft delete (`deleted_at` confirmado en BD, index deja de listarlo, la fila de `insumo_movimientos` **persiste**), 422 validacion en espanol, 422 insumo inexistente, 401 sin auth.
- En progreso / NO tocar: el **frontend de este modulo lo esta construyendo otro agente en paralelo** (mismo contrato de API). Solo se toco el repo backend — no deberian pisarse.
- Pendiente:
  - Otros tipos de movimiento ademas de `toma_fisica` (consumo automatico al vender, entrada por compra) — hoy solo se registra ajuste manual por conteo.
  - Vincular consumo de insumos con el modulo Pedidos cuando exista (descontar stock al confirmar un pedido).
  - Filtro/endpoint de "insumos bajo stock" si el front lo necesita (hoy `bajo_stock` viene por insumo en el listado, el front puede filtrar en cliente).
- NO TOCAR / nota: la BD se sigue manteniendo por SQL, no por `php artisan migrate`. `InsumoMovimiento` es un log inmutable: no agregarle SoftDeletes ni `updated_at`. `cantidad_actual` no debe volverse editable por el PUT normal — esa es la garantia de auditoria del modulo.

## Sesion 2026-07-13 (cont.) — Inventario: refinamiento (conteo de movimientos + validacion stock_minimo)
- Hecho:
  - `InsumoRepository::listarTodos()` ahora hace `withCount('movimientos')` (evita N+1) para exponer `tiene_movimientos` en el listado; `InsumoService::buscarPorId()` agrega `loadCount('movimientos')` de respaldo si el atributo no viene ya cargado (cubre show/update/toma-fisica).
  - `InsumoResource`: nuevo campo `'tiene_movimientos' => ($this->movimientos_count ?? 0) > 0` — el frontend lo usa para mostrar/ocultar el boton de historial por fila.
  - **Validacion cruzada `stock_minimo` <= `cantidad_actual`** via `withValidator()`:
    - `StoreInsumoRequest`: compara contra `cantidad_actual` enviada (o 0 si se omite) → "El stock mínimo no puede ser mayor a la cantidad inicial."
    - `UpdateInsumoRequest`: busca `Insumo::find($this->route('id'))->cantidad_actual` (la real en BD, porque el PUT no la recibe) → "El stock mínimo no puede ser mayor a la cantidad actual."
  - Verificado por curl (CREATE): `POST /admin/insumos` con `cantidad_actual:100, stock_minimo:150` → 422 con el mensaje esperado.
- Pendiente:
  - Correr la prueba equivalente por curl para el path UPDATE (`PUT /admin/insumos/{id}` con `stock_minimo` > `cantidad_actual` real del insumo) — la regla ya esta implementada, falta el curl explicito de confirmacion.
- NO TOCAR / nota: igual que la sesion anterior — `cantidad_actual` sigue sin ser editable por PUT; la nueva validacion de `stock_minimo` es adicional, no cambia esa garantia.

## Sesion 2026-07-16 — Modulo 5: Pedidos (carrito, checkout, seguimiento, admin) + tamanos/acompanamientos de producto
- Hecho:
  - **Fix prerequisito (EB-03)**: `UserRepository::crearCliente()` no asignaba `instancia_id` (NOT NULL desde el multi-tenant del companero) — `POST /api/register` tiraba 500 para CUALQUIER cliente nuevo. Arreglado con constante `INSTANCIA_DEFAULT = 1`.
  - **Fix prerequisito (EB-04)**: `.env` tenia `CACHE_STORE=database`/`QUEUE_CONNECTION=database` sin las tablas `cache`/`jobs` — cualquier ruta con `throttle:` (incluidas las YA EXISTENTES `/forgot-password`/`/reset-password`) tiraba 500. Cambiado a `CACHE_STORE=file`/`QUEUE_CONNECTION=sync` (igual que `.env.example`).
  - **Esquema nuevo** (aprobado explicitamente por el usuario, ver plan de sesion): tabla `producto_tamanos` (tamanos opcionales por producto, ej. Personal/Mediana/Grande con precio propio) + `detalle_pedido.producto_tamano_id`/`tamano_nombre` (snapshot) + `pedidos.codigo`/`pagado`/`pagado_en`. `extras`/`detalle_pedido_extras` (ya existian, ligadas a `categoria_id`) se reutilizan tal cual como "acompanamientos" — sin tabla nueva. `Sucursal` gano el trait `PerteneceAInstancia` (la columna ya existia) + se sembro 1 fila para la instancia 1 (la tabla estaba 100% vacia, bloqueando cualquier pedido). Esquema: 28 → **29 tablas**. Migracion: `bd-doc/migracion_2026-07-16_pedidos_tamanos_extras.sql`.
  - **Producto + tamanos**: `ProductoTamano` (modelo, soft delete), `Producto::tamanos()`, `ProductoResource` expone `tamanos[]` y `extras[]` (extras de la categoria del producto). `StoreProductoRequest`/`UpdateProductoRequest` decodifican un campo `tamanos` (JSON string, porque el upload de imagen ya usa multipart) y `ProductoRepository::sincronizarTamanos()` recrea las filas en cada guardado (seguro: `detalle_pedido.producto_tamano_id` es `ON DELETE SET NULL`, no hay perdida de historial).
  - **Modulo Extra (acompanamientos)**: CRUD admin completo (`Extra`, `ExtraRepository/Service`, `ExtraController`, rutas `/admin/extras`). `eliminar()` bloquea con 422 si el extra ya fue usado en algun pedido (`estaReferenciado()` via `detalle_pedido_extras`) — sugiere desactivarlo en su lugar.
  - **Modulo Pedido** (el grueso): `Pedido`/`DetallePedido`/`DetallePedidoExtra`/`PedidoHistorialEstado`/`PuntosMovimiento` (modelos), `PedidoRepository` (incluye `buscarPorCodigo()`/`existeCodigo()` SIN scope de tenant — el codigo es global y unico, necesario para el lookup publico), `PedidoService`:
    - `crear()`: valida sucursal activa, cada producto disponible, cada tamano pertenece al producto y esta activo, cada extra pertenece a la MISMA categoria del producto y esta disponible — nunca confia en precios del cliente, los recalcula server-side. Genera codigo unico (8 chars, alfabeto sin 0/O/1/I/L, formato `XXXX-XXXX`, reintento ante colision). Calcula `puntos_ganados` (1 punto por ₡1000 del total) y los suma a `users.puntos_balance` + registra en `puntos_movimientos` (tablas que existian sin usar). Todo en una transaccion.
    - `cambiarEstado()`: maquina de estados `pendiente→en_proceso|cancelado`, `en_proceso→listo|cancelado`, `listo→entregado|cancelado`, `entregado`/`cancelado` terminales. Cada cambio escribe `pedido_historial_estado`.
    - `registrarPago()`: solo si `estado=entregado` y no pagado ya — marca `pagado`+`pagado_en`.
    - `estimarHoraLista()`: NO se persiste, se calcula al vuelo (15 min para llevar / 20 comer aqui, +2 min por item sobre 3, tope 45) — siempre da el mismo resultado porque los datos de entrada no cambian.
  - **Rutas**: cliente (`POST /pedidos`, `GET /pedidos/mios[/{id}]`, todas `auth:sanctum`), publica (`GET /pedidos/buscar?codigo=`, sin auth, `throttle:10,1`, respuesta minima SIN nombre/precios/detalle del carrito — evita fuga de datos), admin (`GET/POST /admin/pedidos*`, prefix ya existente con `role:super_admin,admin_sede`).
  - Verificado end-to-end por curl (login real, no solo el del subagente que construyo el modulo): producto con 2 tamanos + extra → `GET /productos` publico los expone → pedido con tamano+extra → total correcto (precio_tamano*cantidad + precio_extra*cantidad) → codigo formato correcto → lookup publico sin PII → transicion invalida rechazada (422) → recorrido completo de estados con historial creciendo → pago bloqueado antes de `entregado`, aceptado despues → borrado de extra usado rechazado (422) → `puntos_balance` incrementado correctamente.
- Pendiente:
  - Sin integracion de cupones en la creacion de pedidos (campo `cupon_id` se deja null a proposito, fuera de alcance de esta sesion).
  - Sin descuento automatico de `insumos` al confirmar un pedido (queda para cuando se conecte Inventario con Pedidos).
  - CRUD completo de Sucursales (hoy solo hay listado minimo + 1 fila sembrada a mano) — modulo futuro aparte, ya trackeado en `ContextoGeneral.md`.
- NO TOCAR / nota: `GET /api/productos` publico sigue sin filtrar por instancia para requests sin sesion (hallazgo documentado en `AntierroresBack.md`, no es un bug de esta sesion, es una decision de arquitectura del multi-tenant que le toca a quien lo diseño). `cupon_id` en pedidos queda null a proposito — no inventar logica de cupones sin que se pida.

## Sesion 2026-07-17 — Extras generales/asignacion por producto + CRUD minimo de Sucursales
- Contexto: el usuario reporto 3 problemas tras probar el modulo Pedidos: (1) selector de sucursal del carrito "horrible" y vacio para instancias que no son la 1, (2) pedidos de prueba visibles en el admin + boton de expandir poco visible en la fila, (3) faltaba forma de crear extras generales (aplican a TODOS los productos) o asignar una extra puntualmente a un producto especifico (no solo por categoria).
- Diagnostico previo a tocar codigo: se confirmo con `DB::table(...)` que hay 2 instancias (`Rooster Pizza & Grill` id=1, `CHRISTIAN` id=2, creada por el companero via superadmin) pero SOLO 1 sucursal sembrada (instancia 1) — la instancia 2 tenia CERO sucursales y no existia forma de crear una (solo `index()`, sin `store`/`update`). Ese es el bug real detras del selector vacio.
- Limpieza: se borraron los 4 pedidos de prueba de la sesion anterior (+ cascada + reversion de `puntos_balance` de los 2 usuarios de prueba a 0).
- **Esquema nuevo** (aprobado explicitamente, ver flujo de aprobacion en la conversacion): `extras.categoria_id` paso a NULLABLE + columna nueva `extras.es_general boolean NOT NULL DEFAULT false` + CHECK `chk_extras_general_xor_categoria` (nunca pueden coexistir ni faltar ambas). Tabla nueva `producto_extras` (pivote producto_id+extra_id, UNIQUE, ambas FK ON DELETE CASCADE) para asignacion puntual. Esquema: 29 → **30 tablas**. Migracion: `bd-doc/migracion_2026-07-17_extras_generales_y_asignacion.sql`.
- **Resolucion de "extras disponibles para un producto"**: `es_general=true` OR `categoria_id = producto.categoria_id` OR existe fila en `producto_extras` — implementado en `ProductoRepository::cargarExtrasDeCategoria()`/`cargarExtrasDeProducto()` (sin N+1: 1 query de generales + 1 agrupada por categoria + 2 del pivote, merge+dedupe por `extra.id` en PHP) y reutilizado en `ExtraController`/`ExtraService` (nuevos `show()`, `asignarAProducto()`, `desasignarDeProducto()`, `productos_asignados` en `ExtraResource` via `whenLoaded`).
- **Bug real corregido en `PedidoService::procesarItem()`**: la validacion de extras del carrito SOLO aceptaba `categoria_id === producto.categoria_id` — con el esquema nuevo, un pedido valido usando una extra general o asignada puntualmente hubiera sido rechazado con 422 falso. Se aplico la misma logica de 3 condiciones.
- **CRUD minimo de Sucursales**: `SucursalService`/`SucursalRepository::crear()`/`actualizar()` (nuevo), `indexAdmin()` (incluye inactivas, a diferencia de `index()` publico que solo muestra activas), rutas `POST`/`PUT /admin/sucursales[/{id}]`. `instancia_id` SIEMPRE lo asigna el trait `PerteneceAInstancia` desde el usuario autenticado — nunca se acepta del request (verificado con curl: mandar `instancia_id` distinto en el body no tiene efecto).
- **Desviacion del contrato original**: `sucursales.direccion` es **NOT NULL** en la BD real (no nullable como se asumio al planear) — `StoreSucursalRequest`/`UpdateSucursalRequest` la hacen `required` para devolver 422 limpio en vez de 500. El formulario del frontend (`configuracion.page.ts`) tenia el validador desalineado (sin `Validators.required` en `direccion`) — corregido en la misma sesion.
- Verificado por curl (login real, no solo el reporte del subagente): extra general 201 (`categoria_id=null`), extra de categoria 201, validacion cruzada 422 en ambos sentidos (general+categoria juntos, ninguno de los dos), asignar extra de categoria a producto de OTRA categoria 200 + aparece en `GET /productos` publico sin duplicados, asignar una extra general a un producto → 422 ("ya aplica a todos"), pedido usando una extra general en un producto de categoria distinta → 201 (antes hubiera sido 422 falso), crear/editar sucursal 201/200 con `instancia_id` correcto, `GET /admin/sucursales` incluye inactivas y `GET /sucursales` (cliente) las excluye. Todos los datos de prueba de esta verificacion fueron borrados al cierre de la sesion (pedidos, extras, sucursal, categoria/producto de prueba) para no ensuciar el catalogo real.
- Pendiente:
  - CRUD completo de Sucursales sigue siendo minimo (crear/editar, sin eliminar — no se pidio, y borrar una sucursal con pedidos historicos asociados necesitaria la misma proteccion 422 que ya tiene `Extra::eliminar()`).
  - Sin UI de superadmin para pre-sembrar una sucursal al crear una instancia nueva — cada tenant nuevo debe crear su primera sucursal manualmente desde Configuracion.
- NO TOCAR / nota: `GET /sucursales` (cliente, fuera del grupo admin) sigue usando `index()`/`listarActivas()` sin cambios — no confundir con `indexAdmin()` que es solo para el panel.

## Sesion 2026-07-18 — Merge de historias divergentes (Pedidos/Extras/Sucursales vs Home-vitrina/Clientes)
- Contexto: mientras esta sesion construia Extras/Sucursales (arriba), el compañero trabajaba en paralelo, sin haber bajado el modulo Pedidos, en 3 features propias (secciones abajo, sin editar). Ambas ramas divergieron del mismo commit base y se mergearon hoy.
- Conflictos reales resueltos (no solo docs):
  - `app/Models/Pedido.php`: conflicto add/add — cada rama creo su propia version. Se combinaron: `$fillable` completo + `casts` de `pagado`/`pagado_en` + relaciones `detalles()`/`historial()` (de esta sesion) MAS la relacion `cupon()` (del compañero, util, sin choque de proposito).
  - `routes/api.php`: sin choque real, solo bloques nuevos de ambos lados en el mismo grupo admin — concatenados.
  - `app/Http/Resources/ProductoResource.php`: auto-merge limpio (cada lado agrego keys distintas: `tamanos`/`extras` vs `popular`/`nuevo`).
  - Docs (`ContextoGeneral.md`, `AntierroresBack.md`, este archivo): reconciliados a mano, ver notas abajo.
- **Importante — `Pedido::$fillable` ya NO esta vacio**: la nota "NO TOCAR" de la sesion "Clientes" (abajo) que decia `Pedido::$fillable = []` intencional quedo OBSOLETA tras el merge — el modelo mergeado tiene fillable completo (viene del modulo Pedidos). Si el modulo Clientes necesitaba forceFill() por eso, revisar si sigue haciendo falta.
- **`Sucursal` ya usa `PerteneceAInstancia`** desde la sesion de Pedidos (2026-07-16) — la entrada EB-03 original del compañero (ahora renumerada EB-06 en `AntierroresBack.md`) decia que no lo usaba; eso era cierto en su rama antes del merge, ya no aplica.
- **Gap de producto abierto (no se resuelve en este merge)**: el compañero reestructuro el Home del cliente como "vitrina" (Destacados/Populares/Nuevo/Ofertas/Cupones, catalogo completo movido a la tab Carrito) mientras esta sesion construia el carrito real sobre la estructura anterior. Se prioriza el carrito real y se descarta la reestructuracion visual del Home/Pedir del compañero (decision del usuario) — el admin YA puede curar Destacados/Populares/Nuevo/oferta-hero (`home-config`, `productos.popular`/`nuevo`, ver sesiones del compañero abajo) pero el Home del cliente todavia no consume esas secciones curadas. Portar esa curacion a la UI real del cliente queda como pendiente explicito.
- Verificado tras el merge: `composer install`/dependencias sin cambios nuevos en este repo, `php artisan serve` sigue arriba sin errores, rutas nuevas de ambos lados responden (smoke test pendiente de detalle, ver mensaje de cierre de sesion).
- NO TOCAR / nota: no rehacer a mano el dump `rooster_pizza_bd.sql` combinando texto de ambas ramas — se regenera completo con `pg_dump` DESPUES de aplicar la migracion del compañero (`migracion_2026-07-18_home_secciones.sql`) sobre la BD local, nunca editando el SQL generado directamente.

## Sesion 2026-07-19 — Batch de UX en Pedidos: revertir estado, buscar propio por codigo, foto en extras, tamano con descripcion
- Contexto: tras probar la app, el usuario pidio un lote de fixes/features sobre el modulo Pedidos ya funcional: quitar el modal de confirmacion/motivo al cambiar estado (queria "ligereza"), poder revertir un pedido a un estado anterior desde el historial, extras con foto (estilo "upsell"), tamanos con detalle de cantidad (ej. "Grande - 12 slides"), y un endpoint para que el cliente vea el detalle COMPLETO de su propio pedido buscandolo por codigo (el publico existente es deliberadamente minimo, eso no se toco).
- **Esquema nuevo** (aprobado): `extras.imagen_url` (nullable) + `producto_tamanos.descripcion` (nullable, texto corto). Sin tablas nuevas. Migracion: `bd-doc/migracion_2026-07-19_extras_foto_y_tamano_descripcion.sql`.
- **Foto en Extras**: mismo patron que Producto — `imagen_url` como parametro SEPARADO del Service (no vive en el DTO), `CloudinaryService::subirImagenExtra()` (folder `rooster-pizza/extras`), `null` en `actualizar()` conserva la imagen existente (no se pisa).
- **Tamano con descripcion**: se propaga por toda la cadena existente de `tamanos` (DTOs, Requests, `sincronizarTamanos()`, Resource) igual que `nombre`/`precio`.
- **"Hora estimada" ELIMINADA por completo** (no solo ocultada): se borro `PedidoService::estimarHoraLista()` y toda referencia en los 4 endpoints de cliente + 4 de admin + los 3 Resources de Pedido. El usuario considero el calculo poco confiable ("va a ser muy relativo si el lugar esta lleno").
- **Nuevo endpoint autenticado `GET /pedidos/mios/buscar?codigo=`**: el cliente ve el detalle COMPLETO (notas, items, sucursal, total) de un pedido PROPIO buscandolo por codigo — distinto del publico `GET /pedidos/buscar` (sin auth, minimo, sin PII) que sigue igual sin tocarse, pensado para gente sin sesion. **Ruta registrada ANTES de `/pedidos/mios/{id}`** en `routes/api.php` (si se registrara despues, Laravel interpretaria "buscar" como el `{id}`) — verificado con `route:list`. Responde 422 (no 404) si el codigo no existe/no es del usuario, siguiendo el mismo patron que ya usa `buscarDeCliente()` en el resto del modulo (ValidationException, consistente con el resto del codebase, no es un descuido).
- **Revertir estado**: `PedidoService::revertirEstado()` es una accion ADMINISTRATIVA aparte de `cambiarEstado()` (que sigue con su matriz de transicion normal intacta) — permite volver a CUALQUIER estado que aparezca en el historial real del pedido (no un estado inventado), agrega una fila NUEVA al historial (nunca borra/edita las existentes, queda auditable con comentario "Revertido manualmente por el admin"), y si se revierte desde `entregado` con `pagado=true`, resetea el pago automaticamente (la entrega se esta deshaciendo, el pago post-entrega ya no aplica). Ruta: `POST /admin/pedidos/{id}/revertir`.
- Verificado end-to-end por curl (propio, no solo el reporte del subagente): extra con imagen_url conservada tras editar sin mandar imagen nueva; tamano con `descripcion` expuesto en `GET /productos`; `hora_estimada` confirmado ausente en los 8 endpoints de pedidos; ruta `mios/buscar` antes que `mios/{id}` en `route:list`; ciclo completo pendiente→...→entregado→pagar→revertir a "listo" → `pagado` vuelve a `false`, historial con la fila nueva; revertir a un estado que nunca ocurrio → 422; revertir al mismo estado actual → 422. Datos de prueba limpiados al cierre.
- Pendiente:
  - Cloudinary no tiene credenciales en el `.env` de este entorno de desarrollo (igual que ya pasaba con Producto) — la subida real de imagen para extras no se pudo probar end-to-end con un archivo real, solo la logica de conservar/no-pisar `imagen_url`.
- NO TOCAR / nota: el endpoint publico `GET /pedidos/buscar` (sin auth, `PedidoPublicoResource` minimo) sigue intacto — el nuevo `GET /pedidos/mios/buscar` es un endpoint APARTE, autenticado, no un reemplazo. `cambiarEstado()` (maquina de transicion normal) tampoco se toco — `revertirEstado()` es logica separada.

## Sesion 2026-07-19 (cont.) — 4 bugs reales encontrados al probar el batch anterior
- **Bug real: `PedidoRepository::listarAdmin()` nunca cargaba `detalles`** — el listado admin (`GET /admin/pedidos`) no hacia eager-load de `detalles.producto`/`detalles.extras.extra` (solo `cliente`/`sucursal`), asi que `PedidoAdminResource` omitia la key `items` por completo (comportamiento correcto de `whenLoaded()`, pero el dato nunca se cargaba). El frontend hace `{{ p.items.length }}` sin guardas, asumiendo que `items` siempre es un array — con la key ausente, el binding rompia el render de la fila completa (tabla casi vacia, KPIs en 0 con filas visibles). Este bug es PRE-EXISTENTE (ya estaba asi antes de esta sesion), simplemente nadie lo habia notado. Fix: agregar `'detalles.producto', 'detalles.extras.extra'` al `->with()` de `listarAdmin()`. Verificado con captura de pantalla real (Puppeteer + Chrome del sistema): tabla renderiza completa, KPIs correctos.
- **Esquema nuevo (aprobado): `pedidos.nombre_cliente`** (nullable, varchar). El checkout siempre preguntó "a nombre de quién" pero ese texto NUNCA se mandaba al backend ni existia donde guardarlo — al buscar el pedido despues, se mostraba el nombre de la CUENTA logueada, no el que se escribio (bug real: alguien puede pedir con la cuenta familiar a nombre de otra persona). Migracion: `bd-doc/migracion_2026-07-19b_pedidos_nombre_cliente.sql`. Propagado por `CrearPedidoDTO`/`StorePedidoRequest` (ahora `required`) → `PedidoService::crear()` → `PedidoResource`/`PedidoAdminResource` (NO en `PedidoPublicoResource`, se mantiene sin PII). El admin (tabla y modal de detalle) ahora usa `nombre_cliente` con fallback a `cliente.nombre` para pedidos viejos que no lo tienen.
- Verificado con curl: pedido creado con `nombre_cliente` distinto del nombre de la cuenta → `GET /pedidos/mios/buscar` devuelve el nombre escrito, no el de la cuenta. Datos de prueba limpiados al cierre.
- NO TOCAR / nota: `PedidoPublicoResource` (busqueda publica sin auth) sigue sin exponer `nombre_cliente` a proposito — es informacion personal, el diseño de privacidad de ese endpoint no cambia.

## Sesion 2026-07-17 (cont.) — Home rediseñado (vitrina) + modulo admin "Inicio"
- Hecho:
  - **Nuevo endpoint `home-config`** (curacion del Home, no contenido duplicado): `Configuracion` (modelo nuevo sobre la tabla ya existente `configuraciones`, clave-valor, `PerteneceAInstancia`), `ConfiguracionRepository` (`obtenerPorClave`/`guardar` via `updateOrCreate`), `ConfiguracionService` (`obtenerHomeConfig`/`actualizarHomeConfig`, clave fija `home_oferta_hero_id`), `ConfiguracionController` (`show` publico, `update` admin), `UpdateHomeConfigRequest` (`oferta_hero_id` nullable, `exists:ofertas,id`).
  - Rutas: `GET /api/home-config` (publico) y `PUT /api/admin/home-config` (`auth:sanctum` + `role:super_admin,admin_sede`), agregadas junto al resto del catalogo en `routes/api.php`.
  - **Decision de diseño**: el Home NO tiene tabla de contenido propia. Destacados = `productos.destacado` (campo que ya existia); Ofertas/Cupones del Home = los mismos endpoints publicos `GET /ofertas` y `GET /cupones` (ya filtran vigencia por fecha en el repository, sin curacion manual). Lo unico que persiste es cual oferta se muestra primero cuando hay varias vigentes ("hero"), via `home-config`.
  - Verificado end-to-end por curl (login admin): crear oferta demo vigente → marcarla como `oferta_hero_id` → `GET /home-config` y `GET /ofertas` la reflejan → 422 en espanol si el id no existe (`exists:ofertas,id`) → limpiado el dato de prueba al terminar (oferta demo eliminada, `oferta_hero_id` vuelto a null).
- Pendiente:
  - Nada pendiente conocido de este endpoint. Si a futuro se necesita destacar tambien un cupon "hero", replicar el mismo patron (nueva clave `home_cupon_hero_id`) en vez de nueva tabla.
- NO TOCAR / nota: `configuraciones.clave` tiene un UNIQUE constraint a nivel de columna (no compuesto con `instancia_id`) — funciona para el uso actual (una instancia activa en dev) pero si el proyecto pasa a multi-instancia real en produccion, revisar si hace falta un unique compuesto `(clave, instancia_id)` antes de que dos instancias choquen por la misma clave.

## Sesion 2026-07-17 (cont. 2) — Secciones multiples del Home: Populares y Lo nuevo
- Hecho:
  - **2 columnas nuevas en `productos`**: `popular boolean not null default false`, `nuevo boolean not null default false` (mismo patron que `destacado`, que ya existia). Aplicadas a mano via `php artisan tinker` con `DB::statement('ALTER TABLE ...')` (NO `php artisan migrate`, sigue la regla del proyecto). SQL de referencia guardado en `bd-doc/migracion_2026-07-18_home_secciones.sql`.
  - Actualizado en cadena, mismo patron que `destacado` en cada capa: `Producto` (fillable + casts), `StoreProductoRequest`/`UpdateProductoRequest` (normalizacion boolean + reglas), `CrearProductoDTO`/`ActualizarProductoDTO` (props + fromArray + toArray), `ProductoResource` (expone `popular`/`nuevo`). `ProductoService`/`ProductoRepository` sin cambios (pasan el array completo, ya cubierto por `$fillable`).
  - Motivacion: el cliente pidio que el Home tenga varias secciones tipo apps grandes (McDonald's/KFC/BK) — "Destacados", "Populares", "Lo nuevo" — no solo un flag. Se decidio agregar boolean flags independientes (un producto puede estar en varias secciones a la vez) en vez de un enum de una sola seccion, para no forzar mutua exclusion que no pidieron.
  - Verificado por curl: `GET /productos` expone `popular`/`nuevo` en cada item; `POST /admin/productos/{id}` (con `_method=PUT`) acepta y persiste `popular=1`/`nuevo=1`; revertido a `false` al terminar la prueba para no dejar datos de prueba activos en el producto real "Lomo".
- Pendiente:
  - Nada pendiente conocido. Si se pide otra seccion mas (ej. "Oferta del dia" para productos, no solo ofertas), replicar el mismo patron: nueva columna boolean + los mismos 4 archivos tocados aca.
- NO TOCAR / nota: `destacado` es el campo mas viejo del trio y sigue siendo el unico visible tambien en el checkbox de Menu (`admin/menu/menu.page.ts`) — `popular`/`nuevo` solo se manejan desde el modulo admin "Inicio" (`admin/inicio/inicio.page.ts`, funcion `toggleSeccion`), no se agrego UI para ellos en Menu a proposito (evitar duplicar controles).

## Sesion 2026-07-18 — Modulo admin: Clientes (analitica de compra, solo lectura)
- Hecho:
  - **Nuevo modelo `Pedido`** (`app/Models/Pedido.php`) — **NO existia antes** (el modulo Pedidos del admin seguia sin API real). Mapea `pedidos`, usa trait `PerteneceAInstancia`, `protected $fillable = []` **a proposito** (solo lectura para este modulo). Relaciones: `cliente()` → `belongsTo(User::class, 'cliente_id')`, `sucursal()` → `belongsTo(Sucursal::class)`, `cupon()` → `belongsTo(Cupon::class)`.
  - **Nuevo modulo API-only de Clientes** (100% solo lectura, sin creacion/edicion): `ClienteRepository` (`listarConEstadisticas`, `listarPedidos`), `ClienteService` (mismos metodos, sin DTOs), `ClienteController` (`index`, `pedidos`), `ClienteResumenResource`/`PedidoResumenResource`.
  - **Endpoints**:
    - `GET /api/admin/clientes` — lista de clientes (rol `cliente`) con `total_gastado`, `cantidad_pedidos`, `ticket_promedio`, `ultimo_pedido_en`, `puntos_balance`, `activo`. Excluye pedidos `cancelado` de los calculos. Agregacion hecha en SQL (subquery + leftJoinSub con `withSelect`/`whereNotNull`, filtro `estado != 'cancelado'`), sin N+1.
    - `GET /api/admin/clientes/{id}/pedidos` — historial de pedidos de un cliente puntual. Validacion de instancia ANTES de devolver datos: `$cliente->instancia_id !== Auth::user()->instancia_id → 404` (evita IDOR cross-tenant).
  - Ambas rutas bajo `auth:sanctum` + `role:super_admin,admin_sede` en el grupo admin ya existente de `routes/api.php`.
  - Patron: Controller-Service-Repository (mismo que Ofertas/Insumos). Sin DTOs porque no hay creacion/edicion.
  - **Seeder de prueba** `ClientesDemoSeeder` (NO registrado en `DatabaseSeeder.php` — opt-in, se corre manual con `php artisan db:seed --class=ClientesDemoSeeder`): crea sucursal "La Fortuna (Centro)" (antes no existia ninguna en BD local, y `pedidos.sucursal_id` es NOT NULL), 15 clientes demo (`cliente-demo-1@rooster-test.com`...), ~100 pedidos en los ultimos 6 meses con estados/montos realistas. Ya ejecutado contra BD local, datos reales verificados.
  - Verificado: `ClienteService::listarConEstadisticas()` devuelve el ranking correcto, `listarPedidos()` respeta la validacion de instancia (404 para clientes de otra instancia).
- Pendiente:
  - Nada pendiente conocido del backend. El frontend del modulo Clientes ya esta conectado y funcional (ver `HiloActualFront.md`).
- NO TOCAR / nota: en esta sesion `Pedido::$fillable = []` era intencional (solo lectura). **Tras el merge con el modulo Pedidos (ver sesion 2026-07-18 arriba), `Pedido` ya tiene fillable completo** — esta nota queda como registro historico de por que el seeder necesito el workaround, no como estado actual del modelo. Ver `AntierroresBack.md` EB-06 (gotchas de Eloquent del seeder, renumerado tras el merge).
