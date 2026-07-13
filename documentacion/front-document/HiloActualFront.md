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

## Sesión 2026-07-12 — Ofertas/Cupones rediseñada con la paleta del proyecto
- Hecho:
  - Terminada la pestaña **Cupones** del módulo **Ofertas y cupones** en `src/app/ofertas/` manteniendo el estilo y paleta ya definidos en el proyecto.
  - La vista quedó con tabs reales entre **Ofertas** y **Cupones**, usando datos hardcodeados locales para ambas secciones.
  - Se reemplazaron los SVGs inline repetidos por `ion-icon` reutilizables para mejorar mantenibilidad sin romper el look del prototipo.
  - Se respetaron los colores base del proyecto: rojo, naranja, dorado y tan, evitando introducir una paleta nueva.
- Pendiente:
  - Conectar ofertas y cupones a datos reales cuando exista el endpoint correspondiente.
  - Revisar si el módulo admin de ofertas/cupons va a reutilizar el mismo patrón visual o si requiere su propia versión.
- NO TOCAR / nota: la lógica actual es de maquetado visual; si se agregan datos reales después, cuidar no romper la estructura y estilos existentes.

## Sesión 2026-07-12 (cont.) — Admin: Ofertas y cupones conectado a la API real
- Hecho:
  - Conectado el módulo **admin → Ofertas y cupones** (`src/app/admin/ofertas/`) a la API real: `GET/POST/PUT/DELETE /admin/ofertas` y `/admin/cupones`, JSON normal (sin `FormData`, a diferencia de Menú que sube foto).
  - Modelos nuevos: `core/models/oferta.model.ts` (`OfertaProducto`, `Oferta`, `OfertaPayload`, reutiliza `ApiCollection<T>`/`ApiResource<T>` de `producto.model.ts`) y `core/models/cupon.model.ts` (`Cupon`, `CuponPayload`).
  - Servicios nuevos: `core/services/oferta.service.ts` y `core/services/cupon.service.ts`, ambos con `listarTodos()` / `crear()` / `actualizar()` / `eliminar()`.
  - `admin/ofertas/ofertas.page.ts`: arrays hardcodeados reemplazados por datos reales; carga ofertas/cupones/productos en `ngOnInit`; KPIs (total/activas/por vencer en 7 días/vencidas para ofertas; total/activos/usos totales/agotados para cupones) calculados en TS; badges de estado dinámicos; dos modales reactivos (`FormGroup`) — el de oferta con multi-select de productos; guardar/eliminar con `window.confirm`.
  - `admin/ofertas/ofertas.page.html`: bindings a datos reales, handlers de click (editar/eliminar/nueva oferta/nuevo cupón), estados de loading/error, modales al final del template.
  - `admin/ofertas/ofertas.page.scss`: bloques de modal/formulario nuevos reutilizando variables `--rooster-*` existentes, sin cambiar paleta ni layout.
  - `admin/ofertas/ofertas.module.ts`: agregado `ReactiveFormsModule`.
  - Patrón replicado de `admin/menu/` (módulo ya funcional conectado al backend real), adaptado a JSON plano en vez de `FormData`.
  - Verificado con `ng build --configuration=development`: compila sin errores.
- Pendiente:
  - Verificación visual manual en navegador (`ionic serve`) de crear/editar/eliminar oferta y cupón en `/admin/ofertas` — no se corrió por restricción de permisos del agente que hizo la implementación.
  - Guard de rol real para `/admin` (pendiente conocido, arrastrado de sesiones anteriores).
