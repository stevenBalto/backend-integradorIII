# Antierrores — Frontend

Catálogo de errores del frontend. Cada vez que se corrige un error, se documenta aquí para que NO se repita en la próxima sesión.

Cómo se llena: una entrada por error corregido, con la regla a no romper.

Formato sugerido por entrada:
```
### EF-01 — <título corto>
- Qué pasó: <descripción del error>
- Causa: <por qué pasó>
- Regla: <qué hacer siempre / nunca para no repetirlo>
- Fecha: YYYY-MM-DD
```

### EF-19 — Imágenes locales de productos: falta la regla `/storage` en el proxy y el pipe `imagenUrl`
- Qué pasó: al preparar la tabla "Productos más vendidos" (Analíticas) para mostrar la foto del producto, se detectó que en cuanto se suban imágenes LOCALES (en vez de las URLs absolutas de Wikimedia que siembra `DemoRoosterSeeder`), las fotos no van a cargar en ninguna pantalla.
- Causa: dos capas independientes.
  1. `proxy.conf.json` del dev-server solo reenviaba `/api` al backend (`127.0.0.1:8000`). Laravel publica las subidas en `/storage/**`, ruta que NO estaba proxeada: el pedido se lo quedaba el dev-server de Angular y devolvía el `index.html` de la SPA, dando una imagen rota difícil de diagnosticar (no es un 404 limpio).
  2. El backend devuelve `imagen_url` CRUDO (`ProductoResource`, `ExtraResource` y `top_productos` de analíticas exponen `$this->imagen_url` sin `Storage::url()` ni `asset()`), y **todas** las plantillas ligan `[src]="p.imagen_url"` directo. Con una ruta relativa (`productos/x.jpg`), el navegador la resuelve contra el origen del FRONTEND, no del backend.
- Regla:
  - La regla `/storage` en `proxy.conf.json` debe existir junto a la de `/api` — si alguien recrea ese archivo, tiene que llevar las dos.
  - Toda plantilla que muestre una imagen del backend debe pasar el valor por el pipe `imagenUrl` (`shared/pipes/imagen-url.pipe.ts`): `[src]="p.imagen_url | imagenUrl"`. El pipe normaliza absoluta / `/storage/x` / `productos/x` / `public/x` a una URL pedible.
  - Alternativa de fondo (evita tocar plantillas): que el backend exponga la URL ya absoluta con `Storage::url()` en `ProductoResource` y `ExtraResource`. Si se toma este camino, el pipe igual es inofensivo (deja pasar las absolutas tal cual).
  - Requiere `php artisan storage:link` hecho una vez en el backend, o `/storage/**` no resuelve a `storage/app/public/`.
- Fecha: 2026-08-05

### EF-18 — Cambios en el contrato de Analíticas no se ven por la caché de 30 min del backend
- Qué pasó: dos veces seguidas (al agregar `ingresos` y luego `imagen_url` a `top_productos`) el campo nuevo no aparecía en el frontend aunque el código del backend ya estaba correcto. Se perdió tiempo buscando el bug en el frontend.
- Causa: `AnaliticasService::resumen()` envuelve la respuesta en `Cache::remember(..., 30 min)` con clave por instancia/sucursal/granularidad/período. Tras cambiar la FORMA de la respuesta, el backend sigue sirviendo los payloads viejos (sin el campo nuevo) hasta que cada clave expira. Como hay una clave por período visitado, quedan muchos payloads viejos conviviendo.
- Regla: después de cualquier cambio en la estructura de la respuesta de analíticas hay que limpiar la caché ANTES de concluir que el frontend está mal. `php artisan cache:clear`, o —si no hay un PHP de la versión que pide el proyecto— con `CACHE_STORE=file` basta con borrar los archivos de `storage/framework/cache/data/` (dejando el `.gitignore`). Para diagnosticar sin adivinar: `grep` del campo nuevo dentro de esos archivos; si no aparece en ninguno, el problema es la caché y no el frontend.
- Fecha: 2026-08-05

