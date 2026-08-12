# Cómo correr el software — Rooster Pizza & Grill

Pasos puntuales para levantar **base de datos + backend + frontend** y probar los
módulos funcionales: **Módulo 1: Autenticación** (registro + login) y
**Módulo 2: Catálogo de productos** (Menú admin + Home cliente, con fotos vía Cloudinary).

> Si te acabás de conectar al proyecto: leé `ContextoGeneral.md`, luego este archivo
> y los `HiloActual*` de cada lado. Con eso sabés qué hay hecho y cómo exponerlo.

---

## 0. Requisitos
- **PHP 8.3+** (el proyecto está en **Laravel 13** desde 2026-08-12) y **Composer**.
  El XAMPP del equipo trae PHP 8.2, que **no alcanza** — hay que instalar PHP 8.3+
  aparte, sin tocar el XAMPP. Ver **sección 2.1** para el paso a paso.
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
5. Cargar los productos reales del menú (reemplaza los placeholders de prueba):
   ejecutar `bd-doc/seed_2026-08-09_productos_reales_rooster.sql`. Re-ejecutable
   sin duplicar. Detalle en `HiloActualBack.md`, sesión 2026-08-09.
6. Si tu BD no tiene la tabla `notificaciones` (dump atrasado, ver `HiloActualBack.md`
   2026-08-12): ejecutar `bd-doc/migracion_2026-07-22_notificaciones.sql`. Aditiva,
   `IF NOT EXISTS`, re-ejecutable sin romper nada.

Verificación: en pgAdmin las tablas están en **Schemas → public → Tables** (deben ser 21).
Por psql: `\dt`.

---

## 2.1 Instalar PHP 8.3+ (una sola vez, sin tocar tu XAMPP)

Desde 2026-08-12 el proyecto corre en **Laravel 13**, que exige PHP 8.3-8.5. Si tu
`php -v` da 8.2 o menos (el XAMPP típico), instalá un PHP aparte — no reemplaces el
del XAMPP, así no afectás otros proyectos ni phpMyAdmin:

1. Descargar el zip **Thread Safe x64** de PHP 8.3 (o 8.4) desde
   `https://downloads.php.net/~windows/releases/` (buscar `php-8.3.x-Win32-vs16-x64.zip`).
2. Descomprimir en `C:\php83` (o el nombre que prefieras).
3. Crear `C:\php83\php.ini` con al menos:
   ```ini
   [PHP]
   extension_dir = "ext"
   memory_limit = 512M
   upload_max_filesize = 40M
   post_max_size = 40M
   date.timezone = America/Costa_Rica

   extension=curl
   extension=fileinfo
   extension=gd
   extension=mbstring
   extension=openssl
   extension=pdo_pgsql
   extension=pgsql
   extension=zip
   extension=exif
   ```
4. Usar ese PHP explícitamente para todo lo de este proyecto (no hace falta tocar
   el PATH del sistema): `C:\php83\php.exe artisan ...`, y Composer con
   `C:\php83\php.exe C:\ProgramData\ComposerSetup\bin\composer.phar ...`
   (ajustar la ruta del `composer.phar` si la tuya es distinta — buscarla con
   `where composer` y mirar el `.bat`).

Verificar: `C:\php83\php.exe -v` debe decir 8.3.x, y `C:\php83\php.exe -m` debe
listar `pgsql`, `pdo_pgsql` y `zip` sin warnings.

---

## 2. Backend (Laravel) → `http://127.0.0.1:8000`
```bash
cd backend-integradorIII
C:\php83\php.exe C:\ProgramData\ComposerSetup\bin\composer.phar install
cp .env.example .env          # luego editar .env (ver abajo)
C:\php83\php.exe artisan key:generate
C:\php83\php.exe artisan config:clear
C:\php83\php.exe artisan db:seed --class=RolesSeeder          # crea roles: super_admin, admin_sede, cliente
C:\php83\php.exe artisan db:seed --class=AdminTestUserSeeder  # crea admin@rooster.com de prueba (ver abajo)
C:\php83\php.exe artisan serve
```
También hay que correr `bd-doc/migracion_2026-07-22_notificaciones.sql` contra tu
BD si todavía no tenés la tabla `notificaciones` (aditiva, re-ejecutable — ver
sección 1).

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
`src/environments/environment.ts` usa `apiBaseUrl: '/api'` (relativo). El dev-server
reenvía `/api` → `http://127.0.0.1:8000` vía `proxy.conf.json` (declarado en
`angular.json` → `serve.options.proxyConfig`). Funciona igual en local. Si el backend
corre en otro host/puerto, cambiá el `target` de `proxy.conf.json` y reiniciá `ionic serve`.

---

