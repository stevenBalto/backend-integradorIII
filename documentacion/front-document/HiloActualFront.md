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

## Sesión 2026-07-23 — Fix: sesión compartida entre pestañas (admin perdía su sesión al registrar un cliente en otra)
- Contexto: el usuario reportó que los clientes registrados por el login caían en el panel admin "Usuarios y roles" (fix real en el backend, ver `HiloActualBack.md`). Al verificar el fix, apareció un SEGUNDO bug real: si se registraba un cliente nuevo en una pestaña del navegador mientras el panel admin ya estaba abierto en otra pestaña, al recargar esta última se rompía con `403 Forbidden` en `admin/usuarios/opciones` ("No tenés permiso para realizar esta acción.").
- **Diagnóstico** (confirmado con el backend probado end-to-end por curl, sin errores — el bug era 100% frontend): `TokenStorageService` usaba `@ionic/storage-angular` (IndexedDB), un almacén **compartido por todas las pestañas del mismo origen**, no aislado por pestaña. `AuthService.persistir()` escribe ahí en cada login/registro. Al recargar (F5) la pestaña del admin, `AuthService.init()` (`APP_INITIALIZER`) vuelve a leer el token desde ese store compartido — y como la otra pestaña acababa de registrar un cliente y sobreescribió las mismas claves (`auth_token`/`auth_user`), la pestaña del admin terminaba levantando el token del CLIENTE en vez del suyo propio.
- **Fix**: `token-storage.service.ts` reescrito para usar `sessionStorage` (nativo del navegador, aislado por pestaña/ventana) en vez de `@ionic/storage-angular`. Mismo cambio aplicado a `superadmin-auth.service.ts` (mismo patrón compartido, mismas claves `sa_token`/`sa_user`) a pedido explícito del usuario, aunque no era parte del bug reportado — evita que el panel superadmin sufra la misma contaminación si se abre en varias pestañas.
- `carrito-storage.service.ts` NO se tocó — sigue usando `@ionic/storage-angular` a propósito (el carrito de compras sí tiene sentido que sea compartido/persistente, no es una sesión de usuario). `IonicStorageModule.forRoot()` se mantiene en `app.module.ts` porque `carrito-storage.service.ts` todavía lo necesita.
- Verificado: `npx tsc --noEmit` limpio tras ambos cambios. Repro manual pendiente de confirmar por el usuario (recargar ambas pestañas para tomar el código nuevo, registrar cliente en una, confirmar que la otra sigue mostrando solo al staff sin 403).
- Pendiente: nada pendiente conocido de este fix. Si en el futuro se quiere "recordar sesión" entre reinicios completos del navegador (no solo reload de la misma pestaña), `sessionStorage` NO alcanza — habría que evaluar un storage persistente pero con clave namespaced por pestaña (ej. un id de pestaña generado en runtime), no volver a un store compartido plano.
- NO TOCAR / nota: el bug reportado tenía dos mitades — la del backend (clientes visibles en Usuarios, ver `HiloActualBack.md`) y esta del frontend (sesión pisada entre pestañas). Ambas están resueltas, pero son fixes independientes en archivos distintos.

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

