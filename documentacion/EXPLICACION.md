# EXPLICACIÓN — Carpetas y archivos de la documentación

Qué es cada carpeta y archivo, su ruta exacta y para qué sirve. Para que cualquier dev lo entienda rápido, sin leer código.

Raíz: `c:\xampp\htdocs\Back_Integradorlll\backend-integradorIII\documentacion`

## Regla base
TODA la documentación del proyecto (backend y frontend) vive **solo aquí**, en el proyecto backend. **No se duplica** en el proyecto frontend: actualizar en dos lugares es tedioso e impreciso. Si terminás algo en el front, venís a esta carpeta a actualizar los archivos que correspondan.

---

## Carpeta `documentacion/`
Contenedor de toda la documentación del proyecto.

- **`documentacion/CLAUDE.md`** — Matriz de decisión y reglas de enrutamiento de subagentes: a qué subagente (y con qué modelo/esfuerzo/pensamiento) se dirige cada tipo de tarea. Es lo primero que rige cómo se trabaja.
- **`documentacion/ContextoGeneral.md`** — Razón de ser e identidad del proyecto: qué es, qué vende, módulos, alcance, stack. Da el contexto para programar con precisión.
- **`documentacion/Subgantes-Doc.md`** — Explicación breve y puntual de cómo funciona cada subagente.
- **`documentacion/EXPLICACION.md`** — Este archivo: explica cada carpeta y archivo de la documentación.

---

## Carpeta `documentacion/back-document/`
Toda la documentación del **backend**.

- **`back-document/ARQUITECTURA.md`** — Arquitectura del backend: tecnologías y estructura de carpetas/capas que se usan.
- **`back-document/AntierroresBack.md`** — Catálogo de errores del backend. Cada vez que se corrige un error, se documenta aquí para que en la próxima sesión NO se repita.
- **`back-document/HiloActualBack.md`** — Estado/hilo actual del backend: qué se está haciendo y qué NO tocar (porque otro dev lo tiene). Se actualiza al cerrar cada sesión; también sirve para saber el estado sin escanear todo el código.

### Carpeta `documentacion/back-document/bd-doc/`
Todo lo referente a la base de datos (para no tener que ir a la BD ni exportarla).

- **`bd-doc/rooster_pizza_bd.sql`** — Script DDL de la base PostgreSQL (21 tablas, 28 FK). Aquí también van futuras migraciones y nuevas versiones del esquema.

---

## Carpeta `documentacion/front-document/`
Toda la documentación del **frontend** (misma dinámica que `back-document/`).

- **`front-document/ARQUITECTURA.md`** — Arquitectura del frontend: tecnologías y estructura de carpetas, incluyendo la ruta del proyecto front (`c:\xampp\htdocs\Front_Integradorlll\frotend-integradorIII`).
- **`front-document/guiaMDFrontend.md`** — Guía detallada de cómo se debe programar el frontend.
- **`front-document/ReglasUX.md`** — Reglas de experiencia de usuario que NO se deben romper. Reglas básicas e importantes para no andar corrigiendo de más.
- **`front-document/AntierroresFront.md`** — Catálogo de errores del frontend (misma dinámica que `AntierroresBack.md`).
- **`front-document/HiloActualFront.md`** — Estado/hilo actual del frontend (misma dinámica que `HiloActualBack.md`).

*Última actualización: 2026-06-28.*
