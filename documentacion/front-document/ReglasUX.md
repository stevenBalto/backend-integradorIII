# Reglas de UX y Validación — Rooster

> **Propósito:** reglas que se aplican **desde el primer diseño** de cualquier pantalla o formulario, para no repetir siempre las mismas correcciones. Todo formulario nuevo debe cumplir estas reglas antes de darse por terminado.

---

## 1. Principios generales

1. **Validar es ayudar, no castigar.** El mensaje siempre dice *cómo corregir*, nunca solo "campo inválido".
2. **Prevenir antes que corregir.** Si un dato puede limitarse en el input (longitud máxima, solo números, formato), se limita en el input — no se deja escribir algo que luego habrá que rechazar.
3. **Nunca confiar solo en el front.** Toda validación visible se asume también en backend; el front es para experiencia, no para seguridad.
4. **Consistencia:** el mismo tipo de campo (correo, teléfono, precio) se valida igual en toda la app.

---

## 2. Cuándo validar (timing)

| Momento | Qué se valida | Por qué |
|---|---|---|
| **Mientras escribe** | Solo formato/longitud que se puede *limitar* (bloquear caracteres, contador) | No molestar con errores antes de tiempo |
| **Al salir del campo (`blur`)** | Validación completa de ese campo | El usuario terminó de escribirlo |
| **Al enviar (`submit`)** | Todos los campos + reglas cruzadas | Última barrera antes de procesar |

> **Regla de oro:** No mostrar error en rojo *mientras* el usuario escribe por primera vez en un campo. El error aparece al salir del campo o al intentar enviar. Una vez mostrado el error, sí se corrige en tiempo real al volver a escribir.

---

## 3. Mensajes de error

- Se muestran **debajo del campo**, en rojo (`#DC2626`), tamaño 11–12px.
- El campo con error toma **borde rojo** + ícono de alerta opcional.
- **Un mensaje por campo** (el más relevante), no una lista.
- Texto específico y accionable:

| ❌ Evitar | ✅ Usar |
|---|---|
| "Campo inválido" | "Ingresa un correo válido (ej: nombre@correo.com)" |
| "Error" | "La contraseña debe tener al menos 8 caracteres" |
| "Requerido" | "Ingresa tu nombre" |

- **Errores de servidor / red** van en un banner o toast arriba del formulario, no sobre un campo individual.

---

## 4. Reglas por tipo de campo

### 4.1 Texto obligatorio (nombre, etc.)
- No vacío y no solo espacios (`trim`).
- Se recorta espacios al inicio/fin antes de guardar.
- Longitud máxima razonable con `maxLength` (ej. nombre 60).

### 4.2 Correo electrónico
- Formato `algo@algo.dominio`.
- Se guarda en **minúsculas** y sin espacios.
- `type="email"`, `autocomplete="email"`, teclado de correo en móvil.

### 4.3 Contraseña
- Mínimo **8 caracteres**.
- Botón de **mostrar/ocultar** (ojo).
- En registro: confirmar contraseña y validar que **coincidan**.
- Nunca mostrar la contraseña en texto plano por defecto ni en logs.

### 4.4 Teléfono
- Solo dígitos (y opcional `+` inicial); se bloquean letras.
- `inputmode="numeric"`, `autocomplete="tel"`.
- Longitud según país; mostrar formato esperado en el placeholder.

### 4.5 Números, cantidades y precios
- `inputmode="decimal"` o `numeric`; no permitir letras.
- **Precios:** mayores a 0, máximo 2 decimales, mostrar símbolo de moneda.
- **Cantidades de pedido:** enteros, mínimo **1**, controles `−` / `+` con tope de stock.
- **Descuentos / porcentajes:** entre 0 y 100.
- No permitir números negativos donde no tienen sentido.

### 4.6 Fechas y rangos (cupones, ofertas)
- Fecha fin **no anterior** a fecha inicio.
- No permitir fechas pasadas donde no aplique (ej. vigencia de cupón).
- Formato visible consistente: `Jun 10` / `10 jun 2026`.

### 4.7 Selección (dropdowns, radios)
- Si hay ≤ 2 opciones, usar radios/toggle, no dropdown.
- Estado por defecto válido o placeholder "Selecciona…" que **no** cuente como selección.

---

## 5. Estados de formulario y botones

| Estado | Comportamiento |
|---|---|
| **Inicial** | Botón de envío habilitado; validar al enviar |
| **Enviando** | Botón en **loading** (spinner) y deshabilitado para evitar doble envío |
| **Error** | Volver a habilitar, enfocar el primer campo con error, scroll hasta él |
| **Éxito** | Confirmación (toast verde) + redirección o limpieza del form |

- **Nunca** permitir doble clic que envíe dos veces (deshabilitar mientras procesa).
- Botón primario con label de acción (`Guardar`, `Crear pedido`), no genérico (`Enviar`).

---

## 6. Feedback al usuario

- **Éxito:** toast verde (`#16A34A`), breve ("Usuario creado").
- **Acciones destructivas** (eliminar, cancelar pedido): **modal de confirmación** con nombre del elemento y botón rojo. Nunca eliminar al primer clic.
- **Operaciones largas:** indicador de carga; nunca dejar la pantalla "congelada" sin feedback.
- Toasts se autodescartan (4–5 s); los errores críticos requieren acción del usuario.

---

## 7. Estados de pantalla (no solo el caso feliz)

Toda vista que carga datos debe contemplar **siempre los 4 estados**:

| Estado | Qué mostrar |
|---|---|
| **Cargando** | Skeleton o spinner, no pantalla en blanco |
| **Vacío** | Mensaje + ícono + acción ("Aún no hay pedidos. Crear el primero") |
| **Error** | Mensaje claro + botón "Reintentar" |
| **Con datos** | El contenido normal |

> **Regla de oro:** Si una lista o tabla puede estar vacía, su estado vacío se diseña **desde el inicio**, no después.

---

## 8. Accesibilidad mínima (obligatoria)

- Todo input tiene `<label>` asociado (no solo placeholder).
- El placeholder **no** sustituye al label.
- Foco visible en teclado (no quitar el `outline` sin reemplazo).
- Contraste de texto suficiente (mínimo AA).
- Errores anunciados (`aria-invalid`, `aria-describedby` al mensaje).
- Áreas táctiles ≥ 40×40px en móvil.

---

## 9. Checklist antes de dar por terminado un formulario

- [ ] Cada campo obligatorio valida vacío con mensaje claro.
- [ ] Formato validado (correo, teléfono, precio, fecha).
- [ ] Errores aparecen al `blur`/`submit`, se corrigen en tiempo real.
- [ ] Botón de envío con estado loading y sin doble envío.
- [ ] Acciones destructivas con confirmación.
- [ ] Estados cargando / vacío / error / éxito contemplados.
- [ ] Labels, foco y contraste accesibles.
- [ ] Mensajes en español, claros y accionables.