## Sesión 2026-07-16 — Módulo 5: Carrito, checkout, seguimiento y admin de Pedidos + tamaños/acompañamientos
- Hecho:
  - **Wording de estados compartido**: `shared/constants/pedido-estado.ts` (nuevo) — única fuente de verdad con las 5 etiquetas ("Por comenzar"/"En preparación"/"Listo"/"Entregado"/"Cancelado") y la máquina de estados (`PEDIDO_TRANSICIONES`, `esTransicionValida()`). Tanto el admin (`status-badge`) como el cliente (mis-pedidos, buscador de pedido) importan de este único archivo — el backend nunca manda texto, solo la clave `estado`, así que es imposible que el wording se desalinee entre las dos vistas (requisito explícito del usuario).
  - **`status-badge.component.ts`**: agregadas claves `pendiente/en_proceso/listo/entregado` (reutiliza `cancelled` ya existente para `cancelado`) + helper `estadoToStatusType()`.
  - **`CarritoService`** (nuevo, `core/services/carrito.service.ts` + `carrito-storage.service.ts`): mismo patrón de 3 capas que `AuthService`/`TokenStorageService` (BehaviorSubject en memoria + persistencia en `@ionic/storage-angular` + hidratación en `APP_INITIALIZER` de `app.module.ts`). Guarda líneas del carrito (producto + tamaño elegido + extras elegidos + cantidad) y la sucursal/modalidad seleccionadas.
  - **`pedir.page.ts/.html/.scss`** (reconstrucción completa): página con 4 vistas (`menu`/`carrito`/`checkout`/`confirmacion`) dentro del mismo tab "Carrito". Selector de sucursal (de `GET /sucursales`), toggle modalidad, catálogo real con búsqueda y filtro por categoría, FAB flotante con contador+total del carrito, checkout con "a nombre de quién" (precargado del usuario logueado) + mensaje "pagás en caja", pantalla de confirmación con el código grande y hora estimada.
  - **`home.page.ts/.html`**: el botón "Añadir al carrito" (antes un toast placeholder) ahora llama a `CarritoService.agregar()`. El modal de detalle de producto ya existente se extendió con selector de tamaño (radio, obligatorio si el producto tiene `tamanos`) y checkboxes de extras (siempre opcionales).
  - **`mis-pedidos/`** (módulo nuevo, ruta top-level `/mis-pedidos`): lista los pedidos del cliente con código, fecha, estado (wording compartido) y total; conectado al botón "Mis pedidos" de `mi-cuenta.page.html` que antes no hacía nada.
  - **Buscador de pedido por código** (nuevo, modal chico en `mi-cuenta.page.html`): input de código → `PedidoService.buscarPorCodigo()` (endpoint público) → pinta el estado con el mismo wording que el admin.
  - **Admin `pedidos.page.ts/.html`** (reconstrucción completa, mismo patrón que `inventario.page.ts`): KPIs clicables como filtro (Pendientes/En preparación/Listos), búsqueda por código o nombre de cliente, polling cada 15s (pausado si hay un modal abierto, refresco inmediato tras cualquier acción propia), modal de detalle **corrigiendo el bug existente** donde siempre mostraba el mismo pedido hardcodeado sin importar la fila clickeada — ahora muestra el pedido real con su carrito itemizado (producto, cantidad, tamaño si aplica, extras), historial de estados, botones de transición según la máquina de estados compartida, botón "Registrar pago" solo si `entregado && !pagado`. Cada fila muestra código + nombre del cliente (requisito explícito).
  - **`admin/menu/menu.page.ts/.html`**: agregado el primer `FormArray` del proyecto (tamaños: nombre+precio, filas repetibles) + bloque de gestión de acompañamientos (CRUD de `Extra`) anclado al selector de categoría existente.
  - **`producto.model.ts`/`producto.service.ts`**: `Producto` gana `tamanos: ProductoTamano[]` y `extras: ExtraDisponible[]`; el envío multipart ahora incluye `tamanos` como JSON string.
  - Verificado: reinicio en frío de `ionic serve` (se crearon ~25 archivos nuevos con el server corriendo — mismo antierror EF-02 de sesiones anteriores) → `Compiled successfully.` limpio. Revisé el código generado por el agente y corregí varios acentos/ortografía menor ("en_proceso" label, "Comer aquí", "¿Confirmar...?", "Código de pedido", etc.) que quedaron sin tilde.
- Pendiente:
  - Verificación visual manual en navegador del flujo completo (no se pudo hacer click-through real esta sesión, solo compilación + revisión de código).
  - Sin integración de cupones en el checkout (el campo existe en el backend pero no se usa en este pase).
  - Guard de rol real para `/admin` (pendiente conocido, arrastrado de sesiones anteriores).
- NO TOCAR / nota: no se tocó `src/app/admin/ofertas/ofertas.page.ts` (trabajo del compañero). El wording de estados de pedido SOLO se edita en `shared/constants/pedido-estado.ts` — si se cambia ahí, cambia automáticamente en admin y cliente a la vez; no hardcodear el texto de un estado en ningún otro lugar.

