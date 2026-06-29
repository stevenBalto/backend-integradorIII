# ARQUITECTURA — Backend Rooster Pizza & Grill

> **Para qué sirve este documento:** es el contrato de estilo y arquitectura del backend. Entregáselo completo a cualquier IA o desarrollador que vaya a crear, modificar o extender código. Regla de oro: **todo módulo nuevo debe ser indistinguible de los existentes.** Se copia el patrón vigente; no se inventan patrones, no se "moderniza" porque sí.
>
> **Origen y alcance:** este documento adapta una arquitectura de referencia por capas (originalmente PHP nativo, sin frameworks) al stack real del proyecto: **Laravel + PostgreSQL**. La **base conceptual** (separación estricta por capas, seguridad, scoping de datos, contrato de respuestas, anti-patrones) se mantiene; los **mecanismos** se implementan con las herramientas propias de Laravel. Si algún detalle del patrón base no encaja con Laravel, se adapta lo mínimo necesario conservando el espíritu.

---

## 0. Instrucción directa a la IA / dev que reciba este documento

Vas a trabajar sobre un backend Laravel (API REST) para Rooster Pizza & Grill. Antes de escribir una sola línea:

1. **Respeta el patrón existente al 100%.** Si dudás cómo hacer algo, abrí un módulo ya hecho (por ejemplo `Producto` o `Pedido`) y replicalo: mismas capas, mismos nombres, mismo estilo de PHPDoc, mismo manejo de errores.
2. **No inventes esquema.** No agregues tablas ni columnas que no estén en las migraciones existentes (esquema de 21 tablas). Si falta un campo, preguntá antes. La autoridad sobre cambios de esquema es `db-schema-guardian`.
3. **No rompas contratos.** Las rutas, el formato de respuesta JSON (API Resources) y los nombres de roles ya están definidos. Cambiarlos rompe el frontend Ionic.
4. **Seguridad por rol y por sucursal.** El rol y la sucursal (`sucursal_id`) se leen SIEMPRE del usuario autenticado en el servidor, nunca del cliente. Omitirlo es un bug de seguridad.
5. **Idioma del código en español de dominio.** Clases en PascalCase, métodos/variables en camelCase, ambos con vocabulario de dominio en español (`crearPedido`, `$pedidoId`). Tablas y columnas en `snake_case`. Mensajes de usuario en español natural.

---

## 1. Identidad del proyecto

| Atributo | Valor |
|---|---|
| **Nombre** | Backend Rooster Pizza & Grill (Proyecto Integrador III, UTN Guanacaste) |
| **Tipo** | API REST (solo JSON, sin vistas Blade de cara al cliente) |
| **Consumidor** | Frontend Ionic separado (repo `frotend-integradorIII`): app cliente, panel admin, kiosko |
| **Dominio** | Pedidos (comer aquí / para llevar), catálogo (pizzas, grill, pastas, bebidas), ofertas y cupones, puntos de fidelidad, clientes, usuarios/roles, reseñas, reportes, sucursales |
| **Negocio** | Food truck en La Fortuna, San Carlos, Costa Rica. Una sucursal hoy, diseño escalable a varias. Sin delivery. Pago solo en caja. |

Contexto general del producto: ver `documentacion/ContextoGeneral.md`.

---

## 2. Stack técnico

* **Lenguaje:** PHP 8 con `declare(strict_types=1);` donde aplique.
* **Framework:** Laravel (Controller-Service-Repository + DTOs + API Resources). Composer + autoload PSR-4.
* **Base de datos:** PostgreSQL. 21 tablas, 28 FK (todas 1-M).
* **Herramientas BD:** pgAdmin 4 para administrar y consultar PostgreSQL.
* **Pruebas de API:** Postman (verificar que cada endpoint responde el contrato esperado).
* **Acceso a datos:** Eloquent ORM / Query Builder (prepared statements automáticos vía PDO; nunca SQL crudo con input del usuario).
* **Autenticación:** Laravel Sanctum (Bearer token).
* **Servidor de desarrollo:** XAMPP/Apache en Windows o `php artisan serve`.
* **Formato de salida:** JSON exclusivamente, vía **API Resources** de Laravel.
* **Seguridad base:** contraseñas con `Hash::make` (bcrypt), tokens vía Sanctum, prepared statements en todo acceso a BD, validación con Form Requests.

