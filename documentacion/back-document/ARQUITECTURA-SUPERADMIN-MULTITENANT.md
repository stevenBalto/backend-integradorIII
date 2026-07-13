# Arquitectura — Superadmin, Usuarios/Roles y Multi-Tenant (Instancias)

Diseño técnico para incorporar al sistema **Rooster Pizza & Grill** un panel de
Superadministración aislado, gestión de usuarios con políticas de contraseña,
permisos configurables y **multi-tenant con aislamiento por `instancia_id`**.

> **Principio rector:** NO reinventar el sistema. Se conserva el patrón actual
> (Controller → Service → Repository + DTOs + API Resources), PostgreSQL con
> `bigserial`/snake_case/FK `tabla_id`, Sanctum con tokens polimórficos, y el
> middleware de roles existente. Todo lo nuevo se **suma** siguiendo esas reglas.

> **Estado:** PROPUESTA de diseño. No se han aplicado migraciones. Por la regla
> del esquema (ningún cambio de tablas sin aprobación explícita), esto se
> implementa por fases y con visto bueno. Ver §13 (plan por fases).

*Fecha: 2026-07-12.*

---

## 0. Decisiones de arquitectura (resumen ejecutivo)

| # | Decisión | Elección | Motivo |
|---|---|---|---|
| D1 | Identidad del Superadmin | **Tabla `superadministradores` separada** + guard Sanctum propio | Anti-escalación de privilegios; el superadmin es global (sin `instancia_id`); aprovecha los tokens polimórficos ya existentes. |
| D2 | Concepto multi-tenant | **Nueva tabla `instancias`** (tenant de nivel superior) | `sucursales` pasa a pertenecer a una instancia. Jerarquía limpia: Instancia → Sucursales → datos. |
| D3 | Aislamiento de datos | **`instancia_id` en las tablas raíz** + Global Scope de Eloquent | Un solo `WHERE` automático e imposible de olvidar. BD compartida, aislamiento lógico. |
| D4 | Modelo de permisos | **Rol (plantilla) + permisos individuales por usuario** | Cumple "permisos completamente configurables" y "no depender solo del nombre del rol". |
| D5 | Credenciales temporales | **Mostrar una sola vez** (Opción B); correo como enganche futuro | Hoy no hay SMTP; se agrega sin rediseñar. |
| D6 | Hashing | **Bcrypt** (ya activo en Laravel), migrable a Argon2id | `password => 'hashed'` ya está en el modelo `User`. |

> D1 es la única que quedó por confirmar del todo. El resto de este documento la
> asume; si se decide "todo en `users`", los cambios son locales (ver §11).

---

## 1. Arquitectura general

### 1.1 Dos aplicaciones, una base de datos

```
                        ┌───────────────────────────────────────┐
                        │           PostgreSQL (única)          │
                        │  globales  +  datos por instancia_id  │
                        └───────────────────────────────────────┘
                                   ▲                 ▲
                 guard: superadmin │                 │ guard: sanctum (web/app)
        ┌──────────────────────────┴───┐   ┌─────────┴──────────────────────────┐
        │   PANEL SUPERADMINISTRACIÓN   │   │      PANEL ADMIN + APP CLIENTE      │
        │   /api/superadmin/*           │   │      /api/admin/*  +  /api/*        │
        │   login propio, sesión propia │   │      login actual (extendido)       │
        │   NO conoce instancia_id      │   │      SIEMPRE atado a una instancia  │
        └───────────────────────────────┘   └─────────────────────────────────────┘
```

- **Superadmin**: global. Administra instancias, superadmins y catálogos
  globales (provincias/cantones/distritos). Nunca ve datos operativos de un
  negocio salvo para soporte explícito.
- **Admin / Cliente**: viven *dentro* de una instancia. Todo lo que consultan o
  escriben queda filtrado por su `instancia_id`.

### 1.2 Capas (idénticas a las actuales)