## Sesión 2026-07-17 — Selector de sucursal, tarjeta Sucursales real, botón expandir Pedidos, modal Extras en Menú
- Contexto: 3 quejas puntuales del usuario tras probar Pedidos: selector de sucursal del carrito "horrible" + vacío para instancias sin sucursal propia, pedidos de prueba visibles en el admin (limpiados directamente en BD, sin cambio de código), y falta de botón visible para expandir un pedido a card en la fila de "Pedidos recientes". Más una feature nueva: extras generales (aplican a todo el catálogo) y asignación puntual de una extra a un producto específico.
- **`pedir.page.html`/`.ts`/`.scss`**: el `<select>` HTML plano del selector de sucursal se reemplazó por `ion-select` nativo (`interface="action-sheet"`, header "Elegí tu sucursal"), paleta cálida (blanco/rojo, nunca negro), SIEMPRE visible aunque haya una sola sucursal (antes el auto-select de la única sucursal dejaba el control casi invisible).
- **`admin/configuracion/configuracion.page.ts`/`.html`/`.scss`**: la tarjeta "Sucursales" (antes 100% maqueta con array `branches` hardcodeado) ahora consume `GET/POST/PUT /admin/sucursales` real — listado con inactivas, modal crear/editar (reutiliza las clases `order-modal`/`menu-form` ya usadas en Menú/Pedidos), toggle de `activa` directo desde la tarjeta. El resto de la página de Configuración sigue siendo maqueta estática (fuera de alcance). `sucursal.service.ts`/`sucursal.model.ts` ganaron `listarAdmin()`/`crear()`/`actualizar()`/`SucursalPayload`.
  - **Corrección post-verificación**: el backend descubrió que `sucursales.direccion` es NOT NULL en la BD real (el contrato original asumía nullable) e hizo el campo `required` en el backend — pero el formulario reactivo del frontend no tenía `Validators.required` en `direccion`, quedando desalineado (hubiera dejado guardar vacío y explotar en 422 recién al submit sin avisar antes). Corregido: `direccion` ahora es `[Validators.required, Validators.maxLength(200)]` + asterisco en el label.
- **`admin/pedidos/pedidos.page.html`/`.scss`**: el botón `"Ver"` (texto plano tipo link, sin ícono, única señal visual era `cursor:pointer` en toda la fila) se reemplazó por un ícono-botón real (`expand-outline`) con el mismo estilo cuadrado que otras acciones de tabla del admin. `abrirDetalle()` y el modal de detalle NO se tocaron (ya funcionaban correctamente, era solo un problema de affordance visual).
- **`admin/menu/menu.page.ts`/`.html`/`.scss`**: botón nuevo "Extras" (variante outline) junto a "Nuevo producto", abre un modal separado del bloque inline de extras-por-categoría ya existente (ese sigue intacto). Dos secciones: crear extra (nombre/precio + toggle "Extra general" que oculta/limpia el select de categoría, mutuamente excluyentes) y asignar extra existente a un producto puntual (selects de extra —filtrando las generales, no aplica— y producto, lista de productos ya asignados con botón ✕). `extra.model.ts`/`extra.service.ts` ganaron `es_general`, `productos_asignados`, `obtenerDetalle()`, `asignarAProducto()`, `desasignarDeProducto()`.
- Verificado: cada chunk lazy recompiló limpio tras cada edición (`√ Compiled successfully.`, última línea del log en cada caso, sin quedar en un estado transitorio de error). No se crearon suficientes archivos nuevos como para disparar el antierror EF-02 (incremental compiler no quedó obsoleto).
- Pendiente:
  - Verificación visual manual en navegador (click-through real) de los 4 cambios — esta sesión solo cubrió compilación limpia + revisión de código + curl del lado backend.
  - CRUD de Sucursales sigue sin "eliminar" (no se pidió).
- NO TOCAR / nota: el bloque inline de extras-por-categoría dentro del formulario de producto (`menu.page.html`, dentro de `*ngIf="form.get('categoria_id')?.value"`) es DISTINTO del modal nuevo "Extras" del header — ambos coexisten a propósito, no fusionarlos sin que se pida.