> Nota de limpieza: Laravel trae muchas carpetas y dependencias por defecto. Se irá depurando lo que no se use; no eliminar nada del core sin estar seguro de que no se usa.

---

## 3. Estructura de carpetas y responsabilidad de cada capa

Estructura relevante (sobre la base estándar de Laravel):

```
backend-integradorIII/
├── app/
│   ├── Http/
│   │   ├── Controllers/        # Orquestan: reciben request, delegan al Service, responden con Resource
│   │   ├── Requests/           # Form Requests: validación/normalización de entrada
│   │   ├── Resources/          # API Resources: forma del JSON de salida (data, whenLoaded, etc.)
│   │   └── Middleware/         # Transversal: auth, rol, sucursal
│   ├── Services/               # Lógica de negocio. Reciben/devuelven DTOs. NO tocan request ni emiten JSON
│   ├── Repositories/           # ÚNICO lugar con consultas Eloquent. Reciben parámetros, devuelven modelos/colecciones
│   ├── DTOs/                   # Objetos planos tipados (sin lógica) entre capas
│   ├── Models/                 # Modelos Eloquent (tablas, relaciones, $fillable, casts)
│   ├── Policies/               # Autorización por acción y por rol/sucursal
│   └── Exceptions/             # Excepciones de dominio tipadas + Handler
├── routes/
│   └── api.php                 # Definición de rutas REST + middleware por grupo
├── database/
│   ├── migrations/             # Esquema versionado (Schema::create/table) con down()
│   ├── seeders/                # Datos de prueba idempotentes (locale es_CR)
│   └── factories/
├── config/                     # database.php, sanctum.php, etc.
└── .env                        # Credenciales y entorno (NO versionar)
```

### Responsabilidad estricta por capa (qué SÍ / qué NO)

| Capa | SÍ hace | NO hace |
|---|---|---|
| **Route (`api.php`)** | Mapea método+URI a una acción de Controller; aplica middleware (`auth:sanctum`, rol, sucursal). | Validar payload, tocar BD, lógica. |
| **Middleware / Policy** | Auth (Sanctum), guardas de rol, scoping por sucursal, cortar la petición si no autoriza. | Lógica de dominio. |
| **Form Request** | Validar/normalizar el input. Reglas de tipo, longitud, requeridos. | Tocar BD, lógica de negocio. |
| **Controller** | Recibir el Form Request, llamar al Service con un DTO, responder con un API Resource y código HTTP. | SQL/Eloquent directo, reglas de negocio. |
| **Service** | Reglas de negocio, orquesta Repositories, resuelve rol/sucursal vía usuario autenticado, lanza excepciones tipadas. Recibe/devuelve DTOs. | Leer `$request`/superglobales, emitir JSON, escribir consultas Eloquent. |
| **Repository** | **Único** que ejecuta Eloquent/Query Builder. Recibe parámetros (ids, filtros, `sucursalId`). Devuelve modelos o colecciones. | Reglas de negocio, formatear respuesta HTTP. |
| **DTO** | Contenedor de datos tipado (propiedades públicas). | Cualquier lógica. |
| **API Resource** | Transformar modelo/colección en el JSON de salida; `whenLoaded` para relaciones. | SQL, validación, lógica; exponer campos sensibles. |
| **Model (Eloquent)** | Definir tabla, relaciones, `$fillable`, `$casts`, scopes simples. | Reglas de negocio complejas; consultas las orquesta el Repository. |

---

## 4. Flujo de una petición (pipeline completo)

```
routes/api.php  (match método + URI, grupo con middleware)
  → Middleware: auth:sanctum            (valida Bearer token, carga usuario autenticado)
  → Middleware/Policy: rol + sucursal   (super_admin / admin_sede / cliente; scope por sucursal)
    → Form Request                      (valida y normaliza el input; 422 si falla)
      → Controller::accion(FormRequest) (arma DTO de entrada)
        → Service::accion(DTO)          (reglas de negocio; usa $request->user() para rol/sucursal)
          → Repository::queryX(..., $sucursalId)
            → Model (Eloquent)  → devuelve modelo/colección
        ← Service devuelve DTO/Modelo
      → Controller responde con XxxResource / XxxCollection (JSON)
  → Excepción no controlada → Handler → JSON de error estandarizado
```

