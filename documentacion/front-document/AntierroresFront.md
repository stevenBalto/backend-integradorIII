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
