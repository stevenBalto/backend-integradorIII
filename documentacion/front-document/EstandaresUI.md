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
| **KPI chips del header** (`.ped-hkpi`) | tarjetas-filtro proyectadas al header (`adminHeaderLead`) | **108×44px** | Ancho **fijo uniforme** (`flex: 0 0 108px`), NO `flex:1` (se estiran y quedan "deformes"), NO content-size (quedan disparejos). Contenido centrado. |
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