### EF-17 — Tooltip fantasma del navegador por pasar `title` como atributo estático a un componente
- Qué pasó: en Analíticas aparecía una caja negra flotante con el texto "Tendencia de ventas" encima del gráfico vecino ("Horas pico"), en una posición que no correspondía a ningún tooltip propio de ese gráfico.
- Causa: `<admin-section-card title="Tendencia de ventas">` — el componente declara `@Input() title`, pero al escribirlo como atributo ESTÁTICO Angular además lo deja reflejado como atributo HTML nativo `title` en el elemento host. `title` es el atributo que dispara el tooltip nativo del navegador al hacer hover, así que la caja negra la dibujaba el navegador, no la app.
- Regla: cuando un `@Input()` se llama igual que un atributo HTML nativo con comportamiento propio (`title`, `hidden`, `draggable`, `tabindex`…), el componente debe neutralizarlo en el host: `host: { '[attr.title]': 'null' }` (así quedó `admin-section-card.component.ts`). Si aparece un tooltip/comportamiento raro que no está en el código de la app, revisar primero si es comportamiento nativo del navegador.
- Fecha: 2026-08-05

### EF-11 — Sesión compartida entre pestañas por usar `@ionic/storage-angular` (IndexedDB) para el token
- Qué pasó: con el panel admin abierto en una pestaña, al registrar un cliente nuevo en OTRA pestaña del mismo navegador y luego recargar (F5) la pestaña del admin, esta se rompía con `403 Forbidden` ("No tenés permiso para realizar esta acción.") en vez de mostrar el panel.
- Causa: `TokenStorageService` (y, con el mismo patrón, `SuperAdminAuthService`) guardaban el token/usuario con `@ionic/storage-angular`, cuyo driver por defecto es IndexedDB — un almacén a nivel de ORIGEN, compartido por todas las pestañas/ventanas abiertas del mismo `localhost:puerto`, no aislado por pestaña. `AuthService.init()` (llamado por `APP_INITIALIZER` en cada carga/recarga de página) relee el token desde ese store compartido. Como registrar/loguear en cualquier pestaña sobreescribe las mismas claves (`auth_token`/`auth_user`), la ÚLTIMA sesión iniciada en CUALQUIER pestaña ganaba para TODAS las pestañas la próxima vez que alguna de ellas recargara.
- Regla: para que cada pestaña de una SPA (Angular/Ionic) mantenga una sesión de usuario independiente (necesario en este proyecto porque un mismo navegador puede tener abiertos a la vez, por ejemplo, el panel admin y el modo kiosko/cliente), la persistencia del token de sesión debe usar un storage AISLADO por pestaña — `sessionStorage` nativo del navegador (se pierde al cerrar la pestaña, pero sobrevive a un F5 de esa misma pestaña), NO un store compartido como IndexedDB/localStorage. Esto es específico de SESIONES DE USUARIO — datos que sí deben compartirse entre pestañas (ej. el carrito de compras en `carrito-storage.service.ts`) pueden seguir usando `@ionic/storage-angular` sin problema, esa es una decisión de producto distinta.
- Fecha: 2026-07-23

### EF-01 — Card de auth omitida y sin centrar/responsive en PC
- Qué pasó: las pantallas login/register quedaron sin la tarjeta blanca redondeada del mockup. Al agregarla, en PC no se centraba (quedaba pegada arriba con espacio muerto) y el contenido excedía la altura de pantalla (scroll, botón inferior cortado). Tomó varios turnos resolverlo.
- Causa:
  1. Al portar el mockup React/Tailwind a Ionic se omitió el contenedor (card) del original; solo se replicó el formulario.
  2. `margin: auto` NO centra vertical si el contenido es más alto que el viewport. El logo a 180px + espaciados grandes hacían overflow, así que no sobraba espacio para centrar → se veía "exactamente igual" aunque el CSS sí cambiaba.
