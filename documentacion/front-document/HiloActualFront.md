# Hilo Actual — Frontend

Estado actual del frontend. Se actualiza al cerrar cada sesión, para que el siguiente dev sepa qué hacer y qué NO tocar (porque otro ya lo está trabajando y aún no hizo push). También sirve para conocer el estado sin escanear todo el código.

Cómo se llena: al terminar una sesión, anotá qué se hizo, qué quedó pendiente y qué está reservado.

Formato sugerido:
```
## Sesión YYYY-MM-DD — <dev>
- Hecho: <qué se hizo / ajustó / borró>
- En progreso / NO tocar: <archivos o módulos que otro dev tiene>
- Pendiente: <qué sigue>
```

## Sesión 2026-06-28 — Módulo 1: Auth (frontend)
- Hecho:
  - `npm install` del scaffold Ionic+Angular. Logo en `src/assets/logo/rooster-logo.png`.
  - Tema de marca (`theme/variables.scss`) + fuentes Playfair/Nunito (`index.html`) + estilos auth en `global.scss`.
  - Core (`src/app/core/`): `environment.apiBaseUrl`, `models/usuario`, `TokenStorageService` (@ionic/storage), `AuthService` (token en memoria + storage), `AuthInterceptor` (Bearer/401), guards (`authGuard`/`guestGuard`), `APP_INITIALIZER`.
  - Pantallas `login` y `register` (`src/app/auth/`) con diseño idéntico al mockup, ReactiveForms, password fuerte client-side, conectadas a la API.
  - Rutas: `''` → login; `/login`, `/register` (guestGuard); post-auth → `/tabs/tab1` (placeholder).
  - Build dev OK (compila sin errores).
- Pendiente:
  - Click-through real en navegador (correr `ng serve` + backend) para validación visual final.
  - Reemplazar el placeholder `/tabs/tab1` por el Home real del cliente.
  - "Olvidé mi contraseña" y Google.
- NO TOCAR / nota: el destino post-login `/tabs/tab1` es temporal.

## Sesión 2026-06-29 — Responsive de auth + logout temporal
- Hecho:
  - Tarjeta blanca (card) del mockup agregada a login/register (faltaba). Ver AntierroresFront EF-01.
  - Centrado robusto: wrapper light-DOM `.auth-center` (`flex:1` + `min-height:100%`) + `.auth-wrap { margin:auto }`; no depende del `::part(scroll)`.
  - Responsive por ALTURA: media queries `max-height: 780px` y `640px` compactan logo/márgenes/inputs para que el card entre y centre en cualquier viewport (clave con zoom/escala alta). Logo login 120px, register 72px.
  - Botón temporal "Cerrar sesión" + saludo (nombre/email) en `tab1` (`tab1.page.ts/html`), llama `AuthService.logout()` y vuelve a `/login`.
  - Verificado en navegador: registro → BD → login → logout.
- Pendiente:
  - Reemplazar placeholder `/tabs/tab1` por el Home real del cliente.
  - "Olvidé mi contraseña" y "Continuar con Google" (fast-follow).
  - `environment.prod.ts` necesita `apiBaseUrl` (solo afecta build de prod).
- NO TOCAR / nota: el destino post-login `/tabs/tab1` y el botón de logout en tab1 son temporales. Cómo correr todo: `documentacion/COMO-CORRER.md`.

