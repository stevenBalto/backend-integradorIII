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