## Sesión 2026-07-19 — Batch de UX en Pedidos (11 puntos)
- Contexto: lote grande de correcciones/features pedidas tras probar la app en navegador (capturas de escritorio adjuntas mostrando el carrito/checkout mal centrados y el select de sucursal con una línea verde descuadrada).
- **Admin Pedidos — un solo click para cambiar estado**: se eliminó el modal intermedio de confirmación + motivo (`modalEstadoOpen`/`comentarioEstado`/`abrirCambioEstado`/`confirmarCambioEstado`) — los botones de estado ahora llaman directo a `cambiarEstadoDirecto(estado)`. El `window.confirm` de "Registrar pago" NO se tocó (acción financiera, se queda con confirmación).
- **Botón "revertir" sutil en el historial**: cada fila del historial (excepto la más reciente, calculada por timestamp vía `indiceHistorialActual()`, no por índice de array) tiene un ícono `arrow-undo-outline` que llama `revertirAEstado()` → nuevo endpoint del backend. Sin diálogos bloqueantes.
- **Extras con foto ("upsell" estilo Taco Bell)**: `extra.service.ts` ahora arma `FormData` (mismo patrón que `ProductoService`) para poder subir imagen. Las cards de extra en el modal de detalle de producto (duplicado en `home.page.html` Y `pedir.page.html`, se actualizaron LOS DOS) pasaron de checkbox discreto a card grande con thumbnail (placeholder `fast-food-outline` si no tiene foto).
- **Tamaño con cantidad** (`producto_tamanos.descripcion`): se muestra como `"{{ nombre }} — {{ descripcion }}"` en ambas copias del modal; el `FormArray` de tamaños en Menú admin ganó el 3er campo.
- **Fix visual select de sucursal**: la línea verde era `--highlight-color-valid` de Ionic sin overridear (solo se había seteado `-focused`) — se agregó `--highlight-color-valid`/`-invalid: var(--rooster-red)` + `--highlight-height: 0` para un look más limpio.
- **Responsive Carrito/Checkout**: nuevo wrapper `.pedir-narrow` (`max-width:600px` + `margin:auto` en ≥768px, mismo patrón ya documentado del antierror EF-01) aplicado a las vistas `carrito`/`checkout` — antes solo la vista `menu` tenía buen tratamiento de centrado en desktop.
- **"A nombre de quién"**: dejó de precargar el nombre como `value` — ahora es solo `placeholder`, campo obligatorio (`Validators.required` vía `puedeEnviarPedido`).
- **Modalidad visible en el checkout** antes de confirmar.
- **Wording compartido nuevo**: `shared/constants/modalidad.ts` (mismo patrón que `pedido-estado.ts`) — "Comer aquí" (ambiguo, alguien pidiendo desde su casa podía leerlo como "comer en mi casa") pasó a **"Comer en el restaurante"** en TODOS los usos reales (pedir, mi-cuenta, mis-pedidos, admin pedidos, modality-pill, admin clientes — este último ya tenía el texto correcto pero hardcodeado aparte, ahora importa el constante). NO se tocaron los mocks estáticos de `admin/analiticas`/`admin/notificaciones` (datos de demostración sin relación real a pedidos).
- **"Tiempo estimado" eliminado** de todas las pantallas de cliente (el backend ya no lo manda, se quitó el binding en vez de dejarlo roto).
- **NUEVO — "Buscar mi pedido" en el tab Carrito**: antes solo existía en Mi Cuenta, y usando el endpoint público mínimo. Ahora hay un botón en el header de `pedir.page.html` Y el modal de Mi Cuenta fue migrado — AMBOS usan el endpoint nuevo autenticado (`pedido.service.ts::buscarPropioPorCodigo()`) que trae detalle completo (notas, items, sucursal, hora exacta, estado con wording compartido). El endpoint público sigue existiendo en el service como fallback, sin usarse desde ninguna pantalla por ahora.
- Verificado: compilación final limpia (`√ Compiled successfully.`, hubo un error transitorio de pipe `crcCurrency` en `mi-cuenta` mientras se armaba el módulo, se autoresolvió al agregar el import — confirmado que la ÚLTIMA compilación del log es exitosa, no una intermedia).
- Pendiente:
  - Verificación visual manual en navegador de los 11 puntos — esta sesión cubrió compilación limpia + revisión de código + curl del lado backend, no un click-through real.
  - Los estilos `.estado-confirm__*` (del modal de cambio de estado eliminado) quedaron huérfanos en el scss de `pedidos.page` — inertes, no rotos, pendiente de limpieza cosmética si se quiere.
- NO TOCAR / nota: `GET /pedidos/buscar` (público, sin auth) sigue existiendo tal cual — el nuevo autenticado es un endpoint APARTE, no un reemplazo. El `window.confirm` de registrar pago en Pedidos admin es intencional, no se quitó (distinto del cambio de estado).