## Sesión 2026-07-02 — Rediseño Home, Pedir, Ofertas y Mi cuenta (según prototipo)
- Hecho:
  - Reemplazado el scaffold de tabs (`tab1`..`tab4`) por los 4 módulos reales del cliente: `src/app/home/`, `src/app/pedir/`, `src/app/ofertas/`, `src/app/mi-cuenta/`.
  - Rediseño visual de esos 4 `.page.html` + `.page.scss` para que calcen con el prototipo React exportado de Figma (`prototipoFrontend-main/src/app/App.tsx` + `src/pages/Home.tsx`, `Cupones.tsx`, `Account.tsx`).
  - **Home**: bienvenida, categorías circulares con foto, grid de tarjetas de platillos populares.
  - **Pedir**: no existe 1:1 en el prototipo (Home.tsx lo combina todo) — se armó como vista de menú completo con buscador + tabs de categoría, con los 9 platillos del prototipo.
  - **Ofertas**: tabs Ofertas/Cupones (réplica de `CuponesPage`); solo queda visible el tab "Ofertas" activo por ahora (la página es estática, sin lógica de tabs todavía) — los estilos de "Cupones" ya están en el SCSS listos para cuando se conecte esa lógica.
  - **Mi cuenta**: perfil + secciones agrupadas con filas navegables + cerrar sesión (réplica de `AccountPage`), con el SVG de flecha del prototipo en vez de `ion-icon`.
  - Precios pasados a dólares (como en el prototipo) en vez de colones — pendiente confirmar con backend/negocio si así debe quedar.
  - Ajustes en `tabs.page.scss` para el nuevo look de la barra inferior.
  - Todo hardcodeado en el HTML (sin `*ngFor`/`*ngIf`, sin binding), tal como estaba el resto del scaffold — son solo estáticas por ahora.
  - Verificado con `ionic serve` (compila sin errores, los 4 módulos cargan).
- Pendiente:
  - Conectar datos reales vía `api-integration-helper` (categorías, platillos, cupones/ofertas, perfil de usuario) — hoy todo es hardcode del prototipo.
  - Lógica de tabs Ofertas/Cupones en `ofertas.page.ts` (hoy solo hay CSS preparado, no hay cambio de vista).
  - Buscador y filtro por categoría en `pedir.page.ts` (hoy es solo maquetado, sin filtrado real).
  - Confirmar si los precios deben ir en colones o dólares.
- NO TOCAR / nota: las 4 páginas nuevas son solo maquetado visual — cualquiera que le agregue lógica primero debe revisar que no rompa el diseño. No se tocó ningún `.ts`/`.module.ts` en esta sesión.

## Sesión 2026-07-03 — Base panel admin
- Hecho:
  - Portada la BASE VISUAL del panel de administrador desde el prototipo React/Tailwind (`prototipoFrontend-main/src/app/App.tsx`, sección admin) a Angular+Ionic+SCSS. Solo maquetado estático, fiel al prototipo, SIN lógica de negocio ni conexión a API (eso viene con `api-integration-helper`).
  - **Estructura nueva** `src/app/admin/`:
    - `admin-shell/` — layout persistente (sidebar colapsable + header + `<ion-router-outlet>`). Sidebar fija de 220px en desktop; en móvil se oculta y se abre como overlay con backdrop (toggle `sidebarOpen`, breakpoint 1024px). Registra las rutas hijas y redirect `'' → dashboard`. Botón "Salir al app" navega a `/login` (replaceUrl, sin tocar token).
    - `shared/` — `AdminSharedModule` con 14 componentes de UI reutilizables (`@Input`): `admin-kpi-card`, `admin-section-card`, `admin-page-header`, `admin-btn`, `filter-tab`, `status-badge`, `modality-pill`, `admin-search-input`, `dropdown-btn`, `progress-bar`, `donut-chart`, `mini-bar`, `bar-chart`, `area-chart`. Los charts son SVG/CSS calculados en TS (paths, conic-gradient) con los datos estáticos del prototipo.
    - 9 páginas (una carpeta c/u, `standalone: false`, lazy-loaded): `dashboard`, `pedidos`, `menu`, `ofertas`, `usuarios`, `analiticas`, `notificaciones`, `resenas`, `configuracion`.
  - **Modales** (toggle visual mostrar/ocultar, sin lógica real): detalle de pedido en `pedidos`, crear usuario en `usuarios` (con selector de rol que muestra/oculta "Sucursal").
  - **Tokens de tema**: agregadas variables `--admin-*` (panel/card/border/text/text-muted/text-soft/sidebar/accent/green/amber/red-alt + neutrals) en `theme/variables.scss`. Esquema neutral 70-20-10 con rojo de marca como acento.
  - **Estilos compartidos** en `global.scss`: `.admin-page`/`.admin-page-content` (wrapper con scroll), grids `.admin-grid-4`/`.admin-grid-3`, `.admin-table*` y `.admin-pagination*`.
  - **Ruteo**: nueva ruta top-level `path: 'admin'` en `app-routing.module.ts` (lazy → `admin-shell`). Acceso temporal: en el login normal, ingresar usuario `admin` / contraseña `123` navega a `/admin` en vez de llamar al backend (chequeo al inicio de `login()` en `login.page.ts`, antes de la validación del formulario — igual que el mock del prototipo). Sin botón ni link visible.
  - Datos hardcodeados idénticos al prototipo (mismos textos/números, montos en ₡). Se usó `*ngFor` sobre arrays locales de cada `.page.ts` para las tablas/listas (más limpio que duplicar HTML; sigue siendo 100% estático, sin binding de API).
  - Iconos: `ion-icon` (mapeo lucide→ionicons), sin agregar `lucide-react`. Fuentes Nunito/Playfair ya cargadas globalmente.
  - Verificado con `ng build --configuration development`: compila sin errores (strictTemplates + strict TS OK). El único warning es el preexistente de `localforage`.
