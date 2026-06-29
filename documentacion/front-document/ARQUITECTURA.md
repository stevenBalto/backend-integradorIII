# ARQUITECTURA — Frontend Rooster Pizza & Grill

> **Para qué sirve este documento:** es el contrato de estilo y arquitectura del frontend. Entregáselo completo a cualquier IA o desarrollador que vaya a crear, modificar o extender pantallas. Regla de oro: **toda pantalla/módulo nuevo debe ser indistinguible de los existentes.** Se copia el patrón vigente.
>
> **Origen y alcance:** este documento adapta una arquitectura de referencia (originalmente **React + Vite + Bootstrap + axios**) al stack real del proyecto: **Ionic + Angular**. La **base conceptual** (cliente HTTP centralizado con interceptores, capas estrictas, validators puros, contrato de datos uniforme, reglas UX móviles) se mantiene; los **mecanismos** se implementan con Ionic/Angular. Lo que no aplica (React/hooks/Bootstrap, contrato del backend de iglesia, setup obligatorio por tenant, dominio cultos) se descarta.

---

## 0. Instrucción directa a la IA / dev que reciba este documento

Vas a trabajar sobre un frontend **Ionic + Angular** para Rooster Pizza & Grill. Antes de escribir una sola línea:

1. **Respeta el patrón existente al 100%.** Si dudás, abrí un módulo ya hecho y replicalo: mismas capas, mismos nombres, mismo manejo de errores.
2. **Toda la red pasa por una sola capa.** Nunca llames al backend directo desde un componente. Se usa el `HttpClient` de Angular + un `HttpInterceptor` único (token, headers, manejo global de sesión/errores) + servicios API por dominio.
3. **No rompas el contrato.** Las rutas, el formato de respuesta JSON (API Resources de Laravel) y los nombres de roles los define el backend. La UI se adapta a ellos, no al revés.
4. **Respeta la identidad visual cerrada.** Reglas de color, fondo y layout en `front-document/ReglasUX.md` y `front-document/guiaMDFrontend.md`. App cliente NUNCA fondo negro; admin 70-20-10.
5. **TypeScript estricto, español de dominio.** Tipado estricto, interfaces para cada Resource del backend, nombres de dominio en español (`PedidoService`, `cargarProductos()`).

---

## 1. Identidad del proyecto

| Atributo | Valor |
|---|---|
| **Nombre** | Frontend Rooster Pizza & Grill (Proyecto Integrador III, UTN Guanacaste) |
| **Tipo** | App híbrida (SPA cliente puro): móvil (Android prioritario, iOS tentativo) + web. Sin lógica de servidor; la fuente de verdad es el backend Laravel. |
| **Tres modos** | App cliente (4 tabs), Panel admin (role-gated), Kiosko (autoservicio en local). |
| **Backend** | Laravel + PostgreSQL (`backend-integradorIII`), solo por API REST. Repos independientes. |
| **Dominio** | Pedidos comer aquí / para llevar, catálogo (pizzas, grill, pastas, bebidas), ofertas y cupones, puntos de fidelidad, cuenta de cliente, reseñas. Sin delivery. Pago solo en caja. |

Contexto general del producto: ver `documentacion/ContextoGeneral.md`.

---

## 2. Stack técnico

| Capa | Tecnología |
|---|---|
| UI / framework | **Ionic** + **Angular** (componentes `IonPage`, `IonContent`, `IonItem`, `IonInput`, `IonModal`, `IonToast`, `IonTabs`, `IonMenu`...) |
| Lenguaje | TypeScript (tipado estricto) |
| Cliente HTTP | **Angular `HttpClient`** + `HttpInterceptor` (RxJS `Observable`) |
| Estado/lógica | Servicios Angular inyectables + RxJS |
| Ruteo / guards | Angular Router + `CanActivate` guards |
| Empaquetado nativo | **Capacitor** (Android prioritario; web también) |
| Almacenamiento | `@capacitor/preferences` / secure storage para el token (no `localStorage` plano) |
| Build | el bundler de Ionic/Angular |

* **El cliente HTTP es `HttpClient` + un interceptor único.** Toda comunicación con el backend pasa por ahí. No se usa `fetch` suelto ni `HttpClient` directo desde componentes.
* La app consume **API Resources** de Laravel; nunca asume campos no documentados (ante duda, `consistency-checker`).

---

## 3. Cómo se conecta como cliente (lo más importante)

