# Cómo correr el software — Rooster Pizza & Grill

Pasos puntuales para levantar **base de datos + backend + frontend** y probar los
módulos funcionales: **Módulo 1: Autenticación** (registro + login) y
**Módulo 2: Catálogo de productos** (Menú admin + Home cliente, con fotos vía Cloudinary).

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
php artisan db:seed --class=RolesSeeder          # crea roles: super_admin, admin_sede, cliente
php artisan db:seed --class=AdminTestUserSeeder  # crea admin@rooster.com de prueba (ver abajo)
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

CLOUDINARY_CLOUD_NAME=<pedir al equipo>
CLOUDINARY_API_KEY=<pedir al equipo>
CLOUDINARY_API_SECRET=<pedir al equipo>
```

Notas:
- El **registro necesita el rol `cliente`** sembrado (paso `db:seed`), si no falla.
- La API vive bajo **`/api`**. La raíz `/` es solo la bienvenida de Laravel (no se usa).
- `.env` está en `.gitignore`: la clave de BD y las credenciales de Cloudinary nunca se commitean.
- Si la BD es una instancia nueva/distinta a la que ya tenías corriendo, verificar además que `personal_access_tokens` tenga columnas `tokenable_id`/`tokenable_type` (no `user_id`) — ver `back-document/AntierroresBack.md` EB-01/EB-02. Sin eso, el login falla con un 500 poco claro.
- Sin `CLOUDINARY_*` configurado, el CRUD de productos sigue funcionando (nombre/precio/categoría/disponible/destacado), pero subir una foto tira error 500 al intentar autenticar contra Cloudinary.

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
   - se emite un token Sanctum y entra a `/tabs/home` (Home real del cliente).
3. En **Mi cuenta** (tab inferior) hay una fila roja **"Cerrar sesión"** conectada.
4. Volver al login e iniciar sesión con esas credenciales.

Endpoints del módulo:
| Método | Ruta | Auth | Qué hace |
|---|---|---|---|
| POST | `/api/register` | no | Registra cliente, devuelve usuario + token |
| POST | `/api/login` | no | Login, devuelve usuario + token |
| POST | `/api/logout` | sanctum | Invalida el token actual |
| GET | `/api/me` | sanctum | Usuario autenticado |

---

## 5. Probar el Módulo 2 — Catálogo de productos (funcional)
Con backend + frontend arriba, roles y `AdminTestUserSeeder` sembrados:
1. Iniciar sesión (login normal, YA NO hay atajo `admin`/`123`) con:
   - **email**: `admin@rooster.com`
   - **password**: `admin123456`
   - El backend devuelve `rol: super_admin` y el frontend redirige solo a `/admin`.
2. En **Menú / Catálogo**: crear un producto (con o sin foto), editarlo, tocar
   una fila para ver el modal de detalle, eliminarlo (soft delete — la fila
   se conserva en la BD con `deleted_at` poblado, pero desaparece de la app).
3. Si la tabla `categorias` está vacía, no va a haber opciones en el selector
   del formulario — sembrar categorías a mano (`pizza`, `grill`, `pastas`,
   `bebidas`) antes de crear productos.
4. Salir al login y entrar como cliente (registro normal) → **Home** debe
   mostrar los productos con `disponible=true`, con foto real si se cargó.

Endpoints del módulo:
| Método | Ruta | Auth | Qué hace |
|---|---|---|---|
| GET | `/api/productos` | no | Catálogo público, solo disponibles |
| GET | `/api/categorias` | no | Categorías activas |
| GET | `/api/admin/productos` | sanctum + rol | Listado completo (admin) |
| POST | `/api/admin/productos` | sanctum + rol | Crear (multipart si lleva foto) |
| PUT/PATCH | `/api/admin/productos/{id}` | sanctum + rol | Editar (vía POST + `_method=PUT` si lleva foto) |
| DELETE | `/api/admin/productos/{id}` | sanctum + rol | Eliminar (soft delete) |

## 7. Recorrer el resto de la base visual (aún hardcodeado)
- **App cliente**: Pedir, Ofertas, Mi cuenta siguen sin conectar a la API.
- **Panel admin**: Dashboard, Pedidos, Ofertas y cupones, Usuarios y roles,
  Analíticas, Notificaciones, Reseñas, Configuración — 8 de los 9 módulos
  siguen siendo maquetado estático (Menú ya es real, ver sección 5).
  "Salir al app" vuelve al login.

---

## 8. Problemas comunes
- **`Undefined table: sessions` en `/`** → `SESSION_DRIVER=file` + `php artisan config:clear`.
- **`fe_sendauth: no password supplied`** → falta `DB_PASSWORD` en `.env` (o el user postgres no tiene clave; fijala en pgAdmin con `ALTER USER postgres PASSWORD '...';`).
- **El front no conecta** → el backend debe estar en `127.0.0.1:8000`; revisar `environment.ts` y que `php artisan serve` esté corriendo.
- **Login/Register se ven cortados o no centran** → es responsive por alto (zoom/escala alta reduce el viewport). Ver `front-document/AntierroresFront.md` EF-01.
- **`ionic serve` no encuentra módulos recién creados (`TS2307`)** → matar el proceso y levantarlo en frío. Ver `front-document/AntierroresFront.md` EF-02.
- **Login funciona pero cualquier acción del admin (crear/editar/eliminar producto) da 401 o "me expulsa" a `/login`** → probablemente la BD es una instancia nueva sin el fix de Sanctum aplicado, o sin roles sembrados. Ver `back-document/AntierroresBack.md` EB-02 (checklist de verificación).
- **Subir foto de producto da error 500** → faltan `CLOUDINARY_*` en `.env` (ver sección 2) o las credenciales son incorrectas.

---

## 9. Siguiente paso (para exponer y seguir)
1. Levantar BD + backend + frontend (secciones 1-3) y probar auth (sección 4).
2. Probar el catálogo de productos (sección 5) y recorrer el resto de la base visual (secciones 6-7).
3. Próximos pendientes (detalle en `back-document/HiloActualBack.md` y
   `front-document/HiloActualFront.md`):
   - Conectar el Carrito/Pedir real (el botón "Añadir al carrito" del Home ya está maquetado, sin lógica).
   - Guard de rol real en Angular para `/admin`.
   - Conectar el resto de módulos del admin (pedidos, ofertas, usuarios, etc.) vía `api-integration-helper`.
   - **"Continuar con Google"** (fast-follow; mapeo aprobado con columnas `google_id` + `auth_provider`).
   - "Olvidé mi contraseña" y localizar a español los mensajes de complejidad de password.

*Última actualización: 2026-07-10.*