```
Route (api.php)
  → Middleware (auth:guard, role, permiso, instancia, password.valida)
    → Controller (orquesta, no tiene lógica)
      → Request (validación) → DTO (datos tipados)
        → Service (lógica de negocio)
          → Repository (acceso a datos, Eloquent)
            → Model (+ Global Scope de instancia)
      ← API Resource (forma del JSON)
```

Se agregan **tres piezas nuevas transversales**, todas en el estilo existente:
- Guard `superadmin` (multi-auth de Sanctum).
- Middleware `permiso:` y `instancia` y `password.valida`.
- Trait `PerteneceAInstancia` (Global Scope) para los modelos operativos.

---

## 2. Diseño de base de datos

Convenciones respetadas: `bigserial` PK, snake_case plural, FK `tabla_id`,
`created_at`/`updated_at` (y `deleted_at` donde haya soft delete), booleanos con
DEFAULT. Se muestran solo las tablas **nuevas** y las **columnas añadidas** a
tablas existentes.

### 2.1 Tablas NUEVAS

#### Núcleo de identidad y tenant

```sql
-- Superadministradores: identidad GLOBAL, aislada de users.
CREATE TABLE superadministradores (
    id              bigserial PRIMARY KEY,
    nombre          varchar(120) NOT NULL,
    usuario         varchar(60)  NOT NULL UNIQUE,
    email           varchar(150) NOT NULL UNIQUE,
    password        varchar(255) NOT NULL,
    activo          boolean      NOT NULL DEFAULT true,
    ultimo_acceso_en timestamp,
    created_at      timestamp,
    updated_at      timestamp,
    deleted_at      timestamp
);

-- Instancias: el TENANT (empresa/negocio/sucursal lógica).
CREATE TABLE instancias (
    id               bigserial PRIMARY KEY,
    nombre           varchar(120) NOT NULL,
    correo_principal varchar(150),
    estado           varchar(20)  NOT NULL DEFAULT 'activa', -- activa|inactiva|suspendida
    creada_por       bigint,        -- FK → superadministradores(id)
    created_at       timestamp,     -- "fecha de creación" del prompt
    updated_at       timestamp,
    deleted_at       timestamp
);
```

> El "usuario administrador inicial" y la "contraseña temporal" NO se guardan en
> `instancias` (sería desnormalizar): viven en la fila de `users` que se crea
> junto con la instancia, usando las columnas nuevas `password_temporal` y
> `cambio_password_obligatorio` (§2.2).

#### Permisos y módulos (RBAC configurable)

```sql
-- Catálogo de módulos del sistema (Dashboard, Inventario, Ventas, ...).
CREATE TABLE modulos (
    id      bigserial PRIMARY KEY,
    clave   varchar(50)  NOT NULL UNIQUE,   -- 'ventas', 'inventario', 'usuarios'
    nombre  varchar(80)  NOT NULL,
    orden   integer      NOT NULL DEFAULT 0,
    activo  boolean      NOT NULL DEFAULT true
);

-- Catálogo de permisos (una acción sobre un módulo).
CREATE TABLE permisos (
    id         bigserial PRIMARY KEY,
    modulo_id  bigint       NOT NULL,        -- FK → modulos(id)
    clave      varchar(80)  NOT NULL UNIQUE, -- 'ventas.ver', 'ventas.crear'
    nombre     varchar(120) NOT NULL,
    created_at timestamp,
    updated_at timestamp
);

-- Permisos por defecto de cada rol (plantilla).
CREATE TABLE rol_permiso (
    id         bigserial PRIMARY KEY,
    role_id    bigint NOT NULL,              -- FK → roles(id)
    permiso_id bigint NOT NULL,              -- FK → permisos(id)
    UNIQUE (role_id, permiso_id)
);

-- Overrides individuales por usuario (el corazón de "permisos configurables").
-- concedido=true  → agrega un permiso que el rol no da.
-- concedido=false → revoca un permiso que el rol sí daría.
CREATE TABLE usuario_permiso (
    id         bigserial PRIMARY KEY,
    user_id    bigint  NOT NULL,             -- FK → users(id)
    permiso_id bigint  NOT NULL,             -- FK → permisos(id)
    concedido  boolean NOT NULL DEFAULT true,
    UNIQUE (user_id, permiso_id)
);
```