- Regla (no romper):
  - Al portar mockup → Ionic, replicar TODOS los contenedores del original (cards, marcos), no solo el form. Revisar responsive en PC y móvil ANTES de dar por cerrada una pantalla. Ver memoria `fidelidad-visual-responsive`.
  - Patrón de centrado robusto (NO depender solo del shadow `::part`; usar wrapper light-DOM):
    ```html
    <ion-content class="auth-content"><div class="auth-center"><div class="auth-wrap">...</div></div></ion-content>
    ```
    ```scss
    .auth-content::part(scroll) { display: flex; flex-direction: column; }
    .auth-center { flex: 1; min-height: 100%; display: flex; flex-direction: column; padding: 24px 0; box-sizing: border-box; }
    .auth-wrap { margin: auto; max-width: 440px; width: calc(100% - 32px); }
    ```
    `flex:1` cubre el caso flex-parent y `min-height:100%` el caso no-flex → el wrapper siempre llena el alto y `margin:auto` centra. Si excede, scroll limpio.
  - REGLA CLAVE: `margin:auto` solo centra si el contenido CABE en el viewport. Si una pantalla (ej. register con 4 campos) es más alta que el viewport del usuario, NO se puede centrar → hay que compactarla. Para adaptarse a cualquier alto, usar media queries por ALTURA que reduzcan logo/márgenes/inputs:
    ```scss
    @media (max-height: 780px) { /* compacta logo, márgenes, btns */ }
    @media (max-height: 640px) { /* logo mínimo, oculta tagline */ }
    ```
  - DIAGNÓSTICO de zoom/escala: si en una captura el card se ve MÁS ANCHO que su `max-width` (ej. 600px cuando max-width es 440px), el usuario tiene zoom/escala (~135%) → su viewport CSS real es menor que la captura en píxeles (ej. 1037px/1.35 ≈ 768px de alto). Calcular el viewport real ANTES de asumir que algo "no aplica".
  - El contenido del card debe caber en un viewport típico para que el centrado sea visible: logo ~120px (no 180), espaciados moderados (~16–24px). En pantallas bajas hace scroll sin recortar (los `margin:auto` colapsan).
  - Antes de culpar al cache/refresh: verificar el CSS realmente servido con `curl http://localhost:8100/styles.css` (grep de la regla). Si la regla está servida, el problema es de layout, no de build.
- Fecha: 2026-06-29

### EF-02 — ionic serve no detecta módulos nuevos creados en caliente
- Qué pasó: al crear ~56 archivos nuevos (`src/app/admin/`) con `ionic serve` corriendo, el rebuild incremental tiraba `TS2307: Cannot find module` para todos los módulos/páginas nuevas, de forma persistente (no se autocorregía con más rebuilds).
- Causa: el watcher incremental de `@ngtools/webpack` no vuelve a escanear el `include` de `tsconfig.json` cuando aparecen muchos archivos nuevos de golpe mientras el dev server ya está arriba (limitación conocida de compilación incremental, no un error en el código).
- Regla: si una tarea (propia o de un subagente) crea módulos/archivos NUEVOS con `ionic serve`/`ng serve` ya corriendo, matar el proceso (`taskkill` sobre el árbol, o Ctrl+C) y levantarlo de nuevo en frío. No esperar a que el rebuild incremental lo resuelva solo. Verificar el output del server tras el reinicio (`Compiled successfully`) antes de dar el cambio por bueno.
- Fecha: 2026-07-03