- Pendiente:
  - Conectar datos reales vía `api-integration-helper` (pedidos, menú, cupones/ofertas, usuarios, reseñas, KPIs, notificaciones) — hoy todo es hardcode del prototipo.
  - Guard de rol real para `/admin` (cuando se conecte `role_id` del backend). Hoy el acceso es libre/temporal.
  - Lógica real de: filtros de tablas (los tabs solo cambian el resaltado, no filtran filas), toggles de disponibilidad/config, paginación, "marcar como leídas", buscadores, y acciones de los modales (guardar/crear).
  - Revisar/coordinar con `ui-verifier` la fidelidad de colores/layout si se requiere validación visual formal.
- NO TOCAR / nota:
  - La ruta `/admin` y el atajo `admin`/`123` en el login son TEMPORALES (placeholder hasta tener guard de rol real con `role_id`).
  - Todo `src/app/admin/` es solo maquetado visual — quien agregue lógica debe cuidar no romper el diseño.
  - De la app cliente solo se tocó `login.page.ts` (atajo admin/123, sin UI extra). No se modificó home/pedir/ofertas/mi-cuenta/register.
  - Desviación menor vs. prototipo: los modales se centran sobre el área de contenido (no cubren sidebar/header) por el `contain` del `.ion-page` de Ionic; funcionalmente equivalente. El componente `mini-bar` se portó por completitud pero ninguna página lo usa aún.

## Sesión 2026-07-03 (cont.) — Antierror: ionic serve no detecta archivos nuevos
- Qué pasó: tras crear ~56 archivos nuevos bajo `src/app/admin/` con el dev server (`ionic serve`) ya corriendo, la recompilación incremental tiraba `TS2307: Cannot find module` para todos los módulos nuevos, indefinidamente.
- Causa: el watcher incremental de `ngtools/webpack` no re-escanea el `include` de `tsconfig` cuando aparecen muchos archivos nuevos de golpe mientras el server ya está arriba.
- Regla: si un subagente/tarea crea módulos/archivos nuevos con `ionic serve` corriendo, matar el proceso y levantarlo en frío (no alcanza con esperar el rebuild incremental). Ver `AntierroresFront.md` EF-02.