**Permisos efectivos de un usuario** = (permisos de su rol) **∪** (individuales
`concedido=true`) **∖** (individuales `concedido=false`). Se resuelve una vez por
request y se cachea en memoria del request.

#### Seguridad: políticas, historial y auditoría

```sql
-- Política de contraseñas CONFIGURABLE, por instancia (con fallback global).
CREATE TABLE politicas_password (
    id                      bigserial PRIMARY KEY,
    instancia_id            bigint,          -- NULL = política global por defecto
    longitud_minima         smallint NOT NULL DEFAULT 12,
    requiere_mayuscula      boolean  NOT NULL DEFAULT true,
    requiere_minuscula      boolean  NOT NULL DEFAULT true,
    requiere_numero         boolean  NOT NULL DEFAULT true,
    requiere_especial       boolean  NOT NULL DEFAULT true,
    dias_expiracion_default integer  NOT NULL DEFAULT 90,
    historial_no_repetir    smallint NOT NULL DEFAULT 5,   -- nº de claves previas vetadas
    max_intentos_fallidos   smallint NOT NULL DEFAULT 5,
    minutos_bloqueo         smallint NOT NULL DEFAULT 15,
    created_at              timestamp,
    updated_at              timestamp,
    UNIQUE (instancia_id)
);

-- Historial de hashes para impedir reutilización de contraseñas.
CREATE TABLE password_historial (
    id         bigserial PRIMARY KEY,
    user_id    bigint       NOT NULL,        -- FK → users(id)
    password   varchar(255) NOT NULL,        -- hash, nunca texto plano
    created_at timestamp
);

-- Auditoría de acciones sensibles (CRUD, cambios de rol/permiso, etc.).
CREATE TABLE auditoria (
    id            bigserial PRIMARY KEY,
    instancia_id  bigint,                    -- NULL = acción global (superadmin)
    actor_type    varchar(30)  NOT NULL,     -- 'User' | 'SuperAdministrador'
    actor_id      bigint,
    accion        varchar(60)  NOT NULL,     -- 'usuario.crear', 'instancia.suspender'
    entidad       varchar(60),
    entidad_id    bigint,
    datos_antes   jsonb,
    datos_despues jsonb,
    ip            varchar(45),
    user_agent    varchar(255),
    created_at    timestamp
);

-- Bitácora de inicios de sesión (éxitos y fallos), para bloqueo y forense.
CREATE TABLE login_logs (
    id                bigserial PRIMARY KEY,
    instancia_id      bigint,
    actor_type        varchar(30),           -- puede ser NULL si el usuario no existe
    actor_id          bigint,
    usuario_intentado varchar(150) NOT NULL, -- email/usuario tecleado
    exito             boolean      NOT NULL,
    motivo            varchar(80),           -- 'credenciales', 'inactivo', 'bloqueado', 'ok'
    ip                varchar(45),
    user_agent        varchar(255),
    created_at        timestamp
);
```

#### Catálogos globales (config del superadmin)

```sql
CREATE TABLE provincias (
    id         bigserial PRIMARY KEY,
    nombre     varchar(80) NOT NULL UNIQUE,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE cantones (
    id           bigserial PRIMARY KEY,
    provincia_id bigint      NOT NULL,       -- FK → provincias(id)
    nombre       varchar(80) NOT NULL,
    created_at   timestamp,
    updated_at   timestamp,
    UNIQUE (provincia_id, nombre)
);

CREATE TABLE distritos (
    id         bigserial PRIMARY KEY,
    canton_id  bigint      NOT NULL,         -- FK → cantones(id)
    nombre     varchar(80) NOT NULL,
    created_at timestamp,
    updated_at timestamp,
    UNIQUE (canton_id, nombre)
);
```

