# BACKEND — Contexto del Proyecto Rooster Pizza & Grill

Backend del Proyecto Integrador III. Cliente ficticio: Rooster Pizza &
Grill. Stack: Laravel (Controller-Service-Repository + DTOs + API
Resources) sobre PostgreSQL (21 tablas, 28 FK, todas 1-M). El repo hermano
`frotend-integradorIII` es Ionic y se comunica con este backend solo por
API REST — no comparten subagentes.

## Protocolo de enrutamiento
Antes de responder una tarea de implementación o revisión, anunciá en una
línea cuál subagente vas a usar y por qué:
`Agente: [nombre] — Razón: [una línea breve]`
Si el usuario indica que prefiere otro agente, usá ese sin discutir.

## Matriz de decisión

| Intención del usuario | Agente | Modelo |
|---|---|---|
| Implementar/crear/modificar controllers, services, repositories, DTOs, API Resources | backend-developer | opus |
| Crear/modificar migraciones, seeders, índices respetando el esquema de 21 tablas | db-specialist | opus |
| Revisar código contra el patrón Controller-Service-Repository y convenciones de nombres | code-verifier | sonnet |
| Revisar autenticación, roles (super_admin/admin_sede/cliente), validación de inputs | security-reviewer | sonnet |
| Verificar que un endpoint nuevo tenga su API Resource consistente | api-contract-checker | sonnet |
| Actualizar documentación técnica viva del proyecto | doc-updater | sonnet |
| Vigilar que un cambio no rompa el esquema de 21 tablas sin aprobación explícita | db-schema-guardian | sonnet |
| Verificar alineación de nombres/estados entre lo que expone backend y lo que frontend espera | consistency-checker | sonnet |
| Pregunta general, arquitectura, planificación | Respuesta directa (sin agente) | — |

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