## Sesión 2026-07-10 — Menú/Catálogo conectado a la API + detalle + Cloudinary
- Hecho:
  - **Admin → Menú/Catálogo** (`src/app/admin/menu/`) conectado a la API real: reemplazado el array estático por `ProductoService`/`CategoriaService` (`core/services/`, `core/models/producto.model.ts`). Listado, filtro por categoría (ahora sí funcional, antes solo cambiaba el resaltado), crear/editar con formulario reactivo (incluye subida de foto con preview, `FormData`), eliminar (soft delete, confirmado que persiste en BD).
  - **Home del cliente** (`src/app/home/`): categorías y "Platillos populares" ya no son hardcode — vienen de `GET /productos` (solo `disponible=true`) y `GET /categorias`. Estados de carga/error. Precio pasado de `$` (dólares, resabio del prototipo) a `₡` real.
  - **Pipe compartido `crcCurrency`** (`src/app/shared/pipes/crc-currency.pipe.ts`, standalone, `Intl.NumberFormat('es-CR', {currency:'CRC'})`) — usado en admin y home, único punto de formato de moneda.
  - **Modal de detalle de producto**: en admin (click en fila de la tabla → foto/categoría/descripción/precio/estado, con atajo a "Editar"), y en home (click en tarjeta → mismo detalle + botón "Añadir al carrito", que hoy solo muestra un toast placeholder porque el módulo de Carrito real no existe todavía).
  - **Fix de login**: `login.page.ts` ya no tiene el atajo `admin`/`123` (bypaseaba el backend por completo, sin token real — causaba que cualquier llamada protegida del admin devolviera 401 y el interceptor global expulsara al usuario a `/login`, sensación de "me echa"). Ahora el login real redirige a `/admin` o `/tabs/home` según el `rol` que devuelve el backend. Ver `AntierroresFront.md` EF-03.
  - **Tab "Pedir" → "Carrito"**: label e ícono (`cart-outline`) corregidos en `tabs.page.html` para calzar con el prototipo de Figma (antes decía "Pedir" con ícono de restaurante).
- Pendiente:
  - Conectar el botón "Añadir al carrito" del modal de detalle cuando exista el módulo de Carrito real (hoy la tab dice "Carrito" pero sigue cargando la página `pedir/` sin lógica de carrito).
  - Guard de rol real para `/admin` (sigue sin guard — cualquiera que navegue directo a la URL entra al shell visualmente, aunque las llamadas a la API van a fallar con 401/403 si no hay sesión de admin real).
  - Labels de tabs pendientes de alinear con `ContextoGeneral.md` (Home→"Inicio", Ofertas→"Cupones") — no se tocaron porque no se pidió explícitamente.
- NO TOCAR / nota: `src/app/tabs/pedir/` sigue siendo la página que carga bajo la tab "Carrito" (no se renombró el módulo/ruta, solo el label+ícono del tab bar) — cuando se construya el Carrito real, decidir si se reusa esa carpeta o se crea una nueva.

### EF-03 — Atajo admin/123 causaba expulsión silenciosa del panel
- Qué pasó: al entrar al admin con el atajo `admin`/`123` (login.page.ts), el frontend navegaba directo a `/admin` sin llamar al backend — no había token real. Al primer request protegido (ej. `GET /admin/productos`), el backend devolvía 401 y el `AuthInterceptor` global limpiaba la sesión y redirigía a `/login`, sensación de "me expulsó".
- Causa: atajo temporal (placeholder previo al guard de rol real) que nunca autenticaba de verdad contra el backend, dejado activo después de que el CRUD real empezó a depender de tener un token válido.
- Regla: cualquier atajo/mock de acceso temporal a una sección protegida por API debe eliminarse en cuanto esa sección empiece a consumir endpoints reales protegidos — si no, el interceptor de 401 lo va a interpretar como sesión inválida y expulsar al usuario de forma confusa. Login real ahora redirige por `rol` (`super_admin`/`admin_sede` → `/admin`, `cliente` → `/tabs/home`).
- Fecha: 2026-07-10