## Sesión 2026-07-19 (cont.) — 4 bugs reales encontrados al probar el batch anterior en navegador
- Contexto: instalé Puppeteer (`puppeteer-core`, apuntando al Chrome del sistema, sin descargar binario nuevo) para verificar visualmente en vez de solo revisar código — hasta ahora estas sesiones se cerraban sin click-through real. Reproduje login + Carrito + admin Pedidos con capturas reales antes y después de cada fix.
- **Tabla de Pedidos admin casi vacía**: causa real en el backend (`listarAdmin()` no cargaba `detalles`), no en el frontend — `{{ p.items.length }}` rompía el render de toda la fila al recibir `items` ausente. Verificado con captura: tabla ahora muestra código, modalidad ("Comer en el restaurante"), estado ("Por comenzar"), pago, todo visible.
- **Logo de Rooster "desaparecido" en el header del tab Carrito**: `pedir.page.scss` tenía `.pedir-header__logo { @media (min-width:1024px) { display:none; } }` — YA estaba así antes de esta sesión (no lo agregó ningún cambio reciente), simplemente nadie lo había notado hasta probar en desktop. Se quitó esa regla; el logo ahora es visible en cualquier ancho.
- **Responsive de Carrito/Checkout seguía feo pese al fix anterior**: el fix de la sesión pasada (`.pedir-narrow { max-width:600px; margin:auto }`) SÍ centraba horizontalmente, pero el contenido quedaba pegado ARRIBA con un vacío enorme abajo en viewports altos (el bug real no era horizontal, era vertical). Fix completo con el patrón ya probado de las pantallas de auth (antierror EF-01): `.pedir-content::part(scroll) { display:flex; flex-direction:column; }` + `.pedir-narrow` como flex item (`flex:1; min-height:100%`) que además centra a SUS HIJOS (`align-items:center; justify-content:center` en desktop, cada hijo directo con `max-width:600px`). Verificado con captura: la card de "Tu carrito" y "Confirmar pedido" quedan centradas en ambos ejes.
- **"A nombre de quién" mostraba el nombre de la cuenta, no el escrito en el checkout**: el campo nunca se mandaba al backend (bug real de diseño, no solo de UI) — ver esquema nuevo `pedidos.nombre_cliente` en `HiloActualBack.md`. Fix en frontend: `pedido.model.ts` (`Pedido.nombre_cliente`, `CrearPedidoPayload.nombre_cliente`), `pedir.page.ts::enviarPedido()` ahora manda `nombre_cliente`, y los DOS lugares que mostraban el resultado de "buscar mi pedido" (`pedir.page.html` línea ~376 y `mi-cuenta.page.html` línea ~182) mostraban `nombreClientePlaceholder`/`nombreUsuario` (el nombre de LA CUENTA) en vez de `pedidoBuscado.nombre_cliente`/`pedidoEncontrado.nombre_cliente` (lo que realmente se escribió) — corregido con fallback al nombre de cuenta solo para pedidos viejos sin el campo. La tabla y el modal de detalle de Pedidos admin también migraron a `nombre_cliente` (con el mismo fallback) porque el staff debe llamar por el nombre EN el pedido, no el de la cuenta que lo hizo.
- Verificado end-to-end con capturas reales (Puppeteer) + curl del lado backend. Limpié los scripts/capturas temporales del repo y desinstalé `puppeteer-core` al terminar (no quedó en `package.json`/`package-lock.json`).
- NO TOCAR / nota: si se necesita volver a verificar visualmente sin depender de un navegador manual, el patrón es `npm install --no-save puppeteer-core` + `executablePath` apuntando a `C:\Program Files\Google\Chrome\Application\chrome.exe` (no hace falta descargar Chromium aparte) — desinstalar y borrar los scripts al terminar para no dejar rastros en el repo.