### EF-06 — Login unificado (superadmin) rechaza el "usuario", solo acepta email
- Qué pasó: al probar el superadmin de prueba (`SuperAdminTestSeeder`: usuario `super` / email `super@rooster.com`) en la pantalla de login normal (`/login`), tipear `super` (el usuario) dispara "Completá correo y contraseña." y nunca llega a pegarle al backend.
- Causa: `login.page.ts` usa un único `FormGroup` con campo `email` + `Validators.email`, y `AuthService.loginUnificado()`/`LoginBody` solo mandan `{ email, password }`. El backend (`LoginRequest`) también exige `'email' => ['required','email']` en `/api/login`. El campo `usuario` de `superadministradores` (pensado para poder loguear por usuario o correo, como sí hace el endpoint aislado `/api/superadmin/login` con el campo `login`) nunca se conecta a este formulario unificado — el "login unificado" del frontend en realidad solo soporta correo.
- Regla (pendiente de decidir/corregir por el compañero): si la intención es que el login unificado acepte usuario O correo (como el diseño de `ARQUITECTURA-SUPERADMIN-MULTITENANT.md` sugiere para superadmin), hay que: (1) quitar `Validators.email` del campo o relajarlo a un validador custom que acepte ambos formatos, (2) renombrar/ampliar `LoginBody` a algo como `{ login, password }`, y (3) que el backend (`LoginRequest`/`AuthService`) resuelva por `email` O `usuario` igual que ya hace `/api/superadmin/login`. Mientras tanto, para probar el superadmin de prueba desde el login normal, usar el **correo** `super@rooster.com`, no el usuario `super`.
- Fecha: 2026-07-16

### EF-07 — Formulario de Sucursal con validador desalineado del backend (`direccion`)
- Qué pasó: al construir el modal de crear/editar Sucursal (Configuración), el `FormGroup` reactivo dejó `direccion` sin `Validators.required`. El backend (construido en paralelo por otro subagente) descubrió que `sucursales.direccion` es NOT NULL en la BD real y la hizo obligatoria server-side. Sin el validador en el frontend, un admin podía dejar el campo vacío y recién enterarse del error al hacer submit (422), sin aviso previo en el form.
- Causa: el contrato de API acordado entre ambos subagentes (backend/frontend) en paralelo asumía `direccion` opcional; el backend corrigió esa suposición al chequear la columna real, pero el frontend ya se había construido contra el contrato original y nadie sincronizó el cambio hasta la revisión manual posterior.
- Regla: cuando 2 features se construyen en paralelo (backend + frontend) sobre un contrato acordado de antemano, y uno de los dos lados detecta que el contrato original no era correcto (ej. una columna resulta NOT NULL), es obligatorio verificar el lado espejo antes de dar la tarea por cerrada — un contrato que cambió a mitad de camino en un lado y no en el otro es un bug silencioso (pasa validación de compilación, falla recién en runtime). `sucursalForm.direccion` ahora tiene `[Validators.required, Validators.maxLength(200)]` + asterisco en el label.
- Fecha: 2026-07-17

### EF-04 — Chart.js dentro de páginas Ionic muestra animaciones bugeadas/trabadas
- Qué pasó: al integrar Chart.js en el módulo admin "Clientes" (`clientes-top-chart.component.ts`) dentro de `ion-content`, la animación de las barras se veía trabada/reiniciada múltiples veces, como si el gráfico se redibujara en loop durante la transición de entrada de la página.
- Causa: el `ResizeObserver` interno de Chart.js reacciona a los cambios de tamaño transitorios del contenedor durante la transición de página de Ionic (que usa `transform` para la animación de ruta), interpretando esos cambios como un resize real del canvas y reiniciando la animación del gráfico en cada frame de la transición.
- Regla:
  - Al usar Chart.js dentro de páginas Ionic (cualquier contenido con transiciones de ruta o `ion-content`), setear `options.animation = false` para deshabilitar la animación del gráfico (el chart aparece completo de golpe, sin transición incremental).
  - Setear `options.resizeDelay = 200` (o 100–300ms) para que el ResizeObserver espere a que el resize se "estabilice" antes de redibujar (útil si se necesita mantener animación en otros contextos).
  - La combinación de ambos (`animation: false` + `resizeDelay`) es el fix completo para páginas Ionic con transiciones.
  - Nota de contexto: Ionic no trae ninguna librería de gráficos — todo lo existente en el proyecto antes de Chart.js (`bar-chart`, `donut-chart`, `progress-bar`, `mini-bar`, `area-chart`) es CSS/SVG hecho a mano, cero dependencias. `chart.js` es la primera dependencia de charting real del proyecto, agregada como excepción puntual (usuario pidió explícitamente Chart.js para el componente de Top 5 clientes).