Laravel autocarga las clases (Composer PSR-4): **no** hay `require_once` manual. Para agregar un módulo basta crear las clases en sus carpetas y registrar la **ruta** en `routes/api.php`.

---

## 5. Conexión a base de datos

* La conexión se configura en `config/database.php` leyendo de `.env` (`DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
* Se administra con **pgAdmin 4**.
* **Todo acceso a datos pasa por un Repository** que usa Eloquent o Query Builder. Eloquent usa PDO con prepared statements automáticamente: nunca concatenar input del usuario en SQL crudo.

```php
// Repository: única capa que consulta la BD
final class ProductoRepository
{
    // El catálogo (productos) es GLOBAL: no tiene sucursal_id. El scoping por
    // sucursal aplica a tablas que sí la tienen (pedidos, users).
    public function findDisponiblesByCategoria(int $categoriaId): Collection
    {
        return Producto::query()
            ->where('categoria_id', $categoriaId)
            ->where('disponible', true)
            ->whereNull('deleted_at')
            ->get();
    }
}
```

* **Transacciones:** cuando una operación escribe en varias tablas, envolver en `DB::transaction(function () { ... })` dentro del Service/Repository (ej. crear un pedido con sus detalles).
* Evitar `DB::raw` con datos de usuario. Si se necesita una expresión, parametrizar siempre.

---

## 6. Alcance por rol y por sucursal (análogo a multi-tenant)

Rooster es un solo negocio con **una sucursal hoy y diseño escalable a varias**. El aislamiento no es entre organizaciones, sino **entre sucursales (`sucursal_id`)** y por rol.

Reglas obligatorias:

1. **El rol y la `sucursal` se obtienen SIEMPRE del usuario autenticado** (`$request->user()` / token Sanctum), nunca del body o del query. Confiar en un `sucursal_id` enviado por el cliente es vulnerabilidad.
2. **`admin_sede` solo opera sobre su sucursal:** todo SQL de dominio con datos por sucursal filtra por `sucursal_id` del usuario.
3. **`super_admin` es global:** ve todas las sucursales y la configuración global; no está limitado por `sucursal_id`.
4. **`cliente` solo ve/crea lo suyo:** sus pedidos, sus cupones, su perfil; y el menú público.
5. El Service resuelve el scope y lo pasa al Repository como parámetro:
   ```php
   public function listarPedidos(ListarPedidosDTO $dto, User $usuario): array
   {
       $sucursalId = $usuario->esSuperAdmin() ? null : $usuario->sucursal_id;
       return $this->pedidoRepository->findAll($dto, $sucursalId);
   }
   ```

> **Qué tiene `sucursal_id`:** solo `users` y `pedidos`. El catálogo (`categorias`, `productos`, `extras`, `ofertas`, `cupones`) es **global** (un solo menú; el alcance excluye menús por sucursal). El scoping de `admin_sede` aplica sobre todo a `pedidos`.
>
> Como hoy hay una sola sucursal, el scoping por `sucursal_id` puede ser trivial, pero se implementa igual para que escalar a varias no requiera reescribir.

---

## 7. Autenticación y autorización

### Token (Laravel Sanctum)
* Login valida credenciales con `Hash::check` (bcrypt) y emite un token Sanctum. El cliente lo envía como `Authorization: Bearer <token>` en cada petición.
* Logout invalida el token (`currentAccessToken()->delete()`).

### Middleware / Policies
* Rutas protegidas con el middleware `auth:sanctum`.
* Autorización por acción con **Policies/Gates** de Laravel (`PedidoPolicy@view`, etc.), no con `if` sueltos en el Controller.
* El `role` se lee del usuario en BD, **nunca** de un campo enviado por el cliente.

### Roles vigentes
| Rol | Alcance |
|---|---|
| `super_admin` | Acceso total: todas las sucursales y configuración global. Sin límite de sucursal. |
| `admin_sede` | Todo dentro de su sucursal asignada (`sucursal_id`). No ve datos de otras sucursales. |
| `cliente` | Sus propios pedidos, cupones y perfil; menú público. |

> Los roles viven en la tabla `roles`; cada usuario referencia uno por `users.role_id`. El rol se lee vía esa relación (p. ej. un helper `User::esSuperAdmin()` que compara `role->nombre`), nunca desde el cliente.

### Reglas de autorización
* `admin_sede` no puede acceder a recursos de otra sucursal (filtro por `sucursal_id` + Policy).
* `cliente` no puede ver ni modificar pedidos de otros usuarios (evitar IDOR: validar pertenencia, no solo `findOrFail`).
* Operaciones destructivas y de configuración global: solo `super_admin`.

---

## 8. Formato de respuestas JSON (API Resources)

Todas las respuestas se emiten con **API Resources** de Laravel. Nunca devolver el modelo crudo ni `->toArray()` manual.

**Recurso único:**
```json
{ "data": { "id": 1, "nombre": "Pizza Margherita", "precio": 8500.00, "categoria": { "id": 2, "nombre": "Pizzas" } } }
```

**Colección paginada:**
```json
{
  "data": [ { "id": 1, "nombre": "..." } ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "last_page": 5, "per_page": 10, "total": 47 }
}
```

**Error de validación (422) / error general:**
```json
{ "message": "Descripción legible del error", "errors": { "campo": ["El campo es requerido"] } }
```

Convenciones del contrato (las vigila `api-contract-checker`):
* Campos en `snake_case`, tipos correctos (precios numéricos `float`, no string; `id` integer).
* Fechas en ISO 8601. Relaciones opcionales con `whenLoaded()` para evitar N+1.
* Nunca exponer `password`, tokens ni timestamps internos innecesarios.
* Estados/enums con valores string predecibles y documentados (estado de pedido: `pendiente`, `en_proceso`, `listo`, `entregado`, `cancelado`; disponibilidad de producto: `disponible` booleano).

### Códigos HTTP canónicos
| HTTP | Cuándo |
|---|---|
| 200 | OK (lectura / actualización). |
| 201 | Recurso creado. |
| 401 | No autenticado (sin token o token inválido). |
| 403 | Rol o sucursal insuficiente. |
| 404 | Recurso inexistente. |
| 422 | Validación fallida (Form Request). |
| 500 | Error interno no controlado. |

---

## 9. Manejo de errores

* **El Controller es la frontera de errores.** El Service lanza excepciones tipadas; el Controller (o el Handler global) las mapea a HTTP.
* **Convención de excepciones de dominio:**
  - Validación / regla de negocio violada → 422 (Form Request) o `ValidationException`.
  - Recurso no encontrado → 404 (`ModelNotFoundException` / excepción de dominio).
  - Rol/sucursal insuficiente → 403 (`AuthorizationException` vía Policy).
  - No autenticado → 401.
  - Cualquier `\Throwable` no controlado → 500 genérico, registrando el detalle en log (`Log::error(...)`), **sin** filtrar internals al cliente.
* El `App\Exceptions\Handler` centraliza el render a JSON. En producción, `APP_DEBUG=false` para no exponer stack traces.

```php
public function crear(CrearPedidoRequest $request): JsonResponse
{
    $dto = CrearPedidoDTO::fromRequest($request->validated());
    $pedido = $this->pedidoService->crear($dto, $request->user());
    return (new PedidoResource($pedido))
        ->response()
        ->setStatusCode(201);
}
```

---

## 10. Validación

* La validación de entrada vive en **Form Requests** (`app/Http/Requests`), no en el Controller ni en el Service.
* El Form Request define `rules()` (tipos, longitudes, requeridos, valores permitidos) y opcionalmente `authorize()`.
* El Controller usa `$request->validated()` para construir el DTO de entrada.
* Reglas de negocio más profundas (que dependen de estado en BD) van en el Service y lanzan excepciones tipadas.

```php
final class CrearPedidoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tipo'            => ['required', 'in:comer_aqui,para_llevar'],
            'items'          => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'items.*.cantidad'    => ['required', 'integer', 'min:1'],
        ];
    }
}
```

---

## 11. Migraciones y esquema

* El esquema se versiona con **migraciones de Laravel** (`database/migrations`), aplicadas con `php artisan migrate`. Cada migración define `up()` y `down()` (rollback seguro).
* **No agregar, eliminar ni renombrar tablas o columnas sin aprobación explícita** (lo vigila `db-schema-guardian`). Cambios permitidos sin aprobación especial: columnas nullable nuevas, índices, seeders, `deleted_at` donde falte.
* Toda tabla de dominio: `snake_case`, FK `tabla_id`, columnas de auditoría `created_at`/`updated_at`. Soft delete (`deleted_at`) **solo** en `users`, `productos` y `metodos_pago`; el resto usa borrado normal.
* Reglas de esquema del proyecto: sin tabla `direcciones` (no hay delivery); horarios en `configuraciones` (clave-valor); precios congelados en el detalle de pedido al momento de la compra (copiar valor, no FK al precio actual).
* El esquema vigente y sus versiones se documentan en `back-document/bd-doc/` (incluye `rooster_pizza_bd.sql`).
* Seeders: idempotentes (`firstOrCreate`/`updateOrCreate`), locale `es_CR`, respetando el orden de dependencias FK.

### 11.1 Esquema real vigente (21 tablas)

Fuente: `bd-doc/rooster_pizza_bd.sql` (DDL PostgreSQL reconstruido del ERD). Nombres reales a respetar:

- **Catálogo base:** `roles`, `sucursales`, `configuraciones`, `faqs`, `categorias`.
- **Usuarios:** `users` (`role_id`, `sucursal_id`, `puntos_balance`, soft delete), `personal_access_tokens` (tokens Sanctum).
- **Productos/extras:** `productos` (`categoria_id`, `precio_base`, `disponible`, `destacado`, soft delete), `extras` (`categoria_id`; modificadores por categoría).
- **Ofertas:** `ofertas` (`tipo_descuento`, `valor`), `oferta_producto` (pivote oferta↔producto).
- **Cupones:** `cupones` (`codigo`, `tipo`, `valor`, `monto_minimo`, `usos_max`, `usos_actuales`). Global, sin `sucursal_id`.
- **Pagos (registro):** `metodos_pago` (`user_id`, `tipo`, `ultimos4`, soft delete), `pagos` (`pedido_id`, `metodo_pago_id`, `monto`, `estado`).
- **Pedidos:** `pedidos` (`cliente_id`→`users`, `sucursal_id`, `cupon_id`, `modalidad`, `estado`, `subtotal`, `descuento`, `total`, `puntos_ganados`), `detalle_pedido` (singular; `precio_unitario` = precio congelado), `detalle_pedido_extras`, `cupon_uso`.
- **Historial/reseñas/puntos:** `pedido_historial_estado`, `resenas` (`calificacion`, `estado`, `respuesta_admin`), `puntos_movimientos`.

Hechos clave (verificar nulabilidad/ON DELETE contra las migraciones reales):
- **`sucursal_id` solo en `users` y `pedidos`.** El catálogo (categorias, productos, extras, ofertas, cupones) es **global** (un solo menú; el alcance excluye menús por sucursal). El scoping de `admin_sede` aplica sobre todo a `pedidos`.
- **Precio congelado:** `detalle_pedido.precio_unitario` guarda el precio al momento de la compra (no FK al precio vigente).
- **Soft delete solo** en `users`, `productos`, `metodos_pago`.
- **`modalidad`** del pedido = comer aquí / para llevar. **`estado`** es `varchar(20)` sin CHECK en el DDL; valores definidos por la app: `pendiente`, `en_proceso`, `listo`, `entregado`, `cancelado`.
- **Puntos:** `users.puntos_balance` + `pedidos.puntos_ganados` + tabla `puntos_movimientos`.
- **Pago:** en esta versión es **solo en caja**. Las tablas `metodos_pago` (con `ultimos4`, `token`) y `pagos` existen pero **quedan para escalabilidad**; no se usan en esta versión.

---

## 12. Receta para agregar un módulo nuevo (paso a paso)

Supongamos la entidad `Cupon`. Creá, en este orden, copiando el estilo de un módulo existente (`Producto` o `Pedido`):

1. **Migración:** `php artisan make:migration create_cupones_table` con `snake_case`, FK, auditoría y `down()`. (Si toca esquema sensible, pasar por `db-schema-guardian` primero.)
2. **Modelo:** `app/Models/Cupon.php` con `$fillable`, `$casts`, relaciones.
3. **Repository:** `app/Repositories/CuponRepository.php` — única capa con Eloquent; métodos reciben `sucursalId` cuando aplica.
4. **DTO:** `app/DTOs/CrearCuponDTO.php` (y de salida si aplica) — propiedades públicas tipadas, sin lógica.
5. **Service:** `app/Services/CuponService.php` — instancia/inyecta el Repository, resuelve rol/sucursal, aplica reglas, lanza excepciones tipadas.
6. **Form Request:** `app/Http/Requests/CrearCuponRequest.php` — `rules()`.
7. **API Resource:** `app/Http/Resources/CuponResource.php` (+ `CuponCollection` si hay listas).
8. **Policy:** `app/Policies/CuponPolicy.php` — autorización por rol/sucursal; registrarla.
9. **Controller:** `app/Http/Controllers/CuponController.php` — recibe Form Request, llama al Service, responde con Resource.
10. **Ruta:** registrar en `routes/api.php` dentro del grupo con `auth:sanctum` y middleware de rol.
11. **Verificar:** `api-contract-checker` (contrato), `code-verifier` (capas), `security-reviewer` (auth/rol), `consistency-checker` (alineación con el front). Documentar el cierre con `doc-updater`.

### Esqueletos mínimos (copiar el estilo)

**Ruta (`routes/api.php`):**
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/cupones', [CuponController::class, 'index']);
    Route::post('/cupones', [CuponController::class, 'store'])->middleware('can:create,App\Models\Cupon');
});
```