## Sesión 2026-07-19 (cont. 2) — 2 hallazgos más tras revisar las capturas del fix anterior
- **Card de producto vs. caja de total no coincidían en ancho** (ver `AntierroresFront.md` EF-10): medido con Puppeteer (`getBoundingClientRect()`) que `.cart-item` quedaba 32px más angosta por lado que `.cart-footer` porque `.cart-list` tenía padding lateral en desktop que `.cart-footer` no tenía. Fix: `.cart-list` sin padding lateral en desktop (`padding:0`), dejando que la card del producto llene el mismo ancho que la caja del total — verificado con medición antes/después, ahora ambas cajas son 420–1020px exactas.
- **Producto de prueba "Pizza Verificacion Plan" (categoría Pizzas) seguía visible en el menú real** — quedó de una sesión anterior (nunca se limpió). Soft-deleted directamente en BD (mismo mecanismo de "eliminar" que ya usa el CRUD de productos). Catálogo activo hoy: solo "Pizza Hawaiana".
- NO TOCAR / nota: la categoría "Pizzas" (id=2) es real, no es dato de prueba — no confundir con el producto de prueba que sí se limpió.
- Contexto: esta rama y la del compañero divergieron del mismo commit antes de que existiera el módulo Pedidos. El compañero, sin el carrito real todavía bajado, reestructuró Home/Pedir como "vitrina" (ver sesiones del compañero abajo) dejando el botón "Añadir al carrito" como placeholder a propósito. Esta sesión ya había reconstruido `home.page.ts/.html` y `pedir.page.ts/.html/.scss` con carrito/checkout/tracking 100% funcional. Conflicto real de contenido en ambos archivos (no solo docs) — decisión del usuario: **mantener la versión con el carrito real**, no la reestructuración visual del compañero.
- Resolución aplicada: `home.page.ts/.html/.scss` y `pedir.page.ts/.html/.scss/module.ts` quedaron con la versión de esta sesión (carrito real, checkout, tracking por código). Se descartó la versión del compañero de esos mismos archivos (Home como vitrina de Ofertas/Cupones/Destacados con menú movido a Carrito, sin carrito real).
- **Lo que SÍ se conserva del compañero** (no conflictuaba, son módulos/archivos que esta sesión no tocó): módulo admin **Inicio** (`admin/inicio/`, curación de Destacados/Populares/Nuevo + oferta hero), módulo admin **Clientes** (`admin/clientes/`, analítica con Chart.js), KPIs clicables + buscador en Ofertas y cupones admin, `productos.popular`/`nuevo` en el modelo/servicio de producto.
- **Gap de producto abierto**: el admin YA puede curar Destacados/Populares/Nuevo/oferta-hero desde "Inicio" (`GET/PUT /home-config`), pero el Home del cliente (versión de esta sesión, la que quedó) todavía NO consume esas secciones curadas — sigue mostrando el catálogo completo con carrito real integrado, no la vitrina de Ofertas/Cupones/Destacados que armó el compañero. Portar esa curación al Home real (el que tiene el carrito) queda pendiente explícito, no se resuelve en este merge.
- NO TOCAR / nota: la nota "NO TOCAR" de la sesión del compañero (abajo) que decía "cualquiera que trabaje el carrito real debe construir sobre esta base [la vitrina], no reemplazarla desde cero" quedó **superada por decisión explícita del usuario** — se reemplazó igual, priorizando el carrito ya funcional. No revertir `home.page.ts`/`pedir.page.ts` a la versión vitrina sin instrucción nueva del usuario.