- Fecha: 2026-07-18

### EF-08 — Centrado solo horizontal no basta: contenido angosto pegado arriba en desktop
- Qué pasó: se aplicó `.pedir-narrow { max-width:600px; margin:auto }` a las vistas Carrito/Checkout de `pedir.page` para centrarlas en desktop (siguiendo EF-01), pero el usuario reportó que "seguía descuadrado" — con captura real (Puppeteer) se confirmó que SÍ estaba centrado horizontalmente, pero pegado ARRIBA del viewport con un vacío enorme abajo en pantallas altas.
- Causa: `margin:auto` con `max-width` centra horizontalmente sin necesitar flex (eso funcionaba bien), pero el centrado VERTICAL requiere que el padre sea un flex container en columna con altura definida — exactamente la mitad del patrón EF-01 que faltó copiar (`::part(scroll){display:flex;flex-direction:column}` + el wrapper como flex item con `flex:1;min-height:100%`).
- Regla: al centrar contenido angosto de tipo "card" dentro de `ion-content` en viewports altos, SIEMPRE aplicar el patrón EF-01 completo (los 3 niveles: `::part(scroll)` flex-column + wrapper `flex:1`/`min-height:100%` + centrado con `align-items`/`justify-content` o `margin:auto` en ambos ejes), nunca solo la parte horizontal. Verificar con una captura real en un viewport alto (900px+), no asumir que "centrado" = "se ve bien" sin mirarlo.
- Fecha: 2026-07-19

### EF-09 — Verificación visual real con Puppeteer + Chrome del sistema (sin Playwright, sin descargar navegador)
- Qué pasó: hasta esta sesión, el "click-through en navegador" quedaba pendiente en casi todos los cierres de sesión porque no había forma rápida de verificar visualmente. Se resolvió instalando `puppeteer-core` (NO `puppeteer` completo, que descarga ~300MB de Chromium) apuntando al Chrome ya instalado del sistema.
- Regla: para verificar visualmente sin depender de que el usuario mande capturas, `npm install --no-save puppeteer-core` (dentro del repo frontend, para que Node resuelva el módulo) + `puppeteer.launch({ executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', headless: 'new' })`. Sirve para login real (llenar formulario + submit), navegar rutas, clickear elementos por selector CSS, y `page.screenshot()`. Al terminar: desinstalar (`npm uninstall puppeteer-core`) y borrar los scripts/capturas temporales — no debe quedar rastro en el repo ni en `package.json`/`package-lock.json`.
- Fecha: 2026-07-19

### EF-10 — Card de producto en el carrito más angosta que la caja del total ("escalón" visual)
- Qué pasó: tras centrar el carrito (EF-08), el usuario reportó que la caja del total seguía "no simétrica" respecto a la card del producto arriba. Medido con Puppeteer (`getBoundingClientRect()`, no a ojo): `.cart-footer` ocupaba exactamente 420–1020px (600px de ancho), pero `.cart-item` (la card visible del producto) solo 452–988px (536px) — 32px de diferencia en cada lado.
- Causa: `.cart-list` tenía `padding: 0 32px` en desktop, lo que empujaba la card del producto 32px hacia adentro por cada lado. `.cart-footer` no tiene ese padding lateral en su propio contenedor (su fondo blanco llega hasta el borde de los 600px), así que las dos "cards" apiladas no coincidían en ancho, generando un escalón visual entre ambas.
- Regla: cuando dos secciones apiladas dentro del mismo wrapper centrado deben verse como una sola tarjeta continua, medir con el navegador (`getBoundingClientRect()`, no asumir a ojo) que AMBAS tienen el mismo ancho real — un `max-width` igual en las dos NO alcanza si una tiene padding lateral adicional que la otra no tiene.
- Fecha: 2026-07-19

