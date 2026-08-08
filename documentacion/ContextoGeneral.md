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
Dependencias: **prohibido agregar paquetes npm o Composer nuevos** (los 4 devs: Reyman, Steven, Bryan, Christian). Se resuelve todo con lo ya instalado o con código propio. Regla completa en `CLAUDE.md`.

## Módulos — Cliente (usuario final)
- **Home**: bebidas, pizzas, pastas, grill, descuentos, configuración inicial (acumulación de puntos explícita).
- **Pedir**: Para llevar / Comer en el restaurante; Mis pedidos.
- **Ofertas**: ofertas y cupones.
- **Mi cuenta**: acumulación de puntos, Mi Perfil, Inicio de sesión y seguridad, Mis cupones, Mis pedidos, Métodos de pago, Configuración, Rooster (sección del local — "Rooster" es el nombre del restaurante, no un usuario ni mascota), Ayuda (preguntas frecuentes), Contacto (consultas directas al número oficial), Sobre la App, Notificaciones, Mi restaurante.

## Módulos — Administrador
- Dashboard / Inicio (curación del Home: destacados, preview de cupones)
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
  - **Inicio de sesión con Google (2026-08-07): FUNCIONAL.** OAuth 2.0 *authorization code* del lado del servidor, **sin paquetes nuevos** (nada de Socialite): `GET /auth/google/redirect`, `GET /auth/google/callback` y `POST /auth/google/exchange`, que devuelve el mismo `{data, token}` que `/login`. Única columna nueva: `users.google_id` (aprobada). Es **puerta de clientes**: una cuenta admin se rechaza, porque entrar por ahí saltearía la política de contraseña vencida. El token nunca viaja en la URL (código de un solo uso en el fragmento). App publicada en Google, así que entra cualquier cuenta. Credenciales compartidas por el equipo, fuera del repo: ver `COMO-CORRER.md` §3.2.
- **Módulo 2 — Catálogo de productos (Menú admin + Home cliente): FUNCIONAL.** CRUD completo (Controller-Service-Repository + DTOs + Resources) protegido por rol (`super_admin`/`admin_sede`), con subida de fotos a Cloudinary (cuenta dedicada al proyecto, subida vía backend). Admin: listar/filtrar/crear/editar/eliminar (soft delete) + modal de detalle. Home: consume el mismo catálogo (`GET /productos`, solo `disponible=true`) con modal de detalle y botón "Añadir al carrito" (placeholder, sin lógica todavía). Detalle en `back-document/HiloActualBack.md` y `front-document/HiloActualFront.md`.
- **App cliente (resto): FUNCIONAL en su mayoría.** Home y Carrito (antes "Pedir") conectados a la API real con carrito/checkout/pedidos (ver Módulo 5). Ofertas sigue con datos hardcodeados. **Mi cuenta: FUNCIONAL end-to-end (2026-07-24, ver Módulo 6 abajo).**
  - **Nota de reconciliación (2026-07-18):** el compañero reestructuró en paralelo el Home como "vitrina" (Destacados/Populares/Lo nuevo/Ofertas/Cupones, con el menú completo movido a la tab Carrito) mientras esta sesión construía el carrito/checkout real sobre la estructura anterior de Home+Pedir. Al mergear, se priorizó el carrito real (funcional) sobre la reestructuración visual.
  - **Actualización 2026-07-25:** se eliminó por completo la feature de "oferta destacada del Home" (`home-config`, oferta "hero") — el usuario pidió quitarla del panel admin (Inicio) por no aportar valor; se retiró también el resaltado ★ correspondiente en el Home cliente y se borró el código huérfano (`home-config.service.ts`/`.model.ts`). El endpoint backend `home-config` quedó sin consumidores (no se tocó, a la espera de decidir si se remueve del todo).