### 3.1 Interceptor HTTP único (token + manejo global)
Equivalente al "axios centralizado con interceptores" de la referencia. En Angular se implementa con un `HttpInterceptor` registrado en root:

* **Request:** inyecta `Authorization: Bearer <token>` (leído del almacenamiento seguro) y headers comunes. Si el token se guarda async (Capacitor Preferences), el interceptor lo resuelve antes de emitir.
* **Response/error:** maneja casos globales en un solo lugar:
  - **401** (token inválido/sesión expirada), excepto en login: limpia sesión y redirige a login. Mapea mensajes técnicos a copy amigable.
  - **403** (rol/sucursal insuficiente): bloquea y notifica sin exponer detalle técnico.
  - **422** (validación): el cuerpo trae `{ message, errors }`; se propaga para pintar errores por campo en el formulario.
  - **Sin respuesta / red** (backend caído): error de conexión amigable.

```ts
@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  constructor(private auth: AuthService, private router: Router, private notify: NotifyService) {}

  intercept(req: HttpRequest<unknown>, next: HttpHandler): Observable<HttpEvent<unknown>> {
    return from(this.auth.getToken()).pipe(
      switchMap(token => {
        const authReq = token
          ? req.clone({ setHeaders: { Authorization: `Bearer ${token}` } })
          : req;
        return next.handle(authReq).pipe(
          catchError((err: HttpErrorResponse) => {
            if (err.status === 401 && !req.url.includes('/login')) {
              this.auth.limpiarSesion();
              this.router.navigateByUrl('/login');
            }
            if (err.status === 0) {
              this.notify.error('Error de conexión con el servidor.');
            }
            return throwError(() => err);
          })
        );
      })
    );
  }
}
```

### 3.2 baseURL y entornos (web vs dispositivo)
* La URL base vive en `environment.ts` (`apiBaseUrl`). En **web/dev** se puede usar el proxy de Angular (`proxy.conf.json`) hacia el backend en XAMPP. En **dispositivo (Capacitor)** NO hay proxy: `apiBaseUrl` debe ser la **URL absoluta** del backend y el backend debe resolver **CORS**.
* Para evitar problemas de CORS en device, se puede usar el HTTP nativo de Capacitor; mantener el mismo contrato.

### 3.3 Contrato JSON canónico (lo define el backend Laravel)
La UI ramifica por **status HTTP** y por el cuerpo de los API Resources. (No se usa el envoltorio `{exito,mensaje,datos}` de la referencia: ese era de otro backend.)

* **Recurso único:** `{ "data": { ... } }`
* **Colección paginada:** `{ "data": [...], "links": {...}, "meta": { "current_page", "last_page", "per_page", "total" } }`
* **Error de validación (422):** `{ "message": "...", "errors": { "campo": ["..."] } }`
* Estados/enums string predecibles (ej. estado de pedido: `pendiente`, `en_proceso`, `listo`, `entregado`, `cancelado`). La UI compara contra esos strings exactos.

> Para cada Resource del backend se define una **interface TypeScript**. Si un campo no coincide (nombre/tipo), se escala a `consistency-checker`; no se asume.
>
> Campos reales (snake_case) del backend: `sucursal_id`, `precio_base`, `disponible`, `puntos_balance`, `precio_unitario`, `modalidad`, `estado`, etc. Ver `back-document/bd-doc/rooster_pizza_bd.sql` y `back-document/ARQUITECTURA.md`.

### 3.4 Capa de servicios API por dominio
Un servicio Angular **plano por dominio** que solo define endpoints (transporte). Nada de lógica de UI:

```ts
@Injectable({ providedIn: 'root' })
export class ProductoApi {
  private base = `${environment.apiBaseUrl}/productos`;
  constructor(private http: HttpClient) {}

  listar(params: ListarProductosParams): Observable<{ data: Producto[]; meta: PaginacionMeta }> {
    return this.http.get<{ data: Producto[]; meta: PaginacionMeta }>(this.base, { params: toHttpParams(params) });
  }
  obtener(id: number): Observable<{ data: Producto }> {
    return this.http.get<{ data: Producto }>(`${this.base}/${id}`);
  }
  crear(body: CrearProductoBody): Observable<{ data: Producto }> {
    return this.http.post<{ data: Producto }>(this.base, body);
  }
}
```

Existe uno por dominio: `AuthApi`, `ProductoApi`, `PedidoApi`, `CuponApi`, `ClienteApi`, `PuntosApi`, `ReseñaApi`, etc.

