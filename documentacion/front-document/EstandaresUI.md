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
| **KPI chips del header — estándar global** (`.admin-hkpi` en `global.scss`) | KPIs de TODAS las secciones proyectados al header (`adminHeaderLead`): dashboard, inicio, menú, inventario, ofertas/cupones, clientes, analíticas, reseñas | **116×50px** | Ancho **fijo uniforme** (`flex: 0 0 116px`). Estructura: `__label` (10px) + `__value` (16px) + `__delta` opcional (9px, variación). `--active` = filtro activo (borde rojo). `--static` = métrica no clicable (reseñas/analíticas). Montos largos se acortan con `montoCorto()` (`₡1,3 M`). En tablet+ viven en el header y el cuerpo se oculta (`.admin-kpis-body`); en móvil el header los oculta y el cuerpo los muestra. |
| **KPI chips del header — Pedidos** (`.ped-hkpi`) | tarjetas-filtro de Pedidos (5 conteos por estado) | **108×44px** | Variante propia de Pedidos (sin línea de variación). Ancho fijo uniforme, NO `flex:1` (se estiran/"deformes"), NO content-size (disparejos). |
| **Botones de acción del header** (`admin-btn [iconOnly]`, `.an-export-btn`) | crear/nuevo/canjear + export Excel/PDF + sync (analíticas) | **32×32px** | En PC/tablet **solo ícono** (sin texto), cuadrado 32×32 con `aria-label`/`title`. Los logos Excel/PDF son **SVG de marca inline** (verde X + rojo Acrobat "A"), no librería de íconos (ver regla no-npm). |
| **Avatar del header** | (`.admin-header__user` avatar) | círculo ~= alto de los botones del header | Mismo alto visual que campana/botones. |

## Reglas
1. **Icono-only = cuadrado.** Un botón que en cierto breakpoint queda solo con el ícono
   (ej. `?` en móvil, con el texto oculto por `.hide-on-mobile`) debe volverse cuadrado
   (`width = height`, `padding: 0`), no quedar como pill ancho.
2. **Misma fila = mismo alto.** Chips, inputs, botones y buscador que conviven en una fila
   deben tener el mismo alto (36px en toolbars de tabla). Si agregás uno nuevo, copiá el alto.
3. **KPIs uniformes.** Los chips-KPI del header van con ancho fijo igual entre sí; no se
   estiran para llenar el header ni se dimensionan al contenido (eso los hace disparejos).
4. **Verificación.** Al cerrar un cambio de header/toolbar, medir alturas con Playwright
   (`getBoundingClientRect`) y confirmar que coinciden; no confiar en la inspección visual.

## Historial
- 2026-08-05: creado tras estandarizar Pedidos (KPIs del header 108×44 uniformes; chips
  de fecha + expandir + lupa a 36px; `?` móvil a 32×32 cuadrado; campana/expand-header ya 32×32).
- 2026-08-06: **KPIs al header en las 8 secciones** con clase global `.admin-hkpi` (116×50
  uniforme, label+valor+variación). Botones del header **solo ícono 32×32** en PC/tablet
  (menú/inventario/ofertas/usuarios + export). **Encabezado de sección**: sin título/subtítulo
  en tablet+ (los KPIs ocupan su lugar); en **móvil se muestra solo el título** (clase
  `.admin-header__left--kpimode`). Logos **Excel/PDF = SVG de marca inline** (no librería).