## Sesión 2026-07-17 (cont.) — Home rediseñado como vitrina + tab "Carrito" con el menú real + módulo admin "Inicio"
- Hecho:
  - **Home** (`src/app/home/`) dejó de ser el menú completo y pasó a ser vitrina: sección **Ofertas** vigentes, **Cupones para ti** vigentes (`OfertaService.listarPublicas()`/`CuponService.listarPublicos()`, ya filtrados por vigencia en el backend) y **Destacados** (`productos.destacado=true`, filtrado client-side sobre `listarDisponibles()`). Estilos nuevos `.promo-card`/`.promo-scroll` en `home.page.scss`. El botón "Añadir al carrito" del modal de detalle sigue igual (toast placeholder) — **no se tocó lógica de carrito**, queda para otro dev.
  - **Tab "Carrito"** (`src/app/pedir/`, antes vacío/placeholder con datos hardcodeados en dólares) ahora tiene el menú completo real: categorías (`CategoriaService.listarActivas()`), productos (`ProductoService.listarDisponibles()`), buscador funcional (`busqueda` + filtro client-side por nombre), filtro por categoría, modal de detalle (mismo patrón `dish-modal` que Home, duplicado en `pedir.page.scss` porque cada página es dueña de su CSS en este proyecto). Agregado `CrcCurrencyPipe` al `pedir.module.ts` (antes mostraba precios hardcodeados en `$`).
  - **Nuevo módulo admin "Inicio"** (`src/app/admin/inicio/`), sidebar entre Dashboard e Inventario (`admin-shell.page.ts`/`admin-shell-routing.module.ts`, ruta `inicio`). Decisión de diseño: **no duplica datos** — es una pantalla de curación sobre lo que ya existe:
    - Tabla de productos disponibles con toggle `ion-toggle` de "destacado" (mismo campo que el checkbox de Menú, otra puerta de entrada — llama `ProductoService.actualizar()` reconstruyendo el payload completo con `destacado` invertido).
    - Selector de "oferta destacada (hero)" entre las ofertas vigentes — persiste vía `HomeConfigService` (`GET/PUT /home-config`, nuevo servicio + modelo `core/models/home-config.model.ts`).
    - Preview de solo lectura de cupones vigentes (sin curación manual, se muestran automáticamente).
  - **Home** ahora también consume `HomeConfigService.obtener()`: si hay `oferta_hero_id`, esa oferta se ordena primero en la lista y se le agrega badge "★" + halo dorado (`.promo-card--hero`).
  - Verificado: `ng serve` compiló sin errores en cada paso (Home, Carrito, módulo Inicio). Backend probado end-to-end por curl (ver `HiloActualBack.md`).
- Pendiente:
  - Verificación visual manual en navegador del flujo completo (Home → toggle destacado en admin → se refleja en Home; elegir oferta hero → aparece primera con ★).
  - Carrito real (agregar/quitar/cantidad, modalidad Comer aquí/Para llevar) — explícitamente fuera de esta sesión, queda para otro dev.
  - Si se agrega curación de "cupón hero" a futuro, replicar el mismo patrón de `home-config` (ver nota en `HiloActualBack.md`).
- NO TOCAR / nota: `pedir.page.ts`/`.html` ya no son placeholder — cualquiera que trabaje el carrito real debe construir sobre esta base (ya tiene productos reales, buscador, filtro, modal), no reemplazarla desde cero. El botón "Añadir al carrito" en Home y en Carrito siguen siendo el mismo toast placeholder a propósito, para que el dev de carrito los conecte a la vez.

## Sesión 2026-07-17 (cont. 2) — Home con múltiples secciones: Populares y Lo nuevo
- Hecho:
  - `Producto`/`ProductoPayload` (`core/models/producto.model.ts`) ganaron `popular`/`nuevo` (boolean), igual que `destacado`. `ProductoService.aFormData()` los agrega al `FormData` (`core/services/producto.service.ts`).
  - **Home** (`home.page.ts`/`.html`): ahora carga 3 listas independientes (`destacados`, `populares`, `nuevos`) filtrando client-side sobre `listarDisponibles()`. Cada sección solo se muestra si tiene al menos un producto (`*ngIf="lista.length > 0"`). El grid de tarjetas se factorizó en un `<ng-template #dishGrid let-lista="lista">` reutilizado con `*ngTemplateOutlet` para las 3 secciones (evita triplicar el HTML).
  - **Admin "Inicio"** (`admin/inicio/inicio.page.ts`/`.html`): la tabla de "Destacados del Home" pasó a "Secciones del Home" con 3 columnas de `ion-toggle` independientes (Destacado/Popular/Nuevo) por producto. Método `toggleSeccion(producto, campo)` reemplaza al viejo `toggleDestacado()` — reconstruye el payload completo (necesario porque `ProductoService.actualizar()` no soporta PATCH parcial) e invierte solo el campo tocado.
  - KPIs del admin "Inicio" ahora muestran los 3 contadores (Destacados/Populares/Lo nuevo) + Ofertas vigentes.
- Pendiente:
  - Nada pendiente conocido. Si se agrega una 4ta sección a futuro, replicar: nueva columna boolean (ver `HiloActualBack.md`), nuevo `getter` + `*ngFor` toggle en Inicio, nueva sección `*ngTemplateOutlet` en Home.
- NO TOCAR / nota: `destacado` se sigue pudiendo togglear también desde `admin/menu/menu.page.ts` (checkbox ya existente) — `popular`/`nuevo` NO tienen control en Menú a propósito, solo viven en el módulo "Inicio" para no duplicar UI de curación en dos lugares.

