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

## Superadministración y multi-tenant (agregado por el compañero, 2026-07-12/13)
El sistema es multi-tenant: cada negocio independiente es una **instancia**
(`instancias`), con su propio panel Superadmin (`/superadmin/*`, login/guard
propios, aislado del panel admin normal) para crear instancias, gestionar
superadministradores y ver catálogos globales. Cada instancia tiene sus
propias sucursales, usuarios, productos, pedidos, etc. (aislados por
`instancia_id` vía el trait `PerteneceAInstancia`). Detalle de diseño:
`back-document/ARQUITECTURA-SUPERADMIN-MULTITENANT.md`.

## Roles
- super_admin: acceso total, configuración global, todas las sucursales.
- admin_sede: solo su sucursal.
- cliente: sus propios pedidos y el menú público.

## Stack tecnológico
- **Backend**: Laravel (PHP) + PostgreSQL. Patrón Controller-Service-Repository, DTOs, API Resources. Detalle: `back-document/ARQUITECTURA.md`.
- **Frontend**: Ionic (+ Angular). Tres modos: app cliente, panel admin, modo kiosko. Detalle: `front-document/ARQUITECTURA.md`.
- Comunicación: solo por **API REST**. Los repos (`backend-integradorIII`, `frotend-integradorIII`) son independientes; no comparten código.

## Base de datos
- PostgreSQL, 30 tablas: 21/28 originales del ERD + `insumos`/`insumo_movimientos` (Inventario, 2026-07-13) + 6 tablas de superadmin/multi-tenant (`instancias`, `superadministradores`, `modulos`, `usuario_modulo`, `password_reset_tokens`, más `instancia_id` en tablas raíz, 2026-07-12/13) + `producto_tamanos` (Pedidos, 2026-07-16) + `producto_extras` (Extras, 2026-07-17). Convención: tablas plural snake_case, FK `tabla_id`.
- Reglas: sin tabla `direcciones`; horarios en `configuraciones` (clave-valor); precios congelados en el detalle de pedido al momento de la compra.
- Estados de pedido: `pendiente`, `en_proceso`, `listo`, `entregado`, `cancelado` (máquina de estados en `PedidoService`, no se puede saltar pasos). Modalidad: `comer_aqui` / `para_llevar`. Código de seguimiento único por pedido (`pedidos.codigo`), pago en caja registrado en `pedidos.pagado`/`pagado_en` (las tablas `metodos_pago`/`pagos` del ERD original siguen sin usarse, ver nota de alcance más arriba).
- `insumos`/`insumo_movimientos`: inventario de ingredientes/materia prima (NO stock de productos del menú). `insumos.deleted_at` (soft delete, conserva historial en `insumo_movimientos`). Cada "toma física" crea una fila en `insumo_movimientos` y actualiza `insumos.cantidad_actual`.
- `producto_tamanos`: tamaños opcionales por producto (ej. pizzas: Personal/Mediana/Grande), cada uno con su propio precio. `extras` (ya existía, ligada a `categoria_id`) se usa como "acompañamientos" opcionales por categoría.
- `extras.categoria_id` es nullable + `extras.es_general` (boolean): una extra o pertenece a una categoría (aplica a todos sus productos, comportamiento original) o es general (`es_general=true`, aplica a TODO el catálogo) — nunca ambas cosas (CHECK en BD). `producto_extras` (pivote) permite además asignar puntualmente una extra a un producto específico fuera de su categoría. Resolución final de "extras de un producto" = general OR categoría OR pivote, sin duplicados (`ProductoRepository`).
- `sucursales`: CRUD mínimo (crear/editar, sin eliminar) desde Configuración admin — cada instancia/tenant debe crear su propia sucursal, no viene sembrada automáticamente al crear una instancia nueva.
- `productos.popular`/`productos.nuevo` (2026-07-18): flags boolean independientes para secciones del Home, igual que `productos.destacado` (un producto puede estar en varias secciones a la vez).
- Esquema y versiones: `back-document/bd-doc/` (`rooster_pizza_bd.sql` es el dump maestro regenerado por `pg_dump`; migraciones incrementales por fecha en la misma carpeta, incluye `migracion_2026-07-18_home_secciones.sql`).