**Controller:**
```php
<?php
declare(strict_types=1);

namespace App\Http\Controllers;

final class CuponController extends Controller
{
    public function __construct(private CuponService $service) {}

    public function index(ListarCuponesRequest $request): AnonymousResourceCollection
    {
        $cupones = $this->service->listar(ListarCuponesDTO::fromArray($request->validated()));
        return CuponResource::collection($cupones);
    }

    public function store(CrearCuponRequest $request): JsonResponse
    {
        $cupon = $this->service->crear(
            CrearCuponDTO::fromArray($request->validated()),
            $request->user()
        );
        return (new CuponResource($cupon))->response()->setStatusCode(201);
    }
}
```

**Service:**
```php
<?php
declare(strict_types=1);

namespace App\Services;

final class CuponService
{
    public function __construct(private CuponRepository $cuponRepository) {}

    public function listar(ListarCuponesDTO $dto): Collection
    {
        // cupones es una tabla GLOBAL (no tiene sucursal_id).
        return $this->cuponRepository->findAll($dto);
    }
}
```

**Repository (único con Eloquent):**
```php
<?php
declare(strict_types=1);

namespace App\Repositories;

final class CuponRepository
{
    public function findAll(ListarCuponesDTO $dto): Collection
    {
        return Cupon::query()
            ->when($dto->soloActivos, fn ($q) => $q->where('activo', true))
            ->get();
    }
}
```