### 2.2 Columnas AÑADIDAS a tablas existentes

```sql
-- users: username, tenant, política de contraseña y anti-fuerza-bruta.
ALTER TABLE users
    ADD COLUMN usuario                     varchar(60) UNIQUE,      -- "Usuario" del prompt
    ADD COLUMN instancia_id                bigint,                  -- tenant (ver nota)
    ADD COLUMN password_expira_en          date,
    ADD COLUMN dias_expiracion_password    integer,
    ADD COLUMN cambio_password_obligatorio boolean NOT NULL DEFAULT false,
    ADD COLUMN password_temporal           boolean NOT NULL DEFAULT false,
    ADD COLUMN ultimo_acceso_en            timestamp,
    ADD COLUMN intentos_fallidos           smallint NOT NULL DEFAULT 0,
    ADD COLUMN bloqueado_hasta             timestamp;
-- (users ya tiene: nombre, email, password, activo=Estado, role_id,
--  created_at, updated_at, deleted_at.)

ALTER TABLE users
    ADD CONSTRAINT fk_users_instancia
        FOREIGN KEY (instancia_id) REFERENCES instancias(id);

-- sucursales pasa a pertenecer a una instancia.
ALTER TABLE sucursales
    ADD COLUMN instancia_id bigint,
    ADD CONSTRAINT fk_sucursales_instancia
        FOREIGN KEY (instancia_id) REFERENCES instancias(id);
```

> **Nota de migración:** `instancia_id` en `users` se define primero como
> `NULL`, se **backfillea** creando la instancia inicial "Rooster La Fortuna" y
> asignando los usuarios/datos actuales a ella, y luego se pasa a `NOT NULL`.
> Así el sistema de hoy queda como la "instancia 1" sin romper nada.

### 2.3 `instancia_id` en tablas operativas (aislamiento)

Se agrega **`instancia_id bigint NOT NULL`** (FK → `instancias`) a las **tablas
raíz** (aggregate roots):

`categorias`, `productos`, `extras`, `ofertas`, `cupones`, `pedidos`,
`resenas`, `puntos_movimientos`, `metodos_pago`, `faqs`.

Las **tablas hijas** heredan el tenant a través de su padre y **no** llevan
`instancia_id` (evita redundancia y updates inconsistentes):
`detalle_pedido`, `detalle_pedido_extras`, `oferta_producto`, `cupon_uso`,
`pagos`, `pedido_historial_estado`.

`configuraciones` gana `instancia_id` **nullable** (NULL = configuración global;
con valor = configuración propia del negocio).

Índice recomendado en cada tabla con tenant:
```sql
CREATE INDEX idx_<tabla>_instancia ON <tabla> (instancia_id);
```

### 2.4 Diagrama de relaciones (alto nivel)

```
superadministradores ──crea──> instancias ──1:N──> sucursales
                                    │
                                    ├──1:N──> users ──N:M──> permisos (via usuario_permiso)
                                    │            └──N:1──> roles ──N:M──> permisos (via rol_permiso)
                                    │                                        └──N:1──> modulos
                                    ├──1:N──> categorias ──1:N──> productos / extras
                                    ├──1:N──> pedidos ──1:N──> detalle_pedido ──1:N──> detalle_pedido_extras
                                    ├──1:N──> ofertas / cupones / resenas / puntos_movimientos
                                    └──1:N──> politicas_password (1:1 real)

globales (sin instancia_id): provincias ──1:N──> cantones ──1:N──> distritos
transversales: auditoria, login_logs, password_historial
```

---

## 3. Flujo de autenticación

Hay **dos flujos de login independientes** (guards distintos, tokens distintos).

### 3.1 Login de Admin / Cliente — `POST /api/login` (extiende el actual)