## 3.1 Acceso por túnel público (celular / demo remota)
Para abrir la app desde un celular u otra red (ej. con **Dev Tunnels de VS Code**):
1. En VS Code, pestaña **Ports** → reenviar el puerto **8100** → visibilidad **Public**.
2. Abrir en el celular la URL del túnel (`https://<algo>-8100.<region>.devtunnels.ms`).
3. Iniciar sesión de nuevo (un token viejo puede apuntar a otra base).

Por qué funciona sin nada más: como `apiBaseUrl` es `/api` (relativo), las llamadas van al
**mismo origen del túnel (8100)** y el dev-server las reenvía al backend local (8000). **No
hace falta exponer el 8000 ni tocar CORS.** Requisitos: la PC que corre `ionic serve` +
`php artisan serve` debe quedar encendida y el túnel del 8100 en **Public**. Tras cambiar el
proxy hay que reiniciar `ionic serve`.

---

## 3.2 Inicio de sesión con Google (una sola vez por máquina)

Las credenciales de Google son **compartidas por el equipo**: hay UN solo cliente
OAuth para todos, no hace falta que cada quien cree el suyo.

1. Pedile a Steven el `GOOGLE_CLIENT_ID` y el `GOOGLE_CLIENT_SECRET` (van por
   canal privado — WhatsApp o Drive, **nunca** al repo: es público).
2. Pegalos en tu `.env` junto con las otras dos claves que ya están en
   `.env.example`:
   ```
   GOOGLE_CLIENT_ID=...apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=GOCSPX-...
   GOOGLE_ALLOWED_ORIGINS=http://localhost:4200,http://localhost:8100
   GOOGLE_FRONTEND_URL=http://localhost:4200
   ```
3. `php artisan config:clear` y reiniciá `php artisan serve`.

**Levantá el front en 4200 (`npm start`) o en 8100 (`ionic serve`)** — esos dos
son los únicos puertos registrados en Google Cloud Console. El front le manda su
origen al backend y este arma el `redirect_uri` con ese mismo puerto.

> Si `ionic serve` te dice que el 8100 está ocupado y se pasa al 8101, **no
> sigas**: pará el proceso que ocupa el 8100 o levantá en 4200. Con un puerto no
> registrado el login no va a funcionar (el backend te lo va a decir con un
> mensaje claro, no te va a dejar a mitad de camino). Si usás **otro** puerto o un túnel, hay que agregarlo en DOS lugares: en
tu `GOOGLE_ALLOWED_ORIGINS` y en Google Cloud Console → Clientes → `Rooster web`
→ URI de redireccionamiento autorizados (con `/api/auth/google/callback`).

### Si te sale un error

| Error | Qué significa |
|---|---|
| **Elegís la cuenta y al volver sale `ERR_CONNECTION_REFUSED`** | El front está en un puerto distinto al que el backend usó para armar la vuelta. Es el error más común del equipo. Fijate en qué puerto levantaste el front (la barra del navegador) y verificá que ese origen esté en tu `GOOGLE_ALLOWED_ORIGINS` **y** en Google Cloud Console. Con el código al día, este caso ya no llega a Google: el backend corta antes con un mensaje que te dice exactamente qué agregar. |
| `El origen "http://localhost:XXXX" no esta permitido` | Justo lo anterior, ya explicado por el backend. Agregá ese origen en los DOS lugares que dice el mensaje. |
| `redirect_uri_mismatch` | El origen desde el que entrás no está cargado en Google Cloud Console. |
| `Acceso bloqueado` | Tu cuenta de Google no está en la lista de usuarios de prueba (o la app no está publicada). |
| `cURL error 60: SSL certificate ...` | Tu PHP no tiene los certificados raíz. Bajá https://curl.se/ca/cacert.pem y en tu `php.ini` poné `curl.cainfo` y `openssl.cafile` apuntando a ese archivo. Reiniciá el server. **No** desactives la verificación TLS: por ese canal viaja el `client_secret`. |

## 3.3 Correr los tests del backend

```
C:\php83\php.exe artisan test
```

**Importante: NO uses `php artisan test` a secas.** El `php` del PATH suele ser el
del XAMPP (8.2) y el proyecto exige 8.3+ (Laravel 13), así que ni arranca. Usá
el PHP standalone de la sección 2.1. Si no te acordás dónde lo instalaste, el que
sirve el 8000 se identifica con:

```powershell
Get-Process -Id (Get-NetTCPConnection -LocalPort 8000 -State Listen).OwningProcess | Select Path
```

Estado esperado hoy (2026-08-12, tras el upgrade a Laravel 13 y aplicar la
migración de `notificaciones` de la sección 1): **22/22 pasan**.

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

*Última actualización: 2026-07-28.*
