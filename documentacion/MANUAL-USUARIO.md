# Manual de Usuario — Rooster Pizza & Grill

Sistema de pedidos y administración para Rooster Pizza & Grill. Este manual cubre las tres vistas del sistema: **Cliente**, **Administrador de Sede** y **Super Administrador**.

---

## Índice

1. [Introducción](#1-introducción)
2. [Vista Cliente](#2-vista-cliente)
3. [Vista Administrador de Sede](#3-vista-administrador-de-sede)
4. [Vista Super Administrador](#4-vista-super-administrador)
5. [Preguntas frecuentes](#5-preguntas-frecuentes)

---

## 1. Introducción

Rooster Pizza & Grill es una aplicación web/móvil (Ionic + Angular) que permite a los clientes explorar el menú, armar pedidos y darles seguimiento, mientras que el personal administrativo gestiona el catálogo, el inventario, las promociones y las analíticas del negocio desde un panel dedicado. El sistema es **multi-sede**: cada sucursal (instancia) opera de forma independiente bajo la supervisión de un Super Administrador general.

Existen tres tipos de usuario:

| Rol | ¿Quién lo usa? | ¿Dónde inicia sesión? |
|---|---|---|
| **Cliente** | Público general que realiza pedidos | Pantalla de login de la app (o invitado, sin cuenta) |
| **Administrador de Sede** (`admin_sede`) | Personal que gestiona una sucursal específica | Mismo login de la app, dirige al panel `/admin` |
| **Super Administrador** (`super_admin`) | Dueño/gerencia general, gestiona todas las sedes | Login independiente en `/superadmin` |

---

## 2. Vista Cliente

La app cliente no requiere cuenta para navegar el menú, pero sí para realizar seguimiento de pedidos guardado en el historial.

### 2.1 Inicio (Home)
Pantalla de bienvenida con banner promocional, la oferta destacada del momento y accesos rápidos a las secciones principales. El contenido de esta pantalla lo configura el administrador de cada sede.

### 2.2 Pedir (Carrito)
Sección principal para armar un pedido:
- Catálogo de productos organizado por categorías (pizzas, bebidas, acompañamientos, etc.).
- Cada producto permite elegir modificadores: tamaño, tipo de masa y extras/ingredientes adicionales. El precio se recalcula automáticamente según lo seleccionado.
- El carrito muestra el detalle y total antes de confirmar.
- Al finalizar, se elige la modalidad de consumo: **Comer aquí** o **Para llevar** (no hay entrega a domicilio).
- Se puede aplicar un cupón de descuento válido antes de confirmar el pedido.
- Es posible realizar el pedido con sesión iniciada o como **invitado** (sin cuenta), en cuyo caso el pedido se puede rastrear luego con un código de búsqueda.

### 2.3 Ofertas
Listado público de ofertas y cupones vigentes en la sede seleccionada, visible sin necesidad de iniciar sesión.

### 2.4 Mi Cuenta
Hub central de la cuenta del cliente, con las siguientes subsecciones:
- **Perfil**: datos personales del usuario (nombre, correo, teléfono).
- **Historial**: listado de pedidos anteriores realizados por el cliente.
- **Roosters / Puntos**: programa de fidelidad, muestra los puntos acumulados.
- **Restaurantes**: listado de sucursales activas del sistema.
- **FAQ / Info**: preguntas frecuentes e información general del negocio.
- Desde aquí también se accede al **login** si el usuario no tiene sesión iniciada.

### 2.5 Mis Pedidos
Pantalla dedicada al seguimiento de pedidos propios: permite ver el listado completo, buscar un pedido puntual y consultar su detalle y estado actual (recibido, en preparación, listo, entregado, etc.).

### 2.6 Autenticación
- **Iniciar sesión**: con correo/contraseña o mediante **Google** (inicio de sesión con un clic usando la cuenta de Google).
- **Registro**: creación de cuenta nueva.
- **Recuperar contraseña**: se envía un enlace de restablecimiento por correo.
- **Cambiar contraseña**: disponible desde el perfil o de forma obligatoria si la contraseña ha expirado.

### 2.7 Reseñas
Después de que un pedido es marcado como entregado, la app puede mostrar automáticamente un aviso invitando al cliente a calificar su experiencia. El cliente puede completar la reseña (calificación + comentario) o descartar el aviso.

---

## 3. Vista Administrador de Sede

Panel de administración accesible desde el mismo login de la app, en la ruta `/admin`. Está pensado para el personal que gestiona el día a día de **una sucursal**. El panel muestra un menú lateral con las siguientes secciones:

### 3.1 Dashboard
Resumen ejecutivo del día: indicadores clave (KPIs), ventas de la semana y los pedidos más recientes/nuevos, para tener una vista rápida del estado del negocio al iniciar sesión.

### 3.2 Inicio (Home)
Permite configurar qué contenido se muestra en la pantalla de Inicio de la app cliente: por ejemplo, cuál es la oferta destacada del momento.

### 3.3 Pedidos
Gestión completa de pedidos entrantes:
- Ver y filtrar pedidos por estado, fecha o modalidad.
- Cambiar el estado de un pedido (en preparación, listo, entregado) o revertirlo si hubo un error.
- Marcar un pedido como pagado.

### 3.4 Menú
Gestión del catálogo de productos de la sede:
- Crear, editar y eliminar productos y categorías.
- Administrar extras/acompañamientos y asignarlos a los productos correspondientes.

### 3.5 Inventario
Control de stock de insumos (materia prima):
- Alta y edición de insumos.
- Registro de tomas físicas de inventario.
- Historial de movimientos (entradas/salidas de stock).

### 3.6 Ofertas y Cupones
Administración de promociones:
- Crear y editar ofertas destacadas.
- Crear y validar cupones de descuento.

### 3.7 Pedido de Mostrador
Módulo para atender pedidos presenciales (walk-in):
- Permite crear pedidos directamente desde el mostrador.
- Incluye un **escáner QR** para canjear cupones de clientes en el local.

### 3.8 Clientes
Vista de solo lectura con información analítica de los clientes: listado, historial de pedidos por cliente y un gráfico de los clientes con más compras (top clientes).

### 3.9 Usuarios y Roles
Gestión del personal con acceso al panel:
- CRUD de usuarios de la sede.
- Asignación de permisos por módulo (qué secciones puede ver/editar cada usuario).
- Activar o desactivar cuentas.

### 3.10 Analíticas
Dashboard de análisis del negocio con gráficos interactivos:
- Ventas mensuales, horas pico, productos más vendidos, comparación entre periodos y modalidad de consumo (comer aquí vs. para llevar).
- Posibilidad de **exportar reportes a Excel o PDF**.

### 3.11 Notificaciones
Bandeja de notificaciones del panel administrativo (por ejemplo, nuevos pedidos entrantes), con actualización en tiempo real. Permite marcar como leídas o eliminar notificaciones.

### 3.12 Reseñas
Moderación de las reseñas dejadas por clientes:
- Listar y filtrar reseñas.
- Ocultar, mostrar o eliminar una reseña.
- Ver estadísticas generales de calificación.

### 3.13 Configuración
Ajustes generales de la sede: información del negocio, horarios de atención, y gestión de datos de la(s) sucursal(es) asociadas.

---

## 4. Vista Super Administrador

El Super Administrador tiene su **propio inicio de sesión**, independiente del login de clientes/administradores de sede, en la ruta `/superadmin`. Su función es supervisar y administrar **todas las sedes/instancias** del sistema.

### 4.1 Panel de Superadministradores
Gestión de las cuentas con privilegios de super administrador:
- Crear, editar y eliminar cuentas de superadmin.
- Restablecer contraseñas.
- Activar o desactivar cuentas.

### 4.2 Instancias (Sedes)
Gestión de las instancias (sedes/sucursales) que operan en el sistema:
- Alta de una nueva instancia (sede), incluyendo la creación de un administrador temporal para esa sede.
- Edición de datos de una instancia existente.
- Activar o desactivar una instancia.
- Eliminar una instancia.

> **Nota:** un Super Administrador también puede ingresar al panel `/admin` de una sede específica con los mismos permisos que un Administrador de Sede, para supervisar o intervenir directamente en la operación de esa sucursal cuando sea necesario.

---

## 5. Preguntas frecuentes

**¿Puedo hacer un pedido sin crear una cuenta?**
Sí. Se puede realizar un pedido como invitado; el sistema entrega un código para rastrear el pedido posteriormente.

**¿El sistema tiene entrega a domicilio?**
No. Las modalidades disponibles son "Comer aquí" y "Para llevar" únicamente.

**¿Cómo inicio sesión con Google?**
Desde la pantalla de login, seleccionando la opción de acceso con Google; no requiere crear una contraseña nueva.

**¿Qué diferencia hay entre el panel de Administrador de Sede y el de Super Administrador?**
El Administrador de Sede gestiona una sola sucursal (menú, pedidos, inventario, analíticas de esa sede). El Super Administrador gestiona todas las sedes del sistema y las cuentas de otros superadministradores.

**Olvidé mi contraseña, ¿qué hago?**
En la pantalla de login, seleccionar "Recuperar contraseña" e ingresar el correo asociado a la cuenta; se enviará un enlace para restablecerla.