**API Resource:**
```php
<?php
declare(strict_types=1);

namespace App\Http\Resources;

final class CuponResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'codigo'       => $this->codigo,
            'tipo'         => $this->tipo,
            'valor'        => (float) $this->valor,
            'monto_minimo' => $this->monto_minimo !== null ? (float) $this->monto_minimo : null,
            'activo'       => (bool) $this->activo,
        ];
    }
}
```

---

## 13. Convenciones de código

* `declare(strict_types=1);` y `namespace` correcto en cada archivo (PSR-4).
* Clases `final` por defecto; una clase pública por archivo (nombre del archivo = nombre de la clase).
* Sufijos por capa: `Controller`, `Service`, `Repository`, `DTO`, `Resource`, `Request`, `Policy`.
* **Nombres de dominio en español:** clases PascalCase (`PedidoService`, `ProductoRepository`), métodos camelCase con verbo (`crearPedido`, `listarActivos`, `findByCategoria`).
* DTO: propiedades públicas en `camelCase` (`$sucursalId`); columnas BD en `snake_case` (`sucursal_id`). El mapeo lo hacen el DTO/Resource/Model `$casts`.
* Rutas API en kebab-case plural (`/api/productos`, `/api/pedidos`).
* PHPDoc en español sobre clases y métodos públicos (`@param`, `@return`, `@throws`).
* Inyección de dependencias por constructor (Laravel resuelve el contenedor); no instanciar a mano lo que el contenedor puede inyectar.
* Mensajes de usuario en español natural, claros, orientados a usuarios poco técnicos.

