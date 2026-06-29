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
