# BACKEND — Contexto del Proyecto Rooster Pizza & Grill

Backend del Proyecto Integrador III. Cliente ficticio: Rooster Pizza &
Grill. Stack: Laravel (Controller-Service-Repository + DTOs + API
Resources) sobre PostgreSQL (21 tablas, 28 FK, todas 1-M). El repo hermano
`frotend-integradorIII` es Ionic y se comunica con este backend solo por
API REST — no comparten subagentes.

## Protocolo de sesión (OBLIGATORIO)
1. **Antes de empezar cualquier desarrollo**: revisar AMBOS repos (`backend-integradorIII`
   y `frotend-integradorIII`) por pulls pendientes (`git fetch origin` + comparar contra
   `origin/main`). Si hay commits nuevos, hacer `pull` (y resolver conflictos si aplica)
   ANTES de tocar código. No arrancar una tarea con un pull pendiente sin hacer.
2. **Al empezar sesión**: leer los `.md` de `documentacion/` (`ContextoGeneral.md`,
   `HiloActualBack.md`/`HiloActualFront.md`, `AntierroresBack.md`/`AntierroresFront.md`,
   `COMO-CORRER.md`) para ponerse al día antes de actuar.
3. **Al cerrar sesión**: no dar la tarea/sesión por terminada sin antes actualizar los
   `.md` pertinentes (como mínimo el `HiloActual*` de lo que se tocó; `Antierrores*` si
   se aprendió algo nuevo; `ContextoGeneral.md` si cambió el estado de un módulo).

## Restricción de dependencias — paquetes npm PROHIBIDOS (OBLIGATORIO)
Ninguno de los 4 desarrolladores (**Reyman, Steven, Bryan, Christian**) puede **instalar
ni agregar paquetes npm nuevos**. Queda **totalmente prohibido**: nada de
`npm install <paquete>`, ni agregar dependencias a `package.json`, ni traer librerías
de terceros para resolver una feature. Todo se hace con lo que **ya** está en el proyecto
(Angular / Ionic / RxJS del stack base) o con **código propio** (ejemplo: los logos de
Excel/PDF se hicieron como **SVG inline**, no con una librería de íconos). Para el backend
rige la misma filosofía con **Composer**: no agregar dependencias nuevas. Si una tarea
parece necesitar un paquete nuevo, NO instalarlo: buscar la solución con lo existente o
consultar al usuario primero.

## Protocolo de enrutamiento
El enrutamiento es automático en cada turno (lo hace el hilo principal con esta
matriz; el protocolo global está en `~/.claude/CLAUDE.md`). Antes de delegar o
responder, mostrá una línea de ruta visible:
`[Ruta] Subagente: <nombre|ninguno> | Modelo: <opus/sonnet/haiku> | Esfuerzo: <alto/medio/bajo> | Pensamiento: <on/off> | Motivo: <una frase>`
Si el usuario prefiere otro agente, usá ese sin discutir.

**Regla de gasto (ni tacaño ni derrochador):** elegí el tier más bajo que sirve.
Trivial/lookup/doc → `context-keeper` o respuesta directa (liviano, sin pensamiento).
Revisión → checkers sonnet. Construcción/riesgo → opus con pensamiento. Si la
intención es simple aunque toque un dominio pesado, bajá de tier automáticamente.

## Matriz de decisión

| Intención del usuario | Agente | Modelo | Esfuerzo | Pensamiento |
|---|---|---|---|---|
| Implementar/crear/modificar controllers, services, repositories, DTOs, API Resources | backend-developer | opus | alto | sí |
| Crear/modificar migraciones, seeders, índices respetando el esquema de 21 tablas | db-specialist | opus | alto | sí |
| Vigilar que un cambio no rompa el esquema de 21 tablas sin aprobación explícita | db-schema-guardian | sonnet | medio | sí |
| Revisar autenticación, roles (super_admin/admin_sede/cliente), validación de inputs | security-reviewer | sonnet | alto | sí |
| Revisar código contra el patrón Controller-Service-Repository y convenciones de nombres | code-verifier | sonnet | medio | no |
| Verificar que un endpoint nuevo tenga su API Resource consistente | api-contract-checker | sonnet | bajo | no |
| Verificar alineación de nombres/estados entre lo que expone backend y lo que frontend espera | consistency-checker | sonnet | medio | no |
| Actualizar documentación técnica viva del proyecto | doc-updater | sonnet | bajo | no |
| Preguntar sobre código/doc/contexto ya documentado | context-keeper | sonnet | bajo | no |
| Pedir recomendación explícita de ruta | orquestador | haiku | bajo | no |
| Pregunta general, arquitectura, planificación | Respuesta directa (sin agente) | liviano | bajo | no |