### EF-11 — Componentes NUEVOS: "is missing from the TypeScript compilation" con `ng serve` corriendo
- Qué pasó: al agregar 7 componentes nuevos (`mi-cuenta/pages/*.page.ts`) y referenciarlos desde el módulo/routing, el `ng serve` YA en ejecución tiró `Module build failed ... is missing from the TypeScript compilation. Please make sure it is in your tsconfig` para cada archivo nuevo, más una cascada de errores FALSOS en el resto del módulo (`No pipe found with name 'crcCurrency'`, `Object is possibly 'null'`) que no eran errores reales.
- Causa: el watcher de `ng serve` no siempre incorpora al TS program, en caliente, archivos `.ts` nuevos referenciados por un módulo lazy. Como el módulo no compilaba, el type-checker de plantillas reportaba el pipe y el null como "errores" — puras cascadas.
- Regla: tras crear archivos de **componente nuevos**, **reiniciar `ng serve`** ANTES de creer que hay errores reales de plantilla. Si tras reiniciar compila limpio, los errores de pipe/null eran cascada de la resolución de módulos. No perder tiempo "arreglando" el pipe/null primero.
- Fecha: 2026-07-24

### EF-12 — Tablas del admin: scroll "diagonal"/inestable en móvil (touch)
- Qué pasó: en móvil, TODAS las tablas del admin (`.admin-table-wrap`/`.of-table-wrap`) se "movían para todos lados" al arrastrarlas con el dedo — se podían desplazar en diagonal, sin la sensación de scroll estable de una tabla normal.
- Causa: cada contenedor de tabla era **un solo contenedor 2D** — scrolleaba en los DOS ejes a la vez: `max-height: 60vh` (scroll vertical interno, para el alto fijo) **y** la tabla con `min-width: max-content`/celdas `nowrap` (scroll horizontal interno). Un único elemento que scrollea en X **y** Y, en touch, se arrastra libremente en diagonal. `touch-action` NO puede "bloquear al eje dominante" en un solo contenedor (solo permite `pan-x`, `pan-y` o ambos; no "uno a la vez según el gesto").
- Solución (CSS puro, sin JS, sin tocar el HTML): **dos contenedores 1D anidados**, cada uno de un solo eje. El navegador enruta nativamente cada gesto al scroller de su eje → nunca diagonal, y se conserva el alto fijo:
  ```scss
  @media (max-width: 767px) {
    .admin-table-wrap {           // externo = SOLO vertical (alto fijo)
      max-height: 60vh; overflow-y: auto; overflow-x: hidden;
    }
    .admin-table {                // tabla como bloque = SOLO horizontal
      display: block; width: max-content; max-width: 100%; min-width: 0;
      overflow-x: auto; overflow-y: hidden;
    }
  }
  ```
  Claves: (1) `min-width: 0` en la tabla es OBLIGATORIO — si queda `min-width: max-content`, el bloque no se topa a `max-width:100%` y no hay overflow horizontal que scrollear. (2) `display:block` en `<table>` mantiene las columnas alineadas (tabla anónima interna; mismo truco que las tablas de GitHub). (3) el encabezado `sticky` puede dejar de pegarse en móvil — es aceptable, comportamiento de tabla normal.
- Ojo con la especificidad: los estilos de componente Ionic quedan scoped (`[_ngcontent-xxx]`), así que `.of-table*` (definido en `ofertas.page.scss`) NO se puede sobreescribir desde `global.scss` — el fix de esas tablas hay que ponerlo EN `ofertas.page.scss`. Las `.admin-table*` sí viven solo en `global.scss`.
- Regla: NUNCA dejar un contenedor que scrollee en los dos ejes a la vez en móvil. Alto fijo + scroll horizontal = separar en dos contenedores 1D anidados (vertical afuera, horizontal adentro/tabla-bloque). Verificar en teléfono real.