## Sesión 2026-07-18 — Ofertas/Cupones con KPIs clicables + nuevo módulo admin "Clientes"
- Hecho:
  - **Admin → Ofertas y cupones** (`src/app/admin/ofertas/`): KPIs ahora son clicables (mismo patrón ya usado en Inventario). `FiltroOferta`/`FiltroCupon` (tipos nuevos), `filtroOferta`/`filtroCupon` (estado), `setFiltroOferta()`/`setFiltroCupon()` (toggle a `'todos'` si se re-toca la misma), getters `ofertasFiltradas`/`cuponesFiltrados` (combinan busqueda + filtro). Buscador (`admin-search-input`) que antes estaba sin `[(value)]` ahora esta conectado y filtra. Tablas iteran sobre getters filtrados, con mensaje de "vacio" distinto segun si no hay datos o si no coincide la busqueda/filtro.
  - **Nuevo módulo admin "Clientes"** (`src/app/admin/clientes/`, registrado en sidebar entre Ofertas y Usuarios, `clientes.module.ts`/`clientes.page.ts`/`.html`/`.scss`):
    - Modelos/servicio nuevos: `core/models/cliente.model.ts` (`Cliente`, `PedidoResumen`), `core/services/cliente.service.ts` (`listarConEstadisticas()`, `listarPedidos(id)`).
    - 4 KPIs clicables: "Clientes totales", "Compraron (30 dias)", "Sin compras" (filtros normales, mismo patron que Inventario/Ofertas), "Top comprador" — **NUEVO tipo de filtro**: al tocar "Top comprador", filtra la tabla para mostrar UNICAMENTE ese cliente (`filtro: FiltroCliente` con valor `top_comprador`, distinto de los filtros de categoría ya conocidos).
    - Tabla con busqueda (nombre/email) + boton de historial por cliente que abre un modal (`historialOpen`/`historialCliente`/`historialPedidos`) con su historial de pedidos — mismo patron visual que el modal de historial de tomas fisicas en Inventario.
    - **Seccion "Top 5 clientes por gasto"**: implementada con Chart.js (`chart.js@^4`, **dependencia NUEVA**, no estaba en el proyecto). Componente `clientes-top-chart.component.ts` (standalone, unico standalone del proyecto — el resto usa NgModule con `declarations`) — barra horizontal (`indexAxis:'y'`), un solo dataset, sin leyenda, eje X con colones abreviados (₡184k), solo la barra #1 (el pico) coloreada con `var(--admin-accent)` y el resto en gris neutral (respeta regla de identidad: "rojo solo en nav activo, botones primarios y dato pico de graficos"), `role="img"` + `aria-label` para accesibilidad, `animation: false` + `resizeDelay: 200` (fix de bug real: el ResizeObserver de Chart.js reaccionaba a las transiciones de pagina de Ionic y la animacion se veia trabada — ver `AntierroresFront.md` EF-04).
    - Fix en componente compartido `admin-shared/admin-kpi-card.component.ts`: agregado `:host{display:block;height:100%}` y `.kpi{height:100%}` para que las 4 tarjetas de un `admin-grid-4` se estiren parejo aunque una tenga texto `[sub]` extra (antes "Top comprador" quedaba con distinta altura que las otras 3). No afecta otras paginas porque ninguna otra usaba `[sub]` todavia.
  - Conectado a la API real (`GET /admin/clientes`, `GET /admin/clientes/{id}/pedidos`) — contrato documentado en `HiloActualBack.md`. Seeder de prueba ya ejecutado en BD local, ranking verificado con datos reales.
- Pendiente:
  - Nada pendiente conocido del módulo Clientes (funcional end-to-end con datos reales).
  - Guard de rol real para `/admin` (pendiente conocido, arrastrado de sesiones anteriores).
- NO TOCAR / nota: Chart.js es la primera dependencia de charting real del proyecto (todo lo existente antes — `bar-chart`, `donut-chart`, `progress-bar`, `mini-bar`, `area-chart` — es CSS/SVG hecho a mano). Se agrego como excepcion puntual porque el usuario pidio explicitamente Chart.js para este componente. Ver `AntierroresFront.md` EF-04 gotcha de Chart.js + Ionic.
