# Estándares de UI — Frontend (altos de controles)

Guía de **altos y tamaños consistentes** para controles del panel admin. Nació porque
se repetían desalineaciones (botones/chips/KPIs con altos distintos en la misma fila).
**Antes de agregar o tocar un control en una fila existente, igualá su alto al de sus
vecinos según esta tabla.** Verificá con `getBoundingClientRect().height` (no a ojo).

> Nota base: Ionic aplica `box-sizing: border-box` global, así que `height: Npx` YA
> incluye padding + border. Poné `height` explícito en los controles que comparten
> fila; NO dejes que el alto lo defina solo el padding cuando conviven varios controles
> (así es como se descuadran).

## Tabla de estándares

| Contexto | Controles | Alto | Notas |
|---|---|---|---|
| **Botones de ícono del header** | campana (`.admin-header__bell`), ayuda `?` (`.ped-help-btn` en móvil), expandir-header (`.ped-expand-btn--header`) | **32×32px** | Cuadrados, `border-radius: 8px`, ícono 16px. Icono-only = cuadrado (no pill ancho). |
| **Controles de toolbar de tabla** | chips de filtro (`.ped-datechip`), calendario (`.ped-datechip--date`), botón expandir en toolbar (`.ped-expand-btn`), lupa del buscador (`admin-search-input .asi__toggle`) | **36px** de alto | Todos los de una misma fila comparten 36px. La lupa del buscador compartido ya es 36px → el resto se iguala a eso. |
| **KPI chips del header** (`.admin-hkpi`, en `global.scss`) | tarjetas-filtro proyectadas al header (`adminHeaderLead`), en los 9 módulos con KPIs | **48px** de alto | Formato reducido de `<admin-kpi-card>`: ícono en cuadro de color 26×26 (radio 7) + label/valor a la derecha, radio 12. Ancho: `flex: 1 1 136px` (llenan el header y se reparten parejo); `.admin-hkpi--wide` (piso 200px) si el chip lleva subtexto largo. Siempre dentro de `<admin-hkpi-strip>`, que saca flechas cuando no caben. |
| **Botones de acción del header** (`admin-btn [iconOnly]`, `.an-export-btn`) | crear/nuevo/canjear + export Excel/PDF + sync (analíticas) | **32×32px** | En PC/tablet **solo ícono** (sin texto), cuadrado 32×32 con `aria-label`/`title`. Los logos Excel/PDF son **SVG de marca inline** (verde X + rojo Acrobat "A"), no librería de íconos (ver regla no-npm). |
| **Avatar del header** | (`.admin-header__user` avatar) | círculo ~= alto de los botones del header | Mismo alto visual que campana/botones. |

## Reglas
1. **Icono-only = cuadrado.** Un botón que en cierto breakpoint queda solo con el ícono
   (ej. `?` en móvil, con el texto oculto por `.hide-on-mobile`) debe volverse cuadrado
   (`width = height`, `padding: 0`), no quedar como pill ancho.
2. **Misma fila = mismo alto.** Chips, inputs, botones y buscador que conviven en una fila
   deben tener el mismo alto (36px en toolbars de tabla). Si agregás uno nuevo, copiá el alto.
3. **KPIs uniformes.** Los chips-KPI del header se reparten por igual el ancho disponible
   (`flex: 1 1 136px`, misma base para todos) — nunca se dimensionan al contenido, que es
   lo que los hace disparejos. Cuando no caben NO se encogen hasta ser ilegibles: el carril
   `<admin-hkpi-strip>` saca flechas y los corre. Los estilos son **globales** (`global.scss`),
   no por página: el template se proyecta al shell y lo comparten 9 módulos.
4. **El texto que cede es el detalle, no el dato.** En un chip con subtexto, el valor lleva
   `flex: 0 0 auto` (nunca se recorta) y el subtexto `flex: 0 1 auto; min-width: 0` (se elipsa).
   Ver EF-21: sin `min-width: 0` el `ellipsis` no recorta y el texto se sale del chip.
5. **Verificación.** Al cerrar un cambio de header/toolbar, medir alturas con Playwright
   (`getBoundingClientRect`) y confirmar que coinciden; no confiar en la inspección visual.

## Historial
- 2026-08-06: **KPIs al header en las 8 secciones** con clase global `.admin-hkpi` (116×50
  uniforme, label+valor+variación). Botones del header **solo ícono 32×32** en PC/tablet
  (menú/inventario/ofertas/usuarios + export). **Encabezado de sección**: sin título/subtítulo
  en tablet+ (los KPIs ocupan su lugar); en **móvil se muestra solo el título** (clase
  `.admin-header__left--kpimode`). Logos **Excel/PDF = SVG de marca inline** (no librería).
- 2026-08-06: los KPIs del header pasaron a `.admin-hkpi` (global, 48px de alto, formato
  reducido de `admin-kpi-card`: icono en cuadro de color + label/valor) y se subieron a los
  9 modulos admin con KPIs, dentro del carril `<admin-hkpi-strip>` con flechas. **Reconcilia las dos
  versiones que se hicieron en paralelo ese dia** (ver la entrada de arriba): queda este
  formato, con la clase generica `.admin-kpis-body` para la copia del cuerpo.
- 2026-08-05: creado tras estandarizar Pedidos (KPIs del header 108×44 uniformes; chips
  de fecha + expandir + lupa a 36px; `?` móvil a 32×32 cuadrado; campana/expand-header ya 32×32).