- **Panel admin (resto): base visual lista.** Shell con sidebar + módulos en `frotend-integradorIII/src/app/admin/` (incluye `superadmin/` aparte, panel independiente). Menú, Ofertas y cupones (con KPIs clicables + buscador funcional), Inventario, Pedidos, **Dashboard** (FUNCIONAL desde 2026-07-25: KPIs del día, ventas de los últimos 7 días, pedidos nuevos/últimos con datos reales), **Inicio** (curación del Home: destacados/popular/nuevo, preview de cupones — ya sin la "oferta hero", retirada 2026-07-25), **Clientes** (analítica de compra), **Analíticas** (FUNCIONAL desde 2026-07-25, extendido 2026-07-29: filtro por Mes/Semana/Día con selector de calendario —no solo mes—, KPIs, ventas por día, horas pico, top productos, modalidad, comparación vs período anterior equivalente, ventas por categoría con datos reales, más **exportar a Excel/PDF**), y **Usuarios y roles** (CRUD real: crear/editar/eliminar staff, asignación de módulos por usuario, ver nota abajo) ya conectados a la API real. Notificaciones, Reseñas, Configuración (resto) siguen maquetado estático. El header del shell admin (saludo, fecha, sucursal, usuario) también se hizo dinámico 2026-07-25 (antes todo hardcodeado). El atajo temporal `admin`/`123` en el login YA NO EXISTE — el acceso ahora depende del rol real devuelto por el backend (login unificado, redirige a `/admin`, `/superadmin/panel` o `/tabs/home` según `tipo`/`rol`).
- **Módulo Usuarios y roles (admin, 2026-07-23): fix de bug real.** El panel excluye ahora el rol `cliente` de su listado/búsqueda (`UserRepository::listarDeInstancia`/`buscarEnInstancia`) — antes los clientes registrados desde el login público aparecían mezclados con el staff. Además se corrigió un bug de sesión en el frontend: el token se guardaba en un storage compartido entre pestañas (`@ionic/storage-angular`/IndexedDB), así que registrar un cliente en una pestaña podía pisar la sesión del admin abierta en otra; ahora usa `sessionStorage` (aislado por pestaña). Detalle en `back-document/HiloActualBack.md` y `front-document/HiloActualFront.md` (sesión 2026-07-23 en ambos).
- **Módulo 4 — Inventario de insumos: FUNCIONAL.** CRUD de insumos (materia prima: carnes, queso, harina...) + toma física auditada (`insumo_movimientos`), protegido por rol, 100% admin (sin endpoints públicos). Refinado 2026-07-13: unidades de medida personalizadas persistentes (se derivan de datos reales), historial de tomas físicas por insumo (botón condicional si tiene movimientos), buscador funcional, KPIs clicables como filtros, estados con wording claro, validación `stock_minimo ≤ cantidad_actual` en frontend y backend. Detalle en `back-document/HiloActualBack.md` y `front-document/HiloActualFront.md`.
- **Módulo 5 — Pedidos (carrito, checkout, seguimiento, admin): FUNCIONAL.** Cliente: elige sucursal (selector `ion-select`, siempre visible) + modalidad (wording compartido `shared/constants/modalidad.ts`: "Comer en el restaurante" / "Para llevar"), arma carrito (productos con tamaños —con detalle de cantidad, ej. "Grande — 12 slices"— y acompañamientos opcionales con foto, estilo upsell; acompañamientos pueden ser de categoría, generales o asignados puntualmente a un producto, ver Extras abajo), escribe "a nombre de quién" (persistido en `pedidos.nombre_cliente`, puede diferir del nombre de la cuenta), paga en caja y recibe un código de seguimiento; puede consultar SU pedido por código con detalle completo desde el tab Carrito o Mi Cuenta (`GET /pedidos/mios/buscar`, autenticado) además del lookup público minimal ya existente. Admin: gestiona el ciclo de vida completo del pedido con un solo click por cambio de estado (sin modal de confirmación), puede revertir a cualquier estado real del historial (`POST /admin/pedidos/{id}/revertir`, resetea el pago si corresponde), KPIs clicables como filtros, actualización cada 15s, botón visible para expandir cada fila a card de detalle. El wording de los estados es una única fuente compartida (`shared/constants/pedido-estado.ts` en el frontend) para que cliente y admin siempre digan lo mismo. Detalle en `back-document/HiloActualBack.md` y `front-document/HiloActualFront.md`.
- **Extras (acompañamientos): FUNCIONAL, extendido 2026-07-17.** Además de "por categoría" (comportamiento original), una extra puede ser general (`es_general=true`, aplica a todo el catálogo) o asignarse puntualmente a un producto específico fuera de su categoría (tabla `producto_extras`). Gestión desde Menú admin, botón "Extras" junto a "Nuevo producto".
- **Sucursales: CRUD mínimo (crear/editar) desde Configuración admin, 2026-07-17.** Antes solo existía 1 fila sembrada a mano para la instancia 1 — cualquier instancia/tenant nueva quedaba con el selector de sucursal del carrito vacío sin forma de arreglarlo. Ahora cada instancia puede crear/editar sus propias sucursales.
- **Módulo Clientes (admin, 2026-07-18): FUNCIONAL.** Analítica de compra 100% solo lectura. Backend: `Pedido` (modelo compartido con el módulo 5, ver nota de merge en `HiloActualBack.md`), `ClienteRepository`/`ClienteService`/`ClienteController`, `ClienteResumenResource`/`PedidoResumenResource`, endpoints `GET /admin/clientes` (agregación SQL sin N+1) y `GET /admin/clientes/{id}/pedidos` (valida instancia, evita IDOR cross-tenant). Frontend: `ClienteService`, 4 KPIs clicables (incluye "Top comprador"), tabla con búsqueda, modal de historial de pedidos, Top 5 por gasto con Chart.js. Seeder `ClientesDemoSeeder` (opt-in) con clientes + pedidos de prueba.
- **Módulo 6 — Mi cuenta cliente + Roosters + pedido de invitado (2026-07-24): FUNCIONAL.** La tab "Mi cuenta" pasó de maqueta a funcional completa. **Roosters (puntos de fidelidad):** se gana el 5% del total de cada pedido (1 Rooster = ₡1) y se canjean como descuento en el checkout (`pedidos.descuento` + `puntos_movimientos` `ganado`/`canjeado`, `users.puntos_balance`; endpoint `GET /puntos/mios`). **Pedido de invitado:** un visitante sin sesión puede pedir (`POST /pedidos/invitado`, público); la orden se guarda a nombre de un usuario centinela `invitado@rooster.local` (sin volver `cliente_id` nullable), da código y sale marcada `es_invitado` en el admin. **Entrada sin login:** la app abre en `/tabs/home`; sin sesión, Mi cuenta muestra login/registro. **Pantallas nuevas** (rutas hijas de `/tabs/mi-cuenta`): Roosters, Historial de compras (pedidos `pagado=true`, salen de "Mis pedidos"), Mi cuenta (perfil, solo lectura), Restaurantes (sucursales con lat/long → Google Maps; `GET /sucursales` ahora público), Productos (vitrina marquee), Preguntas frecuentes, y prosa (Quiénes somos con link a la web informativa, Términos, Privacidad, Sobre la app). Datos de contacto/devs centralizados en `shared/constants/negocio.ts`. Detalle en `back-document/HiloActualBack.md` y `front-document/HiloActualFront.md` (sesión 2026-07-24). Pendiente del usuario: pasar la info real de desarrolladores para "Sobre la app".
- **Módulo Dashboard (admin, 2026-07-25): FUNCIONAL.** Antes 100% maquetado (arrays hardcodeados). Backend: `GET /admin/dashboard` (`DashboardController`/`DashboardService`/`DashboardRepository`, solo lectura, sin tablas nuevas) devuelve pedidos/ventas de hoy + variación vs ayer, ticket promedio, pedidos activos, ventas de los últimos 7 días reales, pedidos nuevos (pendientes) y últimos pedidos. Frontend: mismo layout visual, solo se reemplazó el binding de datos (`DashboardService`/`DashboardResumen`).
- **Home cliente — tarjetas de ofertas/cupones (2026-07-25): rediseñadas.** Antes texto plano sin ningún estilo. Ahora reutilizan el lenguaje visual tipo "ticket" de la pantalla dedicada Ofertas (círculo de color + ícono, badge de descuento, y para cupones borde punteado + línea de perforación). Se retiró junto con esto la feature de "oferta destacada del Home" (ver nota de reconciliación arriba).
- **Cloudinary**: cuenta gratuita dedicada al proyecto (no mezclada con cuentas personales de ningún dev), subida de imágenes firmada desde el backend (`CloudinaryService`), credenciales solo en `.env` local de cada dev (pedirlas al equipo, no están versionadas).
- **Chart.js**: dependencia nueva agregada 2026-07-18 (primera librería de charting real del proyecto — todo lo anterior es CSS/SVG hecho a mano). Usada solo en `clientes-top-chart.component.ts`. Requiere `animation: false` + `resizeDelay: 200` para evitar animaciones bugeadas por las transiciones de página de Ionic (ver `AntierroresFront.md`).
- **Canje de Ofertas y Cupones por QR (2026-07-29/30): FUNCIONAL.** El cliente muestra un QR desde "Ofertas y cupones" **o desde las tarjetas de vitrina del Home** (`ROOSTER-CUPON:<codigo>` / `OF-<id>` para ofertas — código corto legible, ya no el payload interno); el staff lo escanea desde el admin (`admin/ofertas` → "Canjear código", cámara vía `getUserMedia`+`jsQR` con fallback de texto manual) y arma el pedido real del cliente en una pantalla nueva de mostrador (`admin/pedidos-mostrador`, catálogo con buscador + carrito local con cantidades/tamaños). Cupones: descuento sobre el subtotal completo, `pedidos.cupon_id` YA NO es siempre null. Ofertas: descuento calculado SOLO sobre el subtotal de los productos que pertenecen a esa oferta (rechaza el pedido con 422 si ninguno de los productos agregados corresponde), sin columna nueva en el esquema — la trazabilidad de qué oferta se usó queda anotada automáticamente en `pedidos.notas` (`[Oferta: <nombre>]`); sus productos (sin tamaño) se auto-agregan al carrito del mostrador al canjearla. El catálogo del mostrador resalta visualmente los productos elegibles cuando hay una oferta activa. Dependencias nuevas: `qrcode`/`jsqr` (JS puro, sin wrapper de Angular).
- **Ofertas/Cupones — alcance por cliente específico (2026-08-07): FUNCIONAL.** El admin puede elegir, al crear/editar una oferta o cupón, si es visible para "Todos los clientes" (default) o para "Clientes específicos" seleccionados manualmente desde un picker (reutiliza las estadísticas del módulo Clientes: gasto total, cantidad de pedidos). Esquema nuevo: `ofertas.alcance`/`cupones.alcance` (`todos`/`especifico`) + tablas puente `oferta_cliente`/`cupon_cliente`. El filtrado real ocurre en el listado público (`GET /ofertas`, `GET /cupones`, según el Bearer token si lo hay) y en la validación de cupón del checkout (`PedidoService`). Manual de usuario nuevo: `documentacion/MANUAL-USUARIO.md`, con botón "Ayuda" en el sidebar admin para descargarlo (PDF en `frotend-integradorIII/src/assets/docs/`).
- **Cierre de sesión por inactividad (2026-08-07): FUNCIONAL.** Solo en `admin` y `superadmin` (no en la app cliente). Avisa a los 15 min sin actividad con un modal + cuenta regresiva de 60s; si no hay respuesta, cierra sesión sola. Tope absoluto de 10h de sesión aunque el usuario esté activo. Servicio nuevo `core/services/inactivity.service.ts` + componente standalone `shared/components/idle-session-modal.component.ts`, cada panel con su propia instancia aislada.
- **Identidad de marca del frontend (2026-08-07): completa.** Título de pestaña, favicon (recortado sin el margen blanco del logo original) y un splash de arranque animado con los colores de marca.
- **Estilo de los toasts del sistema (2026-08-08): unificado.** Los ~25 toasts repartidos en 17 archivos pasaron de la barra roja cuadrada de punta a punta (el default de Ionic) a una tarjeta compacta, redondeada y centrada. Definido **una sola vez** en `src/global.scss` vía CSS custom properties + `::part()`; las páginas siguen pasando solo `message`/`duration`/`position`/`color`, no hubo que tocar ninguna llamada. Detalle en `front-document/HiloActualFront.md`.
- **Fix de migración — `ofertas.alcance` (2026-08-08).** La feature de alcance por cliente específico había llegado **solo al dump** `bd-doc/rooster_pizza_bd.sql`, sin `.sql` incremental: quien mantiene su BD por migraciones reventaba con `Undefined column: "alcance"` al crear una oferta. Se escribió el incremental faltante `bd-doc/migracion_2026-08-07_ofertas_cupones_alcance.sql`. **Regla nueva:** todo cambio de esquema va en las dos fuentes (incremental + dump) — ver `AntierroresBack.md` **EB-18**. El dump sigue atrasado (le faltan `notificaciones` y 19 columnas); regenerarlo queda pendiente de decisión del equipo.
- **Fix — portal de acciones del header admin (2026-07-30):** el botón de acciones de una sección (ej. "Canjear código" en Ofertas) desaparecía al volver de otra pantalla, por el cacheo de páginas de `IonicRouteStrategy` (el `ngOnInit` que lo republicaba no vuelve a correr). Corregido escuchando el evento `ionViewWillEnter` de Ionic en `admin/shared/admin-header-actions.directive.ts` — aplica a las 5 páginas que usan ese portal.
- **Exportar Analíticas a Excel/PDF (2026-07-29): FUNCIONAL.** Botones nuevos junto al contador de caché de "Reportes y analíticas" — descargan el resumen del período filtrado (Mes/Semana/Día) en `.xlsx` (6 hojas) o `.pdf`, generado en el backend (`AnaliticasExportService`). Dependencias nuevas: `openspout/openspout` (Excel; **no** `maatwebsite/excel`, incompatible con PHP 8.5 de este entorno, ver `AntierroresBack.md` EB-11) y `barryvdh/laravel-dompdf` (PDF).
- Próximos: descuento automático de insumos al confirmar un pedido, eliminar sucursales (hoy el CRUD es solo crear/editar), guard de rol real en Angular para `/admin`, resto de módulos del admin (notificaciones, reseñas, configuración) vía `api-integration-helper`, decidir si se elimina del todo el endpoint backend `home-config` (huérfano desde 2026-07-25), "Continuar con Google" (fast-follow). Detalle en `back-document/HiloActualBack.md` y `front-document/HiloActualFront.md`.

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

*Última actualización: 2026-07-30.*