---

## 14. Reglas estrictas / anti-patrones (NO hacer)

1. No poner lógica de negocio ni consultas Eloquent en el Controller.
2. No hacer consultas a BD fuera de un Repository (ni `DB::`/Eloquent en Service o Controller).
3. No devolver modelos crudos ni `->toArray()` manual: siempre API Resource.
4. No exponer campos sensibles (`password`, tokens, timestamps internos) en los Resources.
5. No aceptar `role` ni `sucursal_id` desde el cliente: leerlos del usuario autenticado.
6. No concatenar input de usuario en SQL crudo: usar Eloquent/Query Builder con bindings.
7. No filtrar errores internos al cliente: 500 genérico + detalle en log.
8. No cambiar rutas, nombres de roles ni formato de respuesta de endpoints existentes sin sincronizar con el frontend.
9. No mezclar capas (Controller con SQL, Repository con reglas de negocio, Form Request con BD pesada).
10. No inventar tablas/columnas fuera del esquema de 21 tablas sin aprobación de `db-schema-guardian`.
11. No agregar funcionalidad fuera de alcance: delivery, menús por sucursal, pagos online, push marketing automatizado.

---

## 15. Sincronización con el frontend

El backend y el frontend Ionic comparten contrato de API (rutas, formato de datos, estados, roles). Cualquier cambio en **auth, roles, rutas o contrato de datos** debe coordinarse con el frontend. La alineación la vigila `consistency-checker`; el contrato JSON de cada endpoint, `api-contract-checker`. Los repos son independientes: romper un lado rompe el otro.

