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
- Dashboard / Inicio (curación del Home: destacados, oferta hero, preview de cupones)
- Gestión de pedidos en tiempo real
- Gestión de menú / catálogo
- Inventario (insumos / materia prima)
- Ofertas y cupones
- Clientes (analítica de compra: gasto total, pedidos, ticket promedio, ranking top 5)
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
- PostgreSQL, 23 tablas, 29 FK (todas 1-M): 21/28 originales del ERD + `insumos`/`insumo_movimientos` (módulo Inventario, 2026-07-13). Convención: tablas plural snake_case, FK `tabla_id`.
- Reglas: sin tabla `direcciones`; horarios en `configuraciones` (clave-valor); precios congelados en el detalle de pedido al momento de la compra.
- Estados de pedido: `pendiente`, `en_proceso`, `listo`, `entregado`, `cancelado`. Modalidad: comer aquí / para llevar.
- `insumos`/`insumo_movimientos`: inventario de ingredientes/materia prima (NO stock de productos del menú). `insumos.deleted_at` (soft delete, conserva historial en `insumo_movimientos`). Cada "toma física" crea una fila en `insumo_movimientos` y actualiza `insumos.cantidad_actual`.
- `productos.popular`/`productos.nuevo` (2026-07-18): flags boolean independientes para secciones del Home, igual que `productos.destacado` (un producto puede estar en varias secciones a la vez).
- Esquema y versiones: `back-document/bd-doc/` (incluye `rooster_pizza_bd.sql`, `migracion_2026-07-13_insumos.sql`, `migracion_2026-07-18_home_secciones.sql`).

## Identidad visual (resumen)
- App cliente: NUNCA fondo negro (negro solo para texto/iconos). Paleta cálida de marca: rojo Pantone 185C (~#E8112D), naranja, dorado, tan. Fondos crema/blanco cálido.
- Panel admin: esquema neutral 70-20-10 (fondo gris-blanco, tarjetas blancas, sidebar negro, rojo solo en nav activo, botones primarios y dato pico de gráficos).
- Kiosko: paleta cálida coherente con la app cliente, alta visibilidad.
- Detalle (colores exactos, logos, reglas UX): `front-document/ReglasUX.md` y `front-document/guiaMDFrontend.md`.

## Estado de módulos
- **Módulo 1 — Autenticación (registro + login): FUNCIONAL.** Backend (Laravel + Sanctum) y frontend (Ionic) conectados y probados end-to-end. Cómo levantarlo y probarlo: `COMO-CORRER.md`.
- **Módulo 2 — Catálogo de productos (Menú admin + Home cliente): FUNCIONAL.** CRUD completo (Controller-Service-Repository + DTOs + Resources) protegido por rol (`super_admin`/`admin_sede`), con subida de fotos a Cloudinary (cuenta dedicada al proyecto, subida vía backend). Admin: listar/filtrar/crear/editar/eliminar (soft delete) + modal de detalle. Home: consume el mismo catálogo (`GET /productos`, solo `disponible=true`) con modal de detalle y botón "Añadir al carrito" (placeholder, sin lógica todavía). Detalle en `back-document/HiloActualBack.md` y `front-document/HiloActualFront.md`.
- **App cliente: Home rediseñado como vitrina (2026-07-17, ampliado 2026-07-18).** Home muestra Destacados (`productos.destacado`), Populares (`productos.popular`), Lo nuevo (`productos.nuevo`), Ofertas y Cupones vigentes — ya no es el menú completo. El menú completo (categorías, buscador, filtro) ahora vive en la tab **Carrito** (antes placeholder hardcodeado). Ofertas (tab), Mi cuenta — siguen maquetado fiel al prototipo, hardcodeado. Carrito real (agregar/editar/ver) todavía NO existe — el botón "Añadir al carrito" es placeholder a propósito, queda para otro dev.
- **Panel admin (funcionales hoy):** Shell con sidebar + 12 módulos en `frotend-integradorIII/src/app/admin/`. Menú, Ofertas y cupones (con KPIs clicables + buscador funcional, 2026-07-18), Inventario, **Inicio** (curación del Home: destacados/popular/nuevo, oferta "hero", preview de cupones) y **Clientes** (nuevo, 2026-07-18: analítica de compra solo lectura, gasto total/ticket promedio/cantidad pedidos/último pedido, historial de pedidos por cliente, Top 5 por gasto con Chart.js) ya conectados a la API real. Dashboard, Pedidos, Usuarios y roles, Analíticas, Notificaciones, Reseñas, Configuración siguen maquetado estático. El atajo temporal `admin`/`123` en el login YA NO EXISTE — el acceso a `/admin` ahora depende del rol real devuelto por el backend (aunque la ruta en sí sigue sin guard de Angular).
- **Módulo 4 — Inventario de insumos: FUNCIONAL.** CRUD de insumos (materia prima: carnes, queso, harina...) + toma física auditada (`insumo_movimientos`), protegido por rol, 100% admin (sin endpoints públicos). Refinado 2026-07-13: unidades de medida personalizadas persistentes (se derivan de datos reales), historial de tomas físicas por insumo (botón condicional si tiene movimientos), buscador funcional, KPIs clicables como filtros, estados con wording claro, validación `stock_minimo ≤ cantidad_actual` en frontend y backend. Detalle en `back-document/HiloActualBack.md` y `front-document/HiloActualFront.md`.
- **Módulo Clientes (admin, nuevo 2026-07-18): FUNCIONAL.** Analítica de compra 100% solo lectura. Backend: `Pedido` (modelo nuevo — antes no existía ningún modelo de pedidos), `ClienteRepository`/`ClienteService`/`ClienteController`, `ClienteResumenResource`/`PedidoResumenResource`, endpoints `GET /admin/clientes` (con agregación SQL sin N+1) y `GET /admin/clientes/{id}/pedidos` (valida instancia antes de devolver, evita IDOR cross-tenant). Frontend: `ClienteService`, 4 KPIs clicables (incluye filtro especial "Top comprador" que muestra únicamente ese cliente), tabla con búsqueda, modal de historial de pedidos, Top 5 por gasto con Chart.js (barra horizontal, solo el pico coloreado con rojo de marca). Seeder `ClientesDemoSeeder` (opt-in) con sucursal + 15 clientes + ~100 pedidos de prueba, ya ejecutado contra BD local.
- **Cloudinary**: cuenta gratuita dedicada al proyecto (no mezclada con cuentas personales de ningún dev), subida de imágenes firmada desde el backend (`CloudinaryService`), credenciales solo en `.env` local de cada dev (pedirlas al equipo, no están versionadas).
- **Chart.js**: dependencia nueva agregada 2026-07-18 (primera librería de charting real del proyecto — todo lo anterior es CSS/SVG hecho a mano). Usada solo en `clientes-top-chart.component.ts`. Requiere `animation: false` + `resizeDelay: 200` para evitar animaciones bugeadas por las transiciones de página de Ionic (ver `AntierroresFront.md` EF-04).
- Próximos: conectar Carrito/Pedir real (el botón "Añadir al carrito" del Home ya está maquetado pero sin lógica), guard de rol real en Angular para `/admin`, resto de módulos del admin (pedidos/usuarios/analíticas/etc.), "Continuar con Google" (fast-follow), "Olvidé mi contraseña". Detalle en `back-document/HiloActualBack.md` y `front-document/HiloActualFront.md`.

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

*Última actualización: 2026-07-18.*