> **Patrón clave:** la API = solo transporte (URL + verbo + tipo). La lógica vive en los **servicios de feature**, no en los componentes.

---

## 4. Estructura de carpetas y responsabilidades

```
src/app/
├── core/                  # Servicios singleton, interceptores, guards, modelos globales
│   ├── http/              # AuthInterceptor, ErrorInterceptor
│   ├── auth/              # AuthService (sesión), guards (rol, modo)
│   ├── api/               # Servicios API por dominio (solo endpoints HttpClient)
│   ├── models/            # Interfaces TS de los Resources del backend
│   └── config/            # constants.ts, environment, eventos (RxJS Subjects)
├── shared/                # Componentes/pipes/directivas reutilizables, validators puros, utils
│   ├── ui/                # Componentes presentacionales (toast wrapper, confirm, tabla...)
│   ├── validators/        # Reglas de validación puras por formulario
│   └── utils/             # sanitizer, notify, helpers
├── features/              # Un módulo por área de dominio, agrupado por modo
│   ├── cliente/           # App cliente: home, pedir, ofertas, mi-cuenta
│   ├── admin/             # Panel admin: dashboard, pedidos, menu, cupones, usuarios...
│   └── kiosko/            # Autoservicio en local
│       └── <modulo>/
│           ├── pages/         # IonPage por ruta (orquesta: llama al service, reparte a components)
│           ├── components/    # UI presentacional (recibe @Input, emite @Output)
│           └── <modulo>.service.ts   # Lógica de negocio del módulo (estado + acciones + API)
└── app-routing.module.ts  # Rutas + guards + lazy-loading por modo/módulo
```

### Flujo de datos (unidireccional, por capas)

```
Page (IonPage)  →  FeatureService (lógica)  →  DominioApi (HttpClient)  →  Backend
     ↑                    │
     └──── @Input ────────┘   (el service expone estado vía Observable; la Page lo enlaza y reparte a Components)

Components  →  @Output (onGuardar, onCambiar...)  →  Page  →  FeatureService
```

**Regla:** la dependencia va Page → service de feature → api. Un componente nunca importa un `*Api`. Un `*Api` nunca importa un service de feature. Capas limpias y testeables.

---

## 5. Patrón de programación

### 5.1 El service de feature es el dueño de la lógica
Equivalente al "hook" de la referencia. Cada módulo tiene un servicio Angular que:
1. **Mantiene estado** (con `BehaviorSubject`/signals: lista, formulario, filtros, cargando, errores).
2. **Carga inicial** al entrar y al cambiar filtros.
3. **Acciones** (`guardar`, `editar`, `eliminar`, `exportar`...).
4. Cada acción sigue el patrón: **sanitiza → valida → llama API → notifica → recarga**.
5. Expone `Observable`s + métodos de acción que la Page consume.

Patrón estándar de una acción de guardado:
```
1. sanitizarObjeto(datos)                 // recorta espacios + limpia HTML
2. validarX(datos)                        // validator puro → { valido, errores }
3. si !valido → setErrores + toast advertencia + return
4. cargando = true
5. api.crear/actualizar(datos).subscribe({
6.   next: res => notificarExito(...) → limpiar → recargar
7.   error: err => mapearError(err) (422 → errores por campo; otro → toast)
8.   complete/finalize: cargando = false
   })
```

### 5.2 Validadores puros — `shared/validators/*`
Funciones (o Angular `Validators`/reactive forms) sin estado que reciben datos y devuelven `{ valido, errores }`. No tocan UI ni red. Validan tipos, longitudes, requeridos, reglas de formulario.

### 5.3 Sanitización — `shared/utils/sanitizer.ts`
Recorta espacios y elimina etiquetas HTML (anti-XSS básico). **Siempre se sanitiza antes de validar y antes de enviar.**

### 5.4 Notificaciones — `shared/utils/notify.ts` (sobre `IonToast`/`IonAlert`)
`notificarExito/Error/Info/Advertencia(mensaje)` y `confirmar(mensaje): Promise<boolean>` usando `ToastController` y `AlertController` de Ionic. Posibilidad de silenciar cascadas de errores cuando varias peticiones fallan en paralelo (ej. al expirar la sesión).

