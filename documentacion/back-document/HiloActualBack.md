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
