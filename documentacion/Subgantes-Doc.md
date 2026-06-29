# Subagentes — Rooster Pizza & Grill

Qué hace cada subagente, cuándo invocarlo y con qué recursos (modelo, esfuerzo, pensamiento).

- Definiciones: `~/.claude/agents/*.md` (globales: sirven en Back y Front). Copia fuente en `backend-integradorIII/.claude/agents/`.
- Proyecto: backend Laravel (API REST), patrón Controller-Service-Repository, DTOs, API Resources. PostgreSQL 21 tablas / 28 FK (1-M). Roles: super_admin, admin_sede, cliente. Sin delivery; horarios en `configuraciones`.
- Verdad técnica: solo `model` es nativo del subagente. `esfuerzo` y `pensamiento` son comportamiento + criterio de ruta. Los subagentes no spawnean otros; el enrutamiento automático lo hace el hilo principal vía CLAUDE.md.

## Enrutamiento

Automático cada turno (matriz en `CLAUDE.md` / `~/.claude/CLAUDE.md`). Antes de delegar se muestra la ruta:

```
[Ruta] Subagente: <nombre|ninguno> | Modelo: <opus/sonnet/haiku> | Esfuerzo: <alto/medio/bajo> | Pensamiento: <on/off> | Motivo: <una frase>
```

Regla de gasto: elegir el tier más bajo que sirve. Trivial/doc -> context-keeper o respuesta directa. Revisión -> checkers sonnet. Construcción/riesgo -> opus con pensamiento. Auto-downgrade si la consulta es simple.

## Matriz

| Subagente | Modelo | Esfuerzo | Pensamiento | Tipo | Función |
|-----------|--------|----------|-------------|------|---------|
| backend-developer | opus | alto | sí | Construcción | Implementa código Laravel |
| db-specialist | opus | alto | sí | Construcción | Migraciones, seeders, índices |
| db-schema-guardian | sonnet | medio | sí | Guardián | Bloquea cambios que rompan el esquema |
| security-reviewer | sonnet | alto | sí | Verificación | Auth, roles, validación de inputs |
| code-verifier | sonnet | medio | no | Verificación | Separación de capas y convenciones |
| api-contract-checker | sonnet | bajo | no | Verificación | Contrato JSON consistente |
| consistency-checker | sonnet | medio | no | Verificación | Alineación back ↔ front |
| doc-updater | sonnet | bajo | no | Mantenimiento | Documentación técnica viva |
| context-keeper | sonnet | bajo | no | Consulta | Responde desde la doc, sin escanear código |
| orquestador | haiku | bajo | no | Ruta | Recomienda a quién delegar |

---

## backend-developer  (opus / alto / pensamiento sí)
Implementa Controllers (delegan al Service), Services (lógica + DTOs), Repositories (Eloquent), DTOs y API Resources.
Reglas: no inventa tablas/columnas (pregunta antes); precios congelados en detalle de pedido; sin delivery; horarios en `configuraciones`; nombres PascalCase/camelCase/snake_case.
Invocar: implementar un endpoint o caso de uso nuevo.
Ej.: "Implementá el listado de productos activos por categoría, paginado de 10, solo clientes."

## db-specialist  (opus / alto / pensamiento sí)
Migraciones (`Schema::create/table`) con `down()` seguro, seeders idempotentes (`es_CR`), índices.
Reglas: nunca agrega/elimina/renombra tablas o columnas sin confirmación; lista migraciones afectadas antes; precios congelados.
Invocar: cambios de esquema, datos de prueba, índices.
Ej.: "Agregá `descripcion_corta` nullable a `productos`."

## db-schema-guardian  (sonnet / medio / pensamiento sí)
Segunda opinión antes de aplicar migraciones. Bloquea lo que rompa el esquema de 21 tablas salvo aprobación.
Requiere aprobación: nueva/eliminar/renombrar tabla o columna, cambiar tipo, M-M directa, cambiar ON DELETE, tabla de delivery.
Permitido: columnas nullable nuevas, índices, seeders, `deleted_at`.
Invocar: antes de correr una migración estructural. Complementa a db-specialist.
Ej.: "Revisá esta migración: agrega tabla `zonas_delivery`."