- NO TOCAR / nota: fuera de alcance de esta sesión, sin tocar: `src/app/ofertas/` (vista cliente, sigue con datos hardcodeados de la sesión anterior).
## Sesión 2026-07-12 (cont.) — Admin: Inventario de insumos (nuevo módulo)
- Hecho:
  - Nuevo módulo **admin → Inventario** (`src/app/admin/inventario/`) — inventario de INSUMOS/ingredientes (carnes, queso, etc.), concepto SEPARADO de los productos del menú (pizzas/platillos). Conectado a la API real bajo `/admin/insumos`.
  - Modelo nuevo: `core/models/insumo.model.ts` (`Insumo`, `InsumoPayload`, `InsumoMovimiento`, `TomaFisicaPayload`, `TomaFisicaResultado`; reutiliza `ApiCollection<T>`/`ApiResource<T>` de `producto.model.ts`).
  - Servicio nuevo: `core/services/insumo.service.ts` con `listarTodos()`, `buscarPorId()`, `crear()`, `actualizar()`, `eliminar()`, `registrarTomaFisica()`, `listarMovimientos()` (mismo patrón Observable + `environment.apiBaseUrl` que `producto`/`oferta` service).
  - `inventario.page.ts`: carga en `ngOnInit`; KPIs (total insumos / bajo stock (`bajo_stock=true`) / stock normal / sin mínimo definido); badge de estado dinámico (`expired` rojo si bajo stock, `active` verde si normal — tipos ya existentes en `status-badge`, no se inventaron nuevos); dos modales reactivos (`FormGroup`).
  - **DECISIÓN CLAVE respetada**: `cantidad_actual` NO se edita en el modal de editar insumo — es solo-lectura ahí (se muestra el valor + hint "Solo se modifica con una toma física"); el control `cantidad_actual` se `disable()` al editar y solo se envía en el POST de creación (como "Cantidad inicial"). La ÚNICA forma de cambiar la cantidad es el modal de **Toma física**.
  - **Modal Toma física** (acción POR FILA en la tabla, junto a editar/eliminar — no botón global, porque es siempre de UN insumo): muestra nombre + cantidad del sistema (solo lectura), input `cantidad_contada`, input `nota` opcional, y diferencia calculada EN VIVO (`cantidad_contada - cantidad_actual`, verde si positiva / rojo si negativa). Llama `POST /admin/insumos/{id}/toma-fisica` y refresca la tabla.
  - Modal Nuevo/Editar: nombre (text), unidad_medida (input con `datalist` de presets kg/g/l/ml/unidad/docena/caja/paquete pero libre, el backend acepta cualquier string), cantidad inicial (solo al crear), stock_minimo (number opcional). Validaciones: nombre y unidad_medida requeridos.
  - `inventario.page.html` / `.scss`: mismo patrón de tabla (`.admin-table-wrap`/`.admin-table` dentro de `admin-section-card` con `admin-search-input` como `card-action`) y modal (`.order-modal`, portado igual que `menu`/`pedidos`) que los módulos existentes; clases propias con prefijo `inv-`; solo variables `--admin-*`/`--rooster-*` existentes, sin colores nuevos.
  - `inventario.module.ts`: `CommonModule` + `FormsModule` + `ReactiveFormsModule` + `IonicModule` + `AdminSharedModule` + ruta hija, igual que `menu`/`ofertas`.
  - **Sidebar + ruteo**: agregado ítem "Inventario" (ícono `cube-outline`, ruta `inventario`) en `admin-shell.page.ts` (entre Menú y Ofertas) y child route lazy-loaded en `admin-shell-routing.module.ts`.
  - Verificado con `ng build --configuration=development`: compila sin errores (solo warning preexistente de `localforage`); chunk lazy `src_app_admin_inventario_inventario_module_ts.js` emitido OK.
- Pendiente:
  - Verificación visual manual en navegador (`ionic serve`) de crear/editar/eliminar insumo y registrar toma física en `/admin/inventario`.
  - Vista/historial de movimientos: el service ya expone `listarMovimientos(id)` pero aún no hay UI que lo consuma (posible siguiente iteración: modal/drawer de historial por insumo).
  - `admin-search-input` en la tabla es decorativo (sin filtrado real), igual que en `menu`/`ofertas` — el componente compartido no emite valor; queda pendiente si se decide hacer búsqueda funcional a nivel global.
  - Guard de rol real para `/admin` (pendiente conocido, arrastrado de sesiones anteriores).