## Reglas del esquema
- 35 tablas (21/28 originales del ERD + `insumos`/`insumo_movimientos` del
  módulo Inventario 2026-07-13 + 6 tablas del multi-tenant/superadmin del
  compañero 2026-07-12/13 [`instancias`, `superadministradores`, `modulos`,
  `usuario_modulo`, `password_reset_tokens`, más columnas `instancia_id` en
  tablas raíz] + `producto_tamanos` del módulo Pedidos 2026-07-16 +
  `producto_extras` de Extras 2026-07-17, aprobado). `extras.imagen_url` y
  `producto_tamanos.descripcion` agregadas 2026-07-19 (aprobado, sin tabla
  nueva). `extras.categoria_id`
  ahora es nullable (extra "general" = `es_general=true`+`categoria_id NULL`,
  CHECK garantiza que no coexistan). Columnas aprobadas 2026-08-03:
  `usuario_modulo.permiso` (varchar(20), NOT NULL, DEFAULT 'lectura', CHECK
  ('lectura','editor')), `ofertas.imagen_url` (varchar(255) NULL),
  `cupones.imagen_url` (varchar(255) NULL). Agregadas 2026-08-07 por el
  compañero (ya en uso, feature "alcance por cliente específico"):
  `ofertas.alcance`/`cupones.alcance` (varchar(20), NOT NULL, DEFAULT 'todos',
  CHECK ('todos','especifico')) + las puente `oferta_cliente`/`cupon_cliente`
  (UNIQUE del par, FK a `ofertas`/`cupones`/`users` con ON DELETE CASCADE).
  **Cambio 2026-08-14 (aprobado): ofertas y cupones dejan de ser
  nacionales/globales.** `ofertas.instancia_id`/`cupones.instancia_id` pasan
  de nullable a **NOT NULL** (las filas preexistentes quedaron asignadas a la
  instancia 1, Rooster Pizza). `cupones.codigo` pasa de UNIQUE global a
  **UNIQUE(instancia_id, codigo)** — dos negocios distintos ya pueden repetir
  código. Se agregó `alcance_sedes` (varchar(20), NOT NULL, DEFAULT 'todas',
  CHECK ('todas','especifica')) a ambas tablas — no confundir con `alcance`
  (por CLIENTE): `alcance_sedes` restringe en cuáles SEDES del negocio se
  puede *canjear* la oferta/cupón (se sigue viendo/administrando desde
  cualquier sede, solo el canje se valida contra la sucursal del pedido). Se
  agregaron las puente `oferta_sucursal`/`cupon_sucursal` (mismo patrón que
  `oferta_cliente`/`cupon_cliente`: UNIQUE del par, FK a
  `ofertas`/`cupones`/`sucursales` con ON DELETE CASCADE). Ver
  `migracion_2026-08-14_ofertas_cupones_por_instancia.sql` y
  `migracion_2026-08-14_ofertas_cupones_alcance_sedes.sql`.
  Ningún agente agrega tablas nuevas sin aprobación explícita del usuario.
- **El conteo de 35 excluye `migrations`** (tabla de control de Laravel; el
  esquema se mantiene por SQL, no por migraciones — ver `COMO-CORRER.md`).
- **Las dos fuentes del esquema tienen que actualizarse SIEMPRE juntas**: el
  `.sql` incremental en `bd-doc/` y el dump `bd-doc/rooster_pizza_bd.sql`.
  Actualizar solo una rompe en silencio a media parte del equipo, según cada
  quien recargue desde el dump o mantenga su BD incremental (ver **EB-18**).
  A 2026-08-08 el dump está atrasado: le faltan `notificaciones` y 19 columnas.
- No existe tabla `direcciones` (no hay delivery en esta versión).
- Horarios viven en `configuraciones` (clave-valor), no en tabla separada.
- Nullability, DEFAULT y ON DELETE deben verificarse contra las migraciones
  reales de Laravel, no asumirse.

## Convenciones generales
- Tablas en plural, snake_case. FK como `tabla_id`.
- `created_at`, `updated_at`, `deleted_at` (soft delete) donde aplique.
- Precios se congelan en el detalle de pedido al momento de la compra.
- Alcance excluye intencionalmente: delivery, menús distintos por
  sucursal, marketing masivo automatizado. No agregar sin instrucción
  explícita del usuario.

## Git — regla estricta
Claude NUNCA, bajo ninguna circunstancia, se autoasigna como colaborador ni coautor del repositorio:
- No agregar el trailer `Co-Authored-By` de Claude/IA en los commits.
- No agregarse como collaborator en GitHub ni firmar como coautor.
- Los commits van únicamente a nombre del desarrollador humano.