### 5.5 Comunicación entre módulos — RxJS
En vez de `window.CustomEvent` (React), se usa un servicio con `Subject`/`Observable` para señales globales (ej. vista activa del topbar, refrescar carrito). Suscribirse con cleanup (`takeUntilDestroyed`/`ngOnDestroy`).

### 5.6 Constantes centralizadas — `core/config/constants.ts`
Roles, modos, estados de pedido, límites de campos, formularios iniciales. Single source para magic values.

### 5.7 Lazy-loading
Cada feature/módulo se carga con `loadChildren` (lazy) en el router para reducir el bundle inicial. Exportaciones pesadas (Excel/PDF en reportes admin) con import dinámico.

---

## 6. Autenticación, sesión y roles

### 6.1 Login y token (Laravel Sanctum)
* Login envía credenciales; el backend responde con `data.token` (Sanctum) y datos del usuario.
* El token se guarda en **almacenamiento seguro** (`@capacitor/preferences` / secure storage), **no** en `localStorage` plano (evita la deuda XSS). El interceptor lo lee (async) en cada request.
* Logout invalida el token en el backend y limpia el almacenamiento local.

### 6.2 Roles y modos
Roles: `super_admin`, `admin_sede`, `cliente`. Modos de la app:
* **App cliente** (`cliente`): 4 tabs — Inicio, Carrito, Cupones, Mi cuenta. Checkout solo "Comer aquí" / "Para llevar", pago en caja.
* **Panel admin** (`admin_sede`, `super_admin`): módulos role-gated (dashboard, pedidos en tiempo real, menú, cupones, clientes, usuarios/roles, reportes, notificaciones, reseñas, sucursales, configuración).
* **Kiosko**: autoservicio en local, alta visibilidad; entrada propia (puede ser por dispositivo, sin login de usuario final).

### 6.3 Guards de ruta (equivalente a `ProtectedRoute`)
Angular `CanActivate` por rol/modo:
1. Sin sesión → redirige a login.
2. Rol no permitido → redirige al home de su rol/modo.
3. (Opcional, si en el futuro hay configuración inicial obligatoria de la sucursal, un guard puede bloquear módulos operativos; hoy no se requiere.)

El home depende del rol: `cliente` → app cliente; `admin_sede`/`super_admin` → panel admin.

### 6.4 Manejo de estados HTTP (contrato cerrado)
* **401** → sesión muerta: limpiar y redirigir a login (global, en el interceptor).
* **403** → rol/sucursal insuficiente: bloquear, notificar amigable.
* **422** → validación: pintar errores por campo desde `errors`.
* **5xx / red** → mensaje genérico de error de conexión/servidor.

---

## 7. Identidad visual y reglas UX

La identidad visual (paleta, logos, fondos, layout) **ya está cerrada**. No reinventar: ver `front-document/ReglasUX.md` y `front-document/guiaMDFrontend.md`. Resumen:

* **App cliente:** NUNCA fondo negro (negro solo para texto/iconos). Paleta cálida de marca: rojo Pantone 185C (~#E8112D), naranja, dorado, tan. Fondos crema/blanco cálido. Botones primarios rojo de marca con texto blanco.
* **Panel admin:** esquema neutral 70-20-10 (70% fondo gris-blanco, 20% tarjetas blancas, 10% acento rojo solo en nav activo, botones primarios y dato pico de gráficos). Sidebar negro. Nunca rojo en títulos/fondos de sección.
* **Kiosko:** paleta cálida coherente con app cliente, tipografía grande, contraste fuerte.

### Patrones UX estructurales portables (base, adaptados a Ionic)
Lecciones genéricas que conviene mantener como estándar:
* **Scroll de tablas en doble eje (crítico móvil):** nunca un único contenedor con `overflow-x` y `overflow-y` juntos (rompe el gesto). Usar dos wrappers (externo solo horizontal, interno solo vertical con `max-width:none`), tabla con `min-width` real, encabezado sticky dentro del wrapper vertical. En Ionic, resolver dentro de `IonContent` con CSS equivalente.
* **Formularios largos como wizard:** una sección/categoría por paso; `Guardar` solo en el último; validar el paso completo; bloquear en la sección **origen** del error; overlays (calendarios/popovers) no atrapados por el scroll interno.
* **Una sola acción visible por vez:** paneles contextuales; solo uno abierto; cierre explícito; estado activo visible.
* **Feedback de error:** error lógico inmediato → toast `advertencia` (amarillo) + resalte del campo; **no** duplicar feedback (toast + inline a la vez). Mensajes en **lenguaje de usuario final**, nunca códigos técnicos.
* **Codificación:** archivos UI siempre UTF-8; barrer mojibake y tildes/`ñ` en labels, toasts, placeholders.
* **Iconografía y responsive:** misma acción = mismo icono en todos los módulos; en móvil botones icon-only consistentes; **compactar antes de apilar**; tablas con altura fija + scroll interno, filtros a botón/modal para liberar ancho.

---

## 8. Errores que NO se pueden volver a cometer

Mantener un **catálogo de antierrores** como Definition of Done de cada pantalla: `front-document/AntierroresFront.md`. Cada error documentado costó retrabajo; revisarlos antes de cerrar una pantalla. Lecciones portables clave:

* Validar en dos capas: límite en el input (`maxlength`) + validación en service/validator.
* Distinguir `vacío` vs `0`; normalizar lo que el dominio exige.
* Mostrar `Guardar` solo cuando hay cambios reales (dirty-state por firma normalizada, no por referencia).
* Cerrar panel con cambios → confirmar descarte y restaurar snapshot.
* No exponer campos técnicos del backend en la UI; generarlos/normalizarlos internamente.
* No duplicar feedback de error; cada campo recibe solo su propio error.
* Nunca mostrar códigos técnicos al usuario; copy accionable ("qué pasó + qué hacer").
* Exportaciones: generar `.xlsx` real (no HTML renombrado); no duplicar columnas derivadas.

---

## 9. Equivalencias (de la referencia React a este proyecto Ionic + Angular)

| Concepto (referencia React) | En este proyecto (Ionic + Angular) |
|---|---|
| Instancia axios + interceptores | `HttpClient` + `HttpInterceptor` (root) |
| Capa `api/` por dominio | Servicios `*Api` inyectables (solo endpoints) |
| Hooks (`useX`) con lógica/estado | Servicios de feature con RxJS (`BehaviorSubject`/signals) |
| Context (`useAuth`, `useSetup`) | `AuthService` (+ guards) provistos en root |
| `ProtectedRoute` | Guards `CanActivate` por rol/modo |
| react-router | Angular Router con lazy-loading |
| Bootstrap + CSS propio | Componentes Ionic + tema de marca (ReglasUX/guiaMDFrontend) |
| `localStorage` token | `@capacitor/preferences` / secure storage |
| Proxy Vite `/api` | proxy Angular (web) / URL absoluta + CORS (device) |
| `window.CustomEvent` | Servicios con `Subject`/`Observable` (RxJS) |
| ToastContainer propio | `IonToast` / `IonAlert` (envueltos en `notify`) |
| Contrato `{exito,mensaje,datos,codigo}` | Contrato Laravel `data` / `data+links+meta` / `message+errors` |

---

## 10. Documentos a leer antes de programar

1. `documentacion/ContextoGeneral.md` — visión general del producto.
2. `documentacion/CLAUDE.md` — protocolo de enrutamiento y matriz de subagentes.
3. `documentacion/Subgantes-Doc.md` — qué hace cada subagente.
4. `documentacion/front-document/ARQUITECTURA.md` — este documento.
5. `documentacion/front-document/ReglasUX.md` — identidad visual y reglas UX cerradas.
6. `documentacion/front-document/guiaMDFrontend.md` — guía de markup/estilo del frontend.
7. `documentacion/front-document/HiloActualFront.md` — estado/hilo actual del frontend.
8. `documentacion/front-document/AntierroresFront.md` — errores conocidos y checklist de cierre.
9. `documentacion/back-document/ARQUITECTURA.md` — contrato de API del backend (qué consume el front).

> **Modo de arranque:** antes de tocar código, leer los documentos, entregar un brief corto (alcance, riesgos, pantallas impactadas) y esperar autorización explícita. Probar el login y la conexión antes que nada. Trabajar por tarea acotada.

---

### Resumen en una frase

**Ionic + Angular, app híbrida (Android prioritario + web) que consume el backend Laravel solo por REST: `HttpClient` centralizado con interceptor único, capas estrictas (Page → service de feature → api), interfaces TS por Resource, contrato JSON del backend (`data`/`links`/`meta`, `message`/`errors`), token en almacenamiento seguro (Capacitor), guards por rol/modo, identidad visual cerrada (ReglasUX/guiaMDFrontend) y catálogo de antierrores como Definition of Done.**

*Última actualización: 2026-06-28.*