```
1. Request valida email/usuario + password (LoginRequest).
2. AuthService.login():
   a. Busca user por email O usuario (UserRepository).
   b. Si bloqueado_hasta > now  → 423 'bloqueado'  (registra login_log fallo).
   c. Hash::check(password). Si falla:
        intentos_fallidos++ ; si supera max_intentos → set bloqueado_hasta.
        registra login_log (motivo='credenciales') ; 422.
   d. Si !activo → 403 'inactivo' (login_log).
   e. Éxito:
        intentos_fallidos=0, bloqueado_hasta=NULL, ultimo_acceso_en=now().
        registra login_log (exito=true).
        emite token Sanctum (guard web) con abilities según permisos efectivos.
3. Responde { user, token, instancia, must_change_password }.
```

`must_change_password = password_temporal OR cambio_password_obligatorio OR
(password_expira_en < hoy)`. El frontend lo usa para redirigir (ver §7).

### 3.2 Login de Superadmin — `POST /api/superadmin/login` (nuevo, aislado)

```
1. SuperadminLoginRequest valida usuario/email + password.
2. SuperadminAuthService.login():
   - Mismas verificaciones (bloqueo, hash, activo) contra superadministradores.
   - ultimo_acceso_en=now(); login_log (actor_type='SuperAdministrador').
   - emite token Sanctum del guard 'superadmin'.
3. Responde { superadmin, token }.  (Nunca incluye instancia_id.)
```

### 3.3 Configuración de guards (Sanctum multi-auth)

`config/auth.php`:
```php
'guards' => [
    'web'        => ['driver' => 'sanctum', 'provider' => 'users'],
    'superadmin' => ['driver' => 'sanctum', 'provider' => 'superadmins'],
],
'providers' => [
    'users'       => ['driver' => 'eloquent', 'model' => App\Models\User::class],
    'superadmins' => ['driver' => 'eloquent', 'model' => App\Models\SuperAdministrador::class],
],
```
`SuperAdministrador` usa `HasApiTokens` igual que `User`. Los tokens conviven en
`personal_access_tokens` gracias a `tokenable_type` (ya polimórfica).

### 3.4 Stack de middlewares

| Middleware | Rol |
|---|---|
| `auth:sanctum` | Autentica admin/cliente (existente). |
| `auth:superadmin` | Autentica superadmin (nuevo guard). |
| `role:...` | Restringe por rol (existente, se conserva). |
| `permiso:ventas.crear` | **Nuevo.** Verifica permiso efectivo. |
| `instancia` | **Nuevo.** Fija el tenant del request desde `user->instancia_id` y activa el Global Scope. |
| `password.valida` | **Nuevo.** Si `must_change_password`, bloquea todo salvo cambiar usuario/contraseña (423). |

---

## 4. Flujo de creación de usuarios (panel Admin)

```
POST /api/admin/usuarios   (auth:sanctum + instancia + permiso:usuarios.crear)
1. StoreUsuarioRequest valida:
   - nombre, usuario (único), email (único), rol_id,
   - dias_expiracion_password, cambio_password_obligatorio,
   - password_temporal (bool), lista de permisos individuales (opcional),
   - password: cumple política de la instancia (longitud/mayús/minús/núm/especial).
2. CrearUsuarioDTO transporta los datos tipados.
3. UsuarioService.crear():
   - instancia_id = instancia actual del admin (NUNCA del request → anti-tenant-hopping).
   - Hash de la contraseña; si password_temporal → cambio_password_obligatorio=true.
   - password_expira_en = hoy + dias_expiracion_password (o política).
   - Persiste user (UserRepository) + password_historial.
   - Sincroniza usuario_permiso con los overrides recibidos.
   - Audita ('usuario.crear').
4. UserResource devuelve el usuario (sin password, con permisos efectivos).
```

Reglas duras:
- El admin **solo** puede asignar roles/permisos **≤** a los suyos (anti-escalación, §9).
- `instancia_id` jamás se acepta desde el cliente; se toma del token.

---

## 5. Flujo de creación de superadministradores (panel Superadmin)