## security-reviewer  (sonnet / alto / pensamiento sí)
Audita auth (Sanctum), autorización por rol, validación de inputs. Solo revisa.
Vectores: IDOR, mass assignment (`$fillable`), SQL injection, rate limiting. El rol se lee de BD, no del cliente.
Invocar: endpoints con datos sensibles o acciones por rol, antes de producción.
Ej.: "Revisá seguridad del PedidoController."

## code-verifier  (sonnet / medio / pensamiento no)
Audita separación Controller-Service-Repository, DTOs, Resources y convenciones de nombres. Solo reporta.
Reporta: `[CAPA] Archivo:Línea — problema / corrección`.
Invocar: tras implementar, antes de cerrar.
Ej.: "Revisá el PedidoController."

## api-contract-checker  (sonnet / bajo / pensamiento no)
Verifica que cada endpoint exponga JSON consistente para Ionic. Solo revisa.
Chequea: Resource dedicado, `snake_case`, tipos correctos, `whenLoaded` (N+1), fechas ISO 8601, precios numéricos, `id` integer, sin campos internos.
Estructura: único `{data}`, lista `{data,links,meta}`, error `{message,errors}`.
Invocar: al crear/modificar un endpoint, antes de exponerlo.
Ej.: "Revisá el contrato de GET /api/productos/{id}."

## consistency-checker  (sonnet / medio / pensamiento no)
Detecta desalineamientos entre lo que el backend expone y lo que el front Ionic consume (repos independientes).
Chequea: nombres de campos, tipos (precio float vs string), estados/enums idénticos, estructura de respuesta, objetos anidados vs ID.
Reporta: `[SEVERIDAD] Campo / backend devuelve / frontend espera / impacto / corrección`.
Invocar: al integrar un módulo front con su endpoint o ante un bug de integración.
Ej.: "Revisá si pedidos está alineado con el panel admin."

## doc-updater  (sonnet / bajo / pensamiento no)
Mantiene la documentación técnica viva: endpoints, decisiones (ADR liviano), cambios de esquema aprobados, notas back-front.
No toca: requerimientos originales, código fuente, `CLAUDE.md`.
Invocar: al cerrar una tarea o módulo.
Ej.: "Cerramos pedidos, actualizá la doc."

## context-keeper  (sonnet / bajo / pensamiento no)
Responde preguntas sobre código/esquema/endpoints/convenciones leyendo SOLO `documentacion/` (curada y compacta), sin escanear todo el código. Rápido y barato.
Reglas: cita el archivo de `documentacion/`; si no está mapeado lo dice y deriva a doc-updater; no modifica nada.
Cómo "lo sabe": el mapa en `documentacion/` es la fuente de verdad y lo mantiene doc-updater al día.
Invocar: cualquier pregunta sobre el proyecto ya documentado.
Ej.: "¿Qué estados puede tener un pedido?"

## orquestador  (haiku / bajo / pensamiento no)
Dada una consulta, recomienda subagente + modelo + esfuerzo + pensamiento y devuelve la línea `[Ruta]`. No ejecuta la tarea.
Nota: el enrutamiento automático real lo hace el hilo principal vía CLAUDE.md; este agente es para pedir una recomendación explícita.
Invocar: cuando dudás a quién delegar.

---

## Flujo
1. Construir: backend-developer / db-specialist.
2. Vigilar BD: db-schema-guardian antes de toda migración estructural.
3. Verificar: code-verifier, api-contract-checker, consistency-checker, security-reviewer.
4. Documentar: doc-updater. Consultar lo documentado: context-keeper.

*Última actualización: 2026-06-27.*
