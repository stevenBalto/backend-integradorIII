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