```
POST /api/superadmin/superadmins   (auth:superadmin)
1. Solo un superadmin autenticado puede crear/editar/eliminar/desactivar/reset.
2. StoreSuperadminRequest valida usuario/email únicos + password fuerte (política global).
3. SuperadminService.crear(): hash, activo=true, audita ('superadmin.crear').
4. SuperAdminResource devuelve el registro (sin password).
```
Un `admin` normal **no tiene ninguna ruta** hacia estos endpoints: distinto
prefijo, distinto guard, distinto middleware. Imposible por diseño.

---

## 6. Flujo de creación de instancias (panel Superadmin)

```
POST /api/superadmin/instancias   (auth:superadmin)
Transacción (todo o nada):
1. Crea la instancia (estado='activa', creada_por=superadmin, created_at=now()).
2. Genera credenciales temporales del admin inicial:
     usuario   = sugerido/derivado del nombre  (editable)
     password  = aleatoria fuerte (Str::password(16))
3. Crea el user administrador inicial de esa instancia:
     instancia_id = nueva instancia,
     rol = 'admin' (rol plantilla con permisos base),
     password_temporal = true, cambio_password_obligatorio = true,
     password_expira_en = hoy (fuerza cambio inmediato).
4. Siembra configuración inicial de la instancia (política de contraseña por
    defecto, configuraciones base).
5. Audita ('instancia.crear').
6. Responde UNA sola vez { instancia, credenciales_temporales } (Opción B).
     [Enganche futuro: si hay SMTP, enviar correo con las credenciales.]
```

La respuesta con la contraseña temporal en claro se entrega **una única vez**;
no se vuelve a exponer nunca (en BD solo está el hash).

---

## 7. Flujo del primer inicio de sesión (credenciales temporales)

```
1. El admin inicial hace login normal (POST /api/login).
2. La respuesta trae must_change_password = true (por password_temporal).
3. El middleware `password.valida` responde 423 (Locked) a CUALQUIER ruta
   protegida, salvo:
     - GET  /api/cuenta/estado-password
     - POST /api/cuenta/cambiar-usuario
     - POST /api/cuenta/cambiar-password
   El frontend, al ver 423 o el flag, redirige a la pantalla "Cambiar
   usuario y contraseña" y no deja navegar a ningún módulo.
4. POST /api/cuenta/cambiar-password:
     - valida password actual + nueva contra la política + historial (no repetir).
     - Hash; guarda en password_historial.
     - password_temporal=false, cambio_password_obligatorio=false,
       password_expira_en = hoy + dias_expiracion.
5. (Opcional) cambiar-usuario si el prompt exige renombrar el usuario temporal.
6. Tras cumplir, el modo temporal desaparece y el usuario navega normal.
```

El **mismo mecanismo** cubre la expiración por tiempo: cuando
`password_expira_en < hoy`, `must_change_password` se vuelve true y el usuario
cae en el mismo embudo de cambio obligatorio.

---

## 8. Modelo de permisos y roles

### 8.1 Conceptos

- **Rol** (`roles`): plantilla con pocos valores (p. ej. `admin`, `cajero`,
  `cliente`). Da un set base de permisos vía `rol_permiso`.
- **Permiso** (`permisos`): acción sobre un módulo, con clave `modulo.accion`
  (`ventas.ver`, `ventas.crear`, `usuarios.editar`…).
- **Override individual** (`usuario_permiso`): agrega o quita permisos puntuales
  a un usuario concreto, sin tocar el rol. Esto satisface "cada permiso
  almacenado individualmente" y "no depender solo del nombre del rol".

### 8.2 Resolución (permisos efectivos)

```
efectivos(user) =
    ( permisos_de_rol(user.role_id)
      ∪ { p : usuario_permiso(user, p, concedido=true) } )
    ∖ { p : usuario_permiso(user, p, concedido=false) }
```