### EF-13 — `IonicRouteStrategy` cachea páginas: `ngOnInit`/`ngOnDestroy` NO son confiables para timers ni para republicar templates
- Qué pasó: en Analíticas, el `setInterval` del contador de caché se seguía multiplicando cada vez que el usuario salía del módulo y volvía a entrar (el navegador terminaba "colgándose" con suficientes visitas). Por separado, el botón del header (contador de caché) desaparecía al volver de otro módulo aunque el timer ya estuviera arreglado.
- Causa: el shell admin usa `{ provide: RouteReuseStrategy, useClass: IonicRouteStrategy }` (`app.module.ts`) — Ionic **cachea las instancias de página** en vez de destruirlas al navegar (para transiciones fluidas tipo app nativa). Eso significa que `ngOnInit` **no vuelve a ejecutarse** al reingresar a una página ya visitada, y `ngOnDestroy` **no siempre se dispara** al salir. Cualquier `setInterval` armado en `ngOnInit` sin limpieza defensiva se acumula en cada reingreso (cada visita crea un timer nuevo sin matar el anterior). Y cualquier lógica que solo corre una vez en `ngOnInit` (como publicar un `<ng-template adminHeaderActions>` en `AdminHeaderService`, ver sesión 2026-07-28 de `HiloActualFront.md`) nunca se repite, así que el header queda "pegado" en lo último que se publicó antes de salir.
- Solución real (dos capas, cada una donde corresponde):
  1. **Timers/lógica por-página**: usar los hooks de **ciclo de vida de Ionic** (`ionViewWillEnter`/`ionViewWillLeave`, interfaces `ViewWillEnter`/`ViewWillLeave` de `@ionic/angular`) en vez de `ngOnInit`/`ngOnDestroy` — estos SÍ se disparan en cada entrada/salida real, sin importar el cacheo. Además, limpiar el timer existente ANTES de crear uno nuevo (`detenerTimer()` al inicio de `iniciarTimer()`) como defensa adicional.
  2. **Portal de acciones del header** (`[adminHeaderActions]`, patrón compartido por varias páginas admin): el fix correcto vive en la **directiva compartida** (`admin-header-actions.directive.ts`), no en cada página. Ionic dispara `ionViewWillEnter`/`ionViewWillLeave` como **eventos DOM reales** sobre el elemento `.ion-page` (no solo como interfaz de componente) — la directiva escucha ese evento (`elRef.nativeElement.parentElement?.closest('.ion-page')`) y republica el template ahí, arreglando el bug para TODAS las páginas que usan `adminHeaderActions` de una sola vez.
- Nota de reconciliación: en esta misma sesión se había parcheado el bug del header **a mano, dentro de `analiticas.page.ts`** (con `@ViewChild` + llamadas manuales a `setActions()`/`clearActions()`) antes de hacer `git pull` — un compañero ya lo había arreglado de forma genérica en la directiva compartida (commit `f89811e`, mismo día). Tras el pull, se revirtió el parche local de Analíticas para volver a usar `<ng-template adminHeaderActions>` simple, evitando dos soluciones duplicadas para el mismo bug.
- Regla: en páginas Ionic/Angular con `IonicRouteStrategy`, **nunca** asumir que `ngOnInit` corre una sola vez por "primera carga" ni que `ngOnDestroy` se dispara siempre al salir — para timers, listeners o cualquier "efecto que se repite en cada visita", usar los hooks de vista de Ionic (`ionViewWillEnter`/`ionViewWillLeave`/`ionViewDidEnter`/`ionViewDidLeave`), o si el problema vive en un patrón COMPARTIDO por varias páginas (como una directiva), arreglarlo ahí y no parchear cada página por separado.
- Fecha: 2026-07-29
- Fecha: 2026-07-29

