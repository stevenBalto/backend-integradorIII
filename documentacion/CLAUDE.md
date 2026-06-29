# BACKEND — Contexto del Proyecto Rooster Pizza & Grill

Backend del Proyecto Integrador III. Cliente ficticio: Rooster Pizza &
Grill. Stack: Laravel (Controller-Service-Repository + DTOs + API
Resources) sobre PostgreSQL (21 tablas, 28 FK, todas 1-M). El repo hermano
`frotend-integradorIII` es Ionic y se comunica con este backend solo por
API REST — no comparten subagentes.

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
- 21 tablas, 28 relaciones, todas 1-M. Ningún agente agrega tablas nuevas
  sin aprobación explícita del usuario.
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