Se calcula al autenticar y se puede embeber como *abilities* del token Sanctum,
de modo que `permiso:ventas.crear` sea una comprobación en memoria (sin query
extra por request). Se invalida al editar permisos del usuario (re-emitir token
o cache corta).

### 8.3 "A qué módulos puede ingresar"

El acceso a un módulo se deriva de tener **al menos un permiso** de ese módulo
(p. ej. `ventas.ver`). El frontend arma el menú lateral con la lista de módulos
permitidos que devuelve `/api/me`. Backend igualmente protege cada endpoint con
`permiso:` (nunca confiar solo en el menú del front).

---

## 9. Estrategia de seguridad

1. **Hashing seguro:** Bcrypt (activo) con `password => 'hashed'`. Migrable a
   Argon2id cambiando `config/hashing.php`. Nunca texto plano; `password` en
   `$hidden`.
2. **Políticas de contraseña configurables** por instancia (`politicas_password`)
   validadas en un FormRequest reutilizable (`PasswordPolicyRule`).
3. **Expiración automática:** `password_expira_en`; al vencer → embudo de cambio
   obligatorio (§7).
4. **Anti-fuerza bruta:** `intentos_fallidos` + `bloqueado_hasta` por usuario, y
   *rate limiting* de Laravel por IP en las rutas de login
   (`throttle:login`, p. ej. 5/min).
5. **Auditoría** (`auditoria`) de acciones sensibles y **bitácora de logins**
   (`login_logs`), con IP y user-agent.
6. **Tokens seguros:** Sanctum, tokens hasheados en BD (ya es así), con
   `expires_at` (caducidad) y *abilities* = permisos efectivos. Logout revoca el
   token actual (ya implementado).
7. **Middleware por capas:** `auth:guard` → `role` → `permiso` → `instancia` →
   `password.valida`. Defensa en profundidad.
8. **Anti-escalación de privilegios:**
   - Superadmin en tabla y guard aparte (no alcanzable desde el panel admin).
   - `instancia_id` y `role_id` nunca se aceptan del cliente en creación/edición.
   - Un admin no puede otorgar permisos que él no posee (`assertSubsetDeMisPermisos`).
9. **Anti-acceso entre instancias:** Global Scope `PerteneceAInstancia` que
   inyecta `WHERE instancia_id = <actual>` en **toda** consulta de modelos
   operativos. El tenant se toma del token autenticado, no del request. Los
   escritos setean `instancia_id` en el evento `creating`.
10. **Validación server-side siempre** (FormRequests) y respuestas de error
    genéricas en login (no revelar si el usuario existe).

---

## 10. Escalabilidad y buenas prácticas

- **Multi-tenant de BD compartida + aislamiento lógico** (elegido): cientos de
  instancias y miles de usuarios sin una BD por negocio. Barato de operar,
  fácil de respaldar y migrar.
- **Índices por `instancia_id`** en todas las tablas con tenant; considerar
  índices compuestos frecuentes (`(instancia_id, estado)` en `pedidos`,
  `(instancia_id, disponible)` en `productos`).
- **Global Scope + trait** para que agregar `instancia_id` sea declarativo: un
  modelo nuevo solo hace `use PerteneceAInstancia;`.
- **Paginación obligatoria** en listados (evita traer miles de filas).
- **Camino de crecimiento** si una instancia se vuelve gigante: mover ese tenant
  a su propio esquema/BD sin reescribir la app (el Global Scope abstrae el
  origen). No hace falta hoy.
- **Colas** (`queue`) para correos y tareas pesadas cuando entre SMTP.
- **Seeders idempotentes** para módulos/permisos/roles y catálogos globales.
- **Feature flags por instancia** vía `configuraciones` (ya existe el patrón
  clave-valor).

---

## 11. Impacto si se decide "Superadmin dentro de `users`" (alternativa a D1)

Si finalmente NO se separa la tabla:
- `superadministradores` no se crea; el superadmin es `users.role_id → super_admin`
  con `instancia_id = NULL`.