## Identidad visual (resumen)
- App cliente: NUNCA fondo negro (negro solo para texto/iconos). Paleta cálida de marca: rojo Pantone 185C (~#E8112D), naranja, dorado, tan. Fondos crema/blanco cálido.
- Panel admin: esquema neutral 70-20-10 (fondo gris-blanco, tarjetas blancas, sidebar negro, rojo solo en nav activo, botones primarios y dato pico de gráficos).
- Kiosko: paleta cálida coherente con la app cliente, alta visibilidad.
- Detalle (colores exactos, logos, reglas UX): `front-document/ReglasUX.md` y `front-document/guiaMDFrontend.md`.

## Estado de módulos
- **Módulo 1 — Autenticación (registro + login): FUNCIONAL.** Backend (Laravel + Sanctum) y frontend (Ionic) conectados y probados end-to-end. Cómo levantarlo y probarlo: `COMO-CORRER.md`.
- **Módulo 2 — Catálogo de productos (Menú admin + Home cliente): FUNCIONAL.** CRUD completo (Controller-Service-Repository + DTOs + Resources) protegido por rol (`super_admin`/`admin_sede`), con subida de fotos a Cloudinary (cuenta dedicada al proyecto, subida vía backend). Admin: listar/filtrar/crear/editar/eliminar (soft delete) + modal de detalle. Home: consume el mismo catálogo (`GET /productos`, solo `disponible=true`) con modal de detalle y botón "Añadir al carrito" (placeholder, sin lógica todavía). Detalle en `back-document/HiloActualBack.md` y `front-document/HiloActualFront.md`.
- **App cliente (resto): FUNCIONAL en su mayoría.** Home y Carrito (antes "Pedir") conectados a la API real con carrito/checkout/pedidos (ver Módulo 5). Ofertas sigue con datos hardcodeados. Mi cuenta: "Mis pedidos" y "Buscar mi pedido" ya conectados; el resto de filas siguen sin lógica.
  - **Nota de reconciliación (2026-07-18):** el compañero reestructuró en paralelo el Home como "vitrina" (Destacados/Populares/Lo nuevo/Ofertas/Cupones, con el menú completo movido a la tab Carrito) mientras esta sesión construía el carrito/checkout real sobre la estructura anterior de Home+Pedir. Al mergear, se priorizó el carrito real (funcional) sobre la reestructuración visual — el Home del cliente **todavía no consume** las secciones curadas (`productos.popular`/`nuevo`, oferta "hero" de `home-config`) aunque el admin ya puede configurarlas desde **Inicio**. Portar la curación del Home a la UI real del cliente queda pendiente (ver Próximos).
- **Panel admin (resto): base visual lista.** Shell con sidebar + módulos en `frotend-integradorIII/src/app/admin/` (incluye `superadmin/` aparte, panel independiente). Menú, Ofertas y cupones (con KPIs clicables + buscador funcional), Inventario, Pedidos, **Inicio** (curación del Home: destacados/popular/nuevo, oferta "hero", preview de cupones) y **Clientes** (analítica de compra) ya conectados a la API real. Dashboard, Usuarios y roles, Analíticas, Notificaciones, Reseñas, Configuración (resto) siguen maquetado estático. El atajo temporal `admin`/`123` en el login YA NO EXISTE — el acceso ahora depende del rol real devuelto por el backend (login unificado, redirige a `/admin`, `/superadmin/panel` o `/tabs/home` según `tipo`/`rol`).
- **Módulo 4 — Inventario de insumos: FUNCIONAL.** CRUD de insumos (materia prima: carnes, queso, harina...) + toma física auditada (`insumo_movimientos`), protegido por rol, 100% admin (sin endpoints públicos). Refinado 2026-07-13: unidades de medida personalizadas persistentes (se derivan de datos reales), historial de tomas físicas por insumo (botón condicional si tiene movimientos), buscador funcional, KPIs clicables como filtros, estados con wording claro, validación `stock_minimo ≤ cantidad_actual` en frontend y backend. Detalle en `back-document/HiloActualBack.md` y `front-document/HiloActualFront.md`.
- **Módulo 5 — Pedidos (carrito, checkout, seguimiento, admin): FUNCIONAL.** Cliente: elige sucursal (selector `ion-select`, siempre visible) + modalidad, arma carrito (productos con tamaños/acompañamientos opcionales — acompañamientos ahora pueden ser de categoría, generales o asignados puntualmente a un producto, ver Extras abajo), paga en caja y recibe un código de seguimiento; puede consultar el estado por código sin necesidad de entrar a "Mis pedidos". Admin: gestiona el ciclo de vida completo del pedido (máquina de estados, historial, registro de pago tras la entrega), KPIs clicables como filtros, actualización cada 15s, botón visible para expandir cada fila a card de detalle. El wording de los estados es una única fuente compartida (`shared/constants/pedido-estado.ts` en el frontend) para que cliente y admin siempre digan lo mismo. Detalle en `back-document/HiloActualBack.md` y `front-document/HiloActualFront.md`.
- **Extras (acompañamientos): FUNCIONAL, extendido 2026-07-17.** Además de "por categoría" (comportamiento original), una extra puede ser general (`es_general=true`, aplica a todo el catálogo) o asignarse puntualmente a un producto específico fuera de su categoría (tabla `producto_extras`). Gestión desde Menú admin, botón "Extras" junto a "Nuevo producto".
- **Sucursales: CRUD mínimo (crear/editar) desde Configuración admin, 2026-07-17.** Antes solo existía 1 fila sembrada a mano para la instancia 1 — cualquier instancia/tenant nueva quedaba con el selector de sucursal del carrito vacío sin forma de arreglarlo. Ahora cada instancia puede crear/editar sus propias sucursales.
- **Módulo Clientes (admin, 2026-07-18): FUNCIONAL.** Analítica de compra 100% solo lectura. Backend: `Pedido` (modelo compartido con el módulo 5, ver nota de merge en `HiloActualBack.md`), `ClienteRepository`/`ClienteService`/`ClienteController`, `ClienteResumenResource`/`PedidoResumenResource`, endpoints `GET /admin/clientes` (agregación SQL sin N+1) y `GET /admin/clientes/{id}/pedidos` (valida instancia, evita IDOR cross-tenant). Frontend: `ClienteService`, 4 KPIs clicables (incluye "Top comprador"), tabla con búsqueda, modal de historial de pedidos, Top 5 por gasto con Chart.js. Seeder `ClientesDemoSeeder` (opt-in) con clientes + pedidos de prueba.
- **Cloudinary**: cuenta gratuita dedicada al proyecto (no mezclada con cuentas personales de ningún dev), subida de imágenes firmada desde el backend (`CloudinaryService`), credenciales solo en `.env` local de cada dev (pedirlas al equipo, no están versionadas).
- **Chart.js**: dependencia nueva agregada 2026-07-18 (primera librería de charting real del proyecto — todo lo anterior es CSS/SVG hecho a mano). Usada solo en `clientes-top-chart.component.ts`. Requiere `animation: false` + `resizeDelay: 200` para evitar animaciones bugeadas por las transiciones de página de Ionic (ver `AntierroresFront.md`).
- Próximos: portar la curación del Home (Destacados/Populares/Nuevo/oferta hero) a la UI real del cliente (hoy solo el admin la configura, el Home del cliente sigue mostrando el catálogo completo con carrito), integración de cupones en el checkout, descuento automático de insumos al confirmar un pedido, eliminar sucursales (hoy el CRUD es solo crear/editar), guard de rol real en Angular para `/admin`, resto de módulos del admin (usuarios, analíticas, etc.) vía `api-integration-helper`, "Continuar con Google" (fast-follow). Detalle en `back-document/HiloActualBack.md` y `front-document/HiloActualFront.md`.

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