### EF-14 — `<ion-modal [isOpen]>` (sheet) deja backdrop huérfano al navegar → congela la app
- Qué pasó: el prompt de reseñas (`resena-prompt.component`, vive en el shell de tabs) usaba `<ion-modal [isOpen]="isOpen" [breakpoints]="[0,1]">`. Tras pasar por varias secciones, la app quedaba **congelada**: no se abría NADA (ni el detalle de un producto, ni otros modales) — un backdrop invisible interceptaba todos los clics "por siempre".
- Causa: `ion-modal` es un overlay a nivel de `ion-app` (no vive dentro de una tab page, así que no se oculta al cambiar de tab). El binding declarativo `[isOpen]` con sheet + los cambios de ruta dejan el `ion-backdrop` en el DOM aunque el contenido ya no esté visible → captura todos los eventos de puntero. Es un problema conocido de `[isOpen]` + sheet al combinarse con navegación.
- Solución: reemplazar el `ion-modal` por el **patrón de overlay `*ngIf` propio** que ya usa todo el proyecto (`.xxx-modal` = `position:fixed; inset:0` + `__backdrop` + panel, todo bajo un `*ngIf="isOpen"`). Al poner `isOpen=false` el nodo entero se **desmonta** → es imposible que quede un backdrop pegado. Se mantiene la misma UI (bottom-sheet, tap en el backdrop = cerrar).
- Regla: en este proyecto, para modales propios preferir el overlay `*ngIf` (se desmonta al cerrar) en vez de `<ion-modal [isOpen]>`, sobre todo si el modal vive en un componente persistente (shell de tabs) — un backdrop de `ion-modal` que no se limpia bloquea TODA la app, no solo su pantalla.
- Fecha: 2026-07-30

### EF-15 — Flex item con `overflow-x:auto` sin `min-width:0` → ancho "infinito" y sin scroll
- Qué pasó: al reestructurar el encabezado del menú (`pedir`) y meter las **categorías** (chips con scroll horizontal) dentro de un contenedor flex-column (`.pedir-menu-head__tools`), en móvil el buscador se hacía de **ancho infinito** (se salía de la pantalla) y las categorías **perdían el scroll horizontal** (los chips se salían sin poder desplazarlos).
- Causa: un flex item tiene `min-width: auto` por defecto = **min-content**. Para una fila de chips con `flex-shrink:0`, el min-content es la suma de TODOS los chips (no envuelven) → el item se estira a ese ancho, excede el viewport ("infinito") y su `overflow-x:auto` nunca dispara (el item está dimensionado a su contenido, no acotado por el contenedor).
- Solución: `min-width: 0` en el contenedor flex (`__tools`) **y** en el item que scrollea (`.pedir-cats`). Así el item respeta el ancho del contenedor (viewport) y su `overflow-x:auto` vuelve a desplazar los chips.
- Regla: cualquier elemento con `overflow-x:auto`/`overflow:hidden` que sea (o esté dentro de) un flex/grid item necesita `min-width:0` en la cadena — sin eso, `min-width:auto` lo dimensiona a min-content y rompe tanto el ancho como el scroll. Es la misma familia de `min-width:0`/`max-width:100%` que ya se usó para el chart del dashboard.
- Fecha: 2026-07-30

### EF-16 — `position:fixed` dentro de `ion-content` se mide desde el content, no desde el viewport
- Qué pasó: el FAB del carrito (`.pedir-fab`, `position:fixed`) "salía muy arriba" en móvil. Se probó `bottom:80px`, `bottom:72px`, `72px + env(safe-area)` y seguía alto; recién `bottom:16px` lo dejó bien.
- Causa: un elemento `position:fixed` que es hijo (slotted) de `<ion-content>` NO se posiciona respecto al viewport, sino respecto al **`ion-content`** (su bloque contenedor), cuyo borde inferior ya está **por encima del tab bar**. Así que un `bottom` grande lo sube muchísimo (se le suma la altura del tab bar) y sumar `env(safe-area)` lo empeora (doble conteo). El offset se mide desde el tope del tab bar, no desde el fondo de la pantalla.
- Regla: para posicionar un FAB/overlay `position:fixed` dentro de `ion-content`, usar offsets **pequeños** (ej. `bottom:16px`) medidos desde el borde del content — no razonar como si fuera relativo al viewport ni sumar el alto del tab bar ni el safe-area. (Relacionado: para tapar/despejar el tab bar, mejor ocultarlo por completo con una clase en `body` que pelear con z-index, ver modo inmersivo del carrito en `HiloActualFront.md`.)
- Fecha: 2026-07-30
