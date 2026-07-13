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