- El Global Scope debe **exceptuar** explícitamente a superadmins (o desactivarse
  para ellos), lo que agrega casos borde y riesgo.
- El login es único; el aislamiento pasa a ser solo por middleware/rol.
- Se pierde la garantía anti-escalación por diseño (queda dependiente de que
  ningún endpoint filtre `role_id`).

Es viable y con menos tablas, pero **no cumple** el "aislamiento total" del
prompt. Por eso la recomendación se mantiene en tabla separada.

---

## 12. Cambios en el frontend (Ionic + Angular), en su línea actual

- **Nuevo módulo `superadmin/`** paralelo a `admin/`, con su propio
  shell, su `superadmin-auth.service.ts`, su `superadmin.guard.ts` y su token
  guardado con una **clave distinta** en storage (sesión separada de verdad).
- Ruta y login distintos (p. ej. `/superadmin/login`), interceptor que adjunta
  el token del superadmin solo a `/api/superadmin/*`.
- **Guard de permisos** en `admin/`: cada ruta declara el permiso requerido; el
  guard consulta los permisos que devolvió `/api/me`.
- **Interceptor de contraseña**: si una respuesta llega 423 o `me` trae
  `must_change_password`, redirige a "Cambiar contraseña" y bloquea el resto.
- Reutilizar los `core/services` y `core/guards` existentes como base.

---

## 13. Plan de implementación por fases (sugerido)

| Fase | Entrega | Depende de |
|---|---|---|
| **F0** | SQL: `instancias`, `instancia_id` + backfill "instancia 1", índices. Sistema actual sigue igual, ya "tenantizado". | — |
| **F1** | Tabla `superadministradores` + guard + login superadmin + CRUD superadmins. | F0 |
| **F2** | CRUD de instancias + credenciales temporales (mostrar una vez) + seed inicial por instancia. | F1 |
| **F3** | Módulos + permisos + `rol_permiso` + `usuario_permiso` + middleware `permiso:` + CRUD usuarios con permisos. | F0 |
| **F4** | Políticas de contraseña + expiración + primer-login obligatorio + anti-fuerza-bruta. | F3 |
| **F5** | Auditoría + login_logs + password_historial. | F3 |
| **F6** | Catálogos globales (provincias/cantones/distritos). | F1 |
| **F7** | Frontend: shell superadmin, guards de permiso, interceptor de contraseña. | F1–F4 |
| **F8** | (Futuro) SMTP: envío de credenciales y "olvidé mi contraseña". | F2 |

**Aplicación del SQL:** la BD se mantiene por scripts SQL versionados en
`bd-doc/` (no `php artisan migrate`), igual que la migración de Sanctum. Cada
fase entrega un archivo `migracion_<fecha>_<fase>.sql` con secciones **forward**
y **rollback**, que se ejecuta en pgAdmin. Al terminar, se actualiza el
`rooster_pizza_bd.sql` maestro.

---

## 14. Mejoras adicionales (robustez profesional)

- **2FA/MFA** (TOTP) para superadmins y admins críticos.
- **Rotación y expiración de tokens** + "cerrar todas las sesiones".
- **Soft delete + papelera** en instancias y usuarios (ya hay `deleted_at`).
- **Suspender instancia** en cascada lógica (usuarios de una instancia suspendida
  no pueden entrar) — vía chequeo de `instancias.estado` en el login.
- **Exportar auditoría** y alertas ante patrones sospechosos (muchos fallos).
- **`Str::password()`** para credenciales temporales y **caducidad de la temporal**
  (p. ej. la instancia debe activarse en X días o la temporal expira).
- **Política de contraseña por rol** (además de por instancia) si se necesita más
  granularidad.
- **Health-check por instancia** y métricas de uso para el superadmin.
- **Pruebas**: tests de aislamiento (un usuario de A jamás ve datos de B) como
  suite obligatoria en CI.

---

*Documento de diseño. Implementación por fases y sujeta a aprobación del esquema
(regla del proyecto: no se crean tablas sin visto bueno explícito).*
