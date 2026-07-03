# Contexto General — Rooster Pizza & Grill

Mapa base del proyecto. Fuente de verdad para `context-keeper` y referencia rápida para todos los subagentes. Compacto a propósito: el detalle vive en los archivos enlazados.

## Identidad del producto
- Cliente: Rooster Pizza & Grill, una food truck en La Fortuna, San Carlos, Costa Rica.
- Vende: pizzas, grill, pastas y bebidas. (Solo eso; no es restaurante de menú amplio.)
- Núcleo de la app: el cliente hace pedidos para **Comer en el restaurante** o **Para llevar**.
- Sin delivery (no hay envíos ni direcciones).
- Programa de **acumulación de puntos** (fidelidad) como feature explícita.
- Proyecto Integrador III, UTN Guanacaste.

## Plataformas
- Aplicación **híbrida**: móvil + web desde una sola base.
- Móvil prioritario, con foco en **Android**; iOS tentativo.
- También disponible como app web (navegadores).

## Alcance
Incluye: pedidos comer aquí / para llevar, catálogo, ofertas y cupones, puntos, cuentas de cliente, panel admin, reseñas, reportes.
Excluye (no agregar sin instrucción explícita del usuario): delivery, menús distintos por sucursal, push marketing automatizado, pagos online.
Pago: único método = **pagar en caja** (no hay pagos online ni pasarela). Las tablas `metodos_pago`/`pagos` del esquema quedan para escalabilidad; no se usan en esta versión.
Sucursales: **una sola** en esta versión, pero el diseño debe ser **escalable a múltiples sucursales** a futuro (horarios, datos y disponibilidad por local).

## Módulos — Cliente (usuario final)
- **Home**: bebidas, pizzas, pastas, grill, descuentos, configuración inicial (acumulación de puntos explícita).
- **Pedir**: Para llevar / Comer en el restaurante; Mis pedidos.
- **Ofertas**: ofertas y cupones.
- **Mi cuenta**: acumulación de puntos, Mi Perfil, Inicio de sesión y seguridad, Mis cupones, Mis pedidos, Métodos de pago, Configuración, Rooster (sección del local — "Rooster" es el nombre del restaurante, no un usuario ni mascota), Ayuda (preguntas frecuentes), Contacto (consultas directas al número oficial), Sobre la App, Notificaciones, Mi restaurante.

## Módulos — Administrador
- Dashboard / Inicio
- Gestión de pedidos en tiempo real
- Gestión de menú / catálogo
- Ofertas y cupones
- Clientes
- Usuarios y roles
- Reportes y analíticas
- Notificaciones / marketing
- Reseñas y calificaciones (moderar y responder)
- Sucursales (una hoy; arquitectura escalable a varias: horarios, datos y disponibilidad por local)
- Configuración general

## Roles
- super_admin: acceso total, configuración global, todas las sucursales.
- admin_sede: solo su sucursal.
- cliente: sus propios pedidos y el menú público.

## Stack tecnológico
- **Backend**: Laravel (PHP) + PostgreSQL. Patrón Controller-Service-Repository, DTOs, API Resources. Detalle: `back-document/ARQUITECTURA.md`.
- **Frontend**: Ionic (+ Angular). Tres modos: app cliente, panel admin, modo kiosko. Detalle: `front-document/ARQUITECTURA.md`.
- Comunicación: solo por **API REST**. Los repos (`backend-integradorIII`, `frotend-integradorIII`) son independientes; no comparten código.

## Base de datos
- PostgreSQL, 21 tablas, 28 FK (todas 1-M). Convención: tablas plural snake_case, FK `tabla_id`.
- Reglas: sin tabla `direcciones`; horarios en `configuraciones` (clave-valor); precios congelados en el detalle de pedido al momento de la compra.
- Estados de pedido: `pendiente`, `en_proceso`, `listo`, `entregado`, `cancelado`. Modalidad: comer aquí / para llevar.
- Esquema y versiones: `back-document/bd-doc/` (incluye `rooster_pizza_bd.sql`).

## Identidad visual (resumen)
- App cliente: NUNCA fondo negro (negro solo para texto/iconos). Paleta cálida de marca: rojo Pantone 185C (~#E8112D), naranja, dorado, tan. Fondos crema/blanco cálido.
- Panel admin: esquema neutral 70-20-10 (fondo gris-blanco, tarjetas blancas, sidebar negro, rojo solo en nav activo, botones primarios y dato pico de gráficos).
- Kiosko: paleta cálida coherente con la app cliente, alta visibilidad.
- Detalle (colores exactos, logos, reglas UX): `front-document/ReglasUX.md` y `front-document/guiaMDFrontend.md`.

## Estado de módulos
- **Módulo 1 — Autenticación (registro + login): FUNCIONAL.** Backend (Laravel + Sanctum) y frontend (Ionic) conectados y probados end-to-end. Cómo levantarlo y probarlo: `COMO-CORRER.md`.
- **App cliente (base visual): lista.** Home, Pedir, Ofertas, Mi cuenta — maquetado fiel al prototipo, hardcodeado (sin conectar a API todavía).
- **Panel admin (base visual): lista.** Shell con sidebar + 9 módulos (Dashboard, Pedidos, Menú, Ofertas y cupones, Usuarios y roles, Analíticas, Notificaciones, Reseñas, Configuración) en `frotend-integradorIII/src/app/admin/`, maquetado fiel al prototipo, hardcodeado. Acceso temporal desde el login: usuario `admin` / contraseña `123` (sin guard de rol real todavía).
- Próximos: conectar datos reales de ambos lados vía `api-integration-helper`, guard de rol real para `/admin`, "Continuar con Google" (fast-follow), "Olvidé mi contraseña". Detalle en `back-document/HiloActualBack.md` y `front-document/HiloActualFront.md`.

## Propósito de esta documentación
Tener referencia documentada (paleta, logos, reglas, base de datos, decisiones) para que los subagentes respondan sin escanear todo el código, ahorrando tokens y trabajando optimizado. Mantener al día vía `doc-updater`.

## Mapa de la documentación
- `ContextoGeneral.md` — este archivo (visión general).
- `COMO-CORRER.md` — pasos para levantar BD + backend + frontend y probar el módulo auth.
- `CLAUDE.md` — protocolo de enrutamiento y matriz de subagentes.
- `Subgantes-Doc.md` — qué hace cada subagente.
- `EXPLICACION.md` — explicación de carpetas y archivos.
- `back-document/` — ARQUITECTURA, AntierroresBack, HiloActualBack, `bd-doc/`.
- `front-document/` — ARQUITECTURA, ReglasUX, guiaMDFrontend, AntierroresFront, HiloActualFront.

*Última actualización: 2026-07-03.*