- NO TOCAR / nota: el módulo consume el contrato EXACTO de `/admin/insumos` construido en paralelo por el agente backend — no se cambió ningún nombre de campo ni ruta. `cantidad_actual` es solo-lectura por diseño de auditoría (no "arreglar" permitiendo editarla en el modal normal). No se tocó ningún otro módulo del admin ni la app cliente.

## Sesión 2026-07-13 (cont.) — Inventario: refinamiento según feedback de usuario
- Hecho:
  - **Unidades personalizadas persistentes**: `unidades` pasó de array estático a getter (`inventario.page.ts`) que combina los presets con las `unidad_medida` distintas ya usadas en `insumos` cargados. Si el dueño escribe una unidad nueva y la guarda, queda disponible en el datalist la próxima vez, sin tabla ni endpoint nuevo (se deriva de datos reales ya persistidos).
  - **Botón "Historial" condicional por fila**: nuevo botón `inv-action--history` (ícono `time-outline`) visible solo si `insumo.tiene_movimientos === true`. Requirió: `withCount('movimientos')` en `InsumoRepository::listarTodos()` + `loadCount` de respaldo en `InsumoService::buscarPorId()` (backend), campo nuevo `tiene_movimientos` en `InsumoResource` y en el modelo/interfaz `Insumo` del frontend. Abre modal de historial que consume `listarMovimientos(id)` (ya existía en el service, sin UI hasta ahora).
  - **Buscador funcional**: `SearchInputComponent` (`admin-search-input`) reescrito para soportar `[(value)]` (two-way binding real, antes era decorativo) + `maxlength="100"`. `inventario.page.ts` agrega `busqueda` + getter `insumosFiltrados` (filtra por `nombre`, case-insensitive). Placeholder cambiado a "Buscar insumo por nombre...".
  - **KPIs como filtros clicables**: `AdminKpiCardComponent` extendido con `[clickable]`/`[active]`/`(cardClick)` (backward-compatible, default `false`). Los 4 KPIs de Inventario ahora filtran la tabla (`filtro: FiltroInventario`, toggle on/off si se re-clickea el mismo).
  - **Estados más claros**: `status-badge` ganó dos tipos nuevos `stock_bajo`/`stock_ok` con labels explícitos ("Bajo stock"/"Stock normal"), reemplazando el reuso previo de `expired`/`active` (que mostraba literalmente "Vencida" — confuso).
  - **Validación de inputs** (anti-typing-infinito): `maxLength` en nombre (120)/unidad (20)/nota (255), `Validators.max(999999)` en cantidad_actual/stock_minimo/cantidad_contada, `maxlength` nativo espejado en los `<input>`/`<textarea>` del HTML.
  - **Regla de negocio `stock_minimo ≤ cantidad_actual`**: validado en 3 capas — `StoreInsumoRequest`/`UpdateInsumoRequest` (backend, `withValidator()`) y `guardar()` en `inventario.page.ts` (frontend, usa `form.getRawValue()` para incluir `cantidad_actual` aunque esté `disable()`d en edición). Mensajes en español, contextuales (cantidad "inicial" al crear vs. "actual" al editar).
  - Verificado: frontend recompiló OK (`Compiled successfully.`); backend probado con curl — `POST /admin/insumos` con `cantidad_actual:100, stock_minimo:150` → 422 `"El stock mínimo no puede ser mayor a la cantidad inicial."`.
- Pendiente:
  - Verificar en navegador (no solo curl) el flujo completo de validación de stock_minimo y el botón de historial condicional.
  - Probar el path de UPDATE (`PUT /admin/insumos/{id}`) con curl del mismo modo que CREATE (la regla ya está implementada en `UpdateInsumoRequest`, pendiente de correr la prueba explícita).
- NO TOCAR / nota: `status-badge`, `admin-search-input` y `admin-kpi-card` son compartidos por otros módulos (Menú/Ofertas/Dashboard) — los cambios son aditivos (nuevos inputs/outputs opcionales con default inerte), no rompen esos usos existentes.