---

## 16. Documentos a leer antes de programar

1. `documentacion/ContextoGeneral.md` — visión general del producto.
2. `documentacion/CLAUDE.md` — protocolo de enrutamiento y matriz de subagentes.
3. `documentacion/Subgantes-Doc.md` — qué hace cada subagente.
4. `documentacion/back-document/ARQUITECTURA.md` — este documento.
5. `documentacion/back-document/bd-doc/` — esquema y versiones de la BD (`rooster_pizza_bd.sql`).
6. `documentacion/back-document/HiloActualBack.md` — estado/hilo actual del backend.
7. `documentacion/back-document/AntierroresBack.md` — errores conocidos y cómo evitarlos.

> **Modo de arranque:** antes de tocar código, leer los documentos, entregar un brief corto (alcance, riesgos, archivos impactados) y esperar autorización explícita. Sin autorización, permanecer en modo análisis. Trabajar por tarea acotada, no por fase completa. Para cambios de esquema, pasar por `db-schema-guardian`.

---

### Resumen en una frase

**Laravel + PostgreSQL, API REST por capas (Route → Middleware/Policy → Form Request → Controller → Service → Repository → Model, con DTOs y API Resources), Eloquent con prepared statements, autorización por rol y por sucursal (`sucursal_id`) tomada siempre del usuario autenticado, respuestas JSON estandarizadas con API Resources (`data`/`links`/`meta`), esquema de 21 tablas inmutable sin aprobación. Copia el módulo `Producto`/`Pedido` como plantilla y no rompas contratos.**

*Última actualización: 2026-06-28.*
