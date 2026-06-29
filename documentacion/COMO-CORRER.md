# Cómo correr el software — Rooster Pizza & Grill

Pasos puntuales para levantar **base de datos + backend + frontend** y probar el
**Módulo 1: Autenticación (registro + login)**, que ya está funcional.

> Si te acabás de conectar al proyecto: leé `ContextoGeneral.md`, luego este archivo
> y los `HiloActual*` de cada lado. Con eso sabés qué hay hecho y cómo exponerlo.

---

## 0. Requisitos
- **PHP 8.2+** y **Composer** (XAMPP sirve).
- **PostgreSQL 18** + **pgAdmin 4**.
- **Node.js + npm**. Ionic CLI: `npm i -g @ionic/cli` (o usar `npx ionic`).
- Dos repos independientes (se comunican solo por API REST):
  - `backend-integradorIII` (Laravel) — acá vive también `documentacion/`.
  - `frotend-integradorIII` (Ionic + Angular).

---

## 1. Base de datos (una sola vez)
La BD se mantiene **por SQL**, no por migraciones de Laravel.

1. Crear la base `rooster_pizza` en PostgreSQL (pgAdmin → Databases → Create, o `createdb`).
2. Cargar el esquema: ejecutar `documentacion/back-document/bd-doc/rooster_pizza_bd.sql`.
3. (Solo si cargaste una versión vieja del SQL) aplicar
   `bd-doc/migracion_2026-06-28_sanctum_personal_access_tokens.sql` (sección forward).
4. **NO** correr `php artisan migrate` — chocaría con las 21 tablas ya creadas.

Verificación: en pgAdmin las tablas están en **Schemas → public → Tables** (deben ser 21).
Por psql: `\dt`.

---

## 2. Backend (Laravel) → `http://127.0.0.1:8000`
```bash
cd backend-integradorIII
composer install
cp .env.example .env          # luego editar .env (ver abajo)
php artisan key:generate
php artisan config:clear
php artisan db:seed --class=RolesSeeder   # crea roles: super_admin, admin_sede, cliente
php artisan serve
```

En `.env` setear (lo demás ya viene bien en `.env.example`):
```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=rooster_pizza
DB_USERNAME=postgres
DB_PASSWORD=<tu_clave_de_postgres>
SESSION_DRIVER=file        # NO 'database' (no hay tabla sessions)
```

Notas:
- El **registro necesita el rol `cliente`** sembrado (paso `db:seed`), si no falla.
- La API vive bajo **`/api`**. La raíz `/` es solo la bienvenida de Laravel (no se usa).
- `.env` está en `.gitignore`: la clave de BD nunca se commitea.

---

## 3. Frontend (Ionic + Angular) → `http://localhost:8100`
```bash
cd frotend-integradorIII
npm install
ionic serve        # o: npx ionic serve
```
Verificar `src/environments/environment.ts` → `apiBaseUrl: 'http://127.0.0.1:8000/api'`.
Si el backend corre en otro host/puerto, ajustar ahí.

---

## 4. Probar el Módulo 1 — Autenticación (funcional)
Con backend + frontend arriba:
1. Abrir `http://localhost:8100` → carga el **login**.
2. **Crear cuenta**: nombre, email y password **12+ caracteres con mayúscula,
   minúscula, número y símbolo** (ej. `Rooster#2026!`). Al enviar:
   - se crea el cliente en la tabla `users` (rol `cliente`, password hasheada),
   - se emite un token Sanctum y entra a `/tabs/tab1` (placeholder temporal).
3. En `tab1` hay un botón rojo **"Cerrar sesión (temporal)"** (hasta tener el Home real).
4. Volver al login e iniciar sesión con esas credenciales.

Endpoints del módulo:
| Método | Ruta | Auth | Qué hace |
|---|---|---|---|
| POST | `/api/register` | no | Registra cliente, devuelve usuario + token |
| POST | `/api/login` | no | Login, devuelve usuario + token |
| POST | `/api/logout` | sanctum | Invalida el token actual |
| GET | `/api/me` | sanctum | Usuario autenticado |

---

## 5. Problemas comunes
- **`Undefined table: sessions` en `/`** → `SESSION_DRIVER=file` + `php artisan config:clear`.
- **`fe_sendauth: no password supplied`** → falta `DB_PASSWORD` en `.env` (o el user postgres no tiene clave; fijala en pgAdmin con `ALTER USER postgres PASSWORD '...';`).
- **El front no conecta** → el backend debe estar en `127.0.0.1:8000`; revisar `environment.ts` y que `php artisan serve` esté corriendo.
- **Login/Register se ven cortados o no centran** → es responsive por alto (zoom/escala alta reduce el viewport). Ver `front-document/AntierroresFront.md` EF-01.

---

## 6. Siguiente paso (para exponer y seguir)
1. Levantar BD + backend + frontend (secciones 1-3) y probar auth (sección 4).
2. Próximos pendientes (detalle en `back-document/HiloActualBack.md` y
   `front-document/HiloActualFront.md`):
   - Reemplazar el placeholder `/tabs/tab1` por el **Home real** del cliente.
   - **"Continuar con Google"** (fast-follow; mapeo aprobado con columnas `google_id` + `auth_provider`).
   - "Olvidé mi contraseña" y localizar a español los mensajes de complejidad de password.

*Última actualización: 2026-06-29.*
