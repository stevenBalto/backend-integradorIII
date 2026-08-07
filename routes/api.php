<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AnaliticasController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\NotificacionController;
use App\Http\Controllers\Admin\PedidoAdminController;
use App\Http\Controllers\Admin\ResenaController as AdminResenaController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\SuperAdmin\InstanciaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\ExtraController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\CuponController;
use App\Http\Controllers\InsumoController;
use App\Http\Controllers\OfertaController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\PuntosController;
use App\Http\Controllers\ResenaController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\SuperAdminAuthController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\ClienteController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\LogRequestTiming;

// Agrupar todas las rutas de API para registrar tiempo de respuesta (middleware)
Route::middleware([LogRequestTiming::class])->group(function () {

// ── Autenticacion ─────────────────────────────────────────────────────────────
// Las dos puertas publicas van con limite de peticiones: sin el, se pueden
// probar contrasenas a la velocidad que aguante el servidor. Ver los limitadores
// 'login' y 'registro' en AppServiceProvider.
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:registro');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// ── Inicio de sesion con Google (OAuth 2.0, publico) ─────────────────────────
// redirect/callback los visita el NAVEGADOR (no son XHR): devuelven redirecciones.
// exchange sí es XHR y entrega el token; por eso lleva limite de intentos.
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);
Route::post('/auth/google/exchange', [GoogleAuthController::class, 'exchange'])->middleware('throttle:10,1');

// ── Reset de contraseña por correo (publico, con limite de intentos) ─────────
Route::post('/forgot-password', [PasswordResetController::class, 'forgot'])->middleware('throttle:6,1');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:6,1');

// ── Cambio de contraseña expirada (self-service, publico, con limite) ────────
// El usuario se autentica con sus credenciales vencidas y proporciona nueva password.
Route::post('/auth/password-expirada', [AuthController::class, 'passwordExpirada'])->middleware('throttle:6,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    // Cambio de contraseña propio (NO lleva password.valida: el usuario temporal
    // debe poder entrar aquí justamente para cambiarla).
    Route::post('/cuenta/cambiar-password', [CuentaController::class, 'cambiarPassword']);

    // Roosters (puntos de fidelidad) del cliente.
    Route::get('/puntos/mios', [PuntosController::class, 'mios']);

    // Pedidos (cliente autenticado).
    Route::post('/pedidos', [PedidoController::class, 'store']);
    Route::get('/pedidos/mios', [PedidoController::class, 'misPedidos']);
    // 'mios/buscar' debe registrarse ANTES que 'mios/{id}', si no Laravel toma "buscar" como {id}.
    Route::get('/pedidos/mios/buscar', [PedidoController::class, 'misPedidosBuscar']);
    Route::get('/pedidos/mios/{id}', [PedidoController::class, 'misPedidosShow']);

    // Reseñas del cliente (solo pedidos propios ya entregados).
    Route::get('/resenas/pendientes', [ResenaController::class, 'pendientes']);
    Route::post('/resenas/pedidos/{pedidoId}', [ResenaController::class, 'enviar']);
    Route::post('/resenas/pedidos/{pedidoId}/descartar', [ResenaController::class, 'descartar']);
});

// ── Catalogo (publico, solo disponibles) ────────────────────────────────────
Route::get('/productos', [ProductoController::class, 'index']);
Route::get('/categorias', [CategoriaController::class, 'index']);
// Sucursales: publico para que un invitado pueda elegir sucursal al pedir. Es info
// de negocio (nombre/direccion/telefono) ya publicada en la web del cliente.
Route::get('/sucursales', [SucursalController::class, 'index']);
Route::get('/ofertas', [OfertaController::class, 'indexPublic']);
Route::get('/cupones', [CuponController::class, 'indexPublic']);
Route::get('/home-config', [ConfiguracionController::class, 'show']);
// Restaurantes: una tarjeta por instancia activa, con los datos que cada
// sucursal cargó en Configuración → Información del negocio.
Route::get('/restaurantes', [ConfiguracionController::class, 'restaurantes']);
Route::get('/productos/{id}/resenas', [ResenaController::class, 'producto']);

// ── Busqueda publica de pedido por codigo ────────────────────────────────────
Route::get('/pedidos/buscar', [PedidoController::class, 'buscarPublico'])->middleware('throttle:10,1');

// ── Pedido de invitado (visitante sin sesion; identificado por codigo + nombre) ─
Route::post('/pedidos/invitado', [PedidoController::class, 'storeInvitado'])->middleware('throttle:10,1');

// ── Catalogo (administracion) ───────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'password.valida', 'role:super_admin,admin_sede'])
    ->prefix('admin')
    ->group(function () {
        // Productos
        Route::get('/productos', [ProductoController::class, 'indexAdmin']);
        Route::get('/productos/{id}', [ProductoController::class, 'show']);
        Route::post('/productos', [ProductoController::class, 'store']);
        Route::match(['put', 'patch'], '/productos/{id}', [ProductoController::class, 'update']);
        Route::delete('/productos/{id}', [ProductoController::class, 'destroy']);

        // Ofertas
        Route::get('/ofertas', [OfertaController::class, 'indexAdmin']);
        Route::get('/ofertas/{id}', [OfertaController::class, 'show']);
        Route::post('/ofertas', [OfertaController::class, 'store']);
        Route::match(['put', 'patch'], '/ofertas/{id}', [OfertaController::class, 'update']);
        Route::delete('/ofertas/{id}', [OfertaController::class, 'destroy']);

        // Cupones
        Route::get('/cupones', [CuponController::class, 'indexAdmin']);
        Route::get('/cupones/{id}', [CuponController::class, 'show']);
        Route::post('/cupones', [CuponController::class, 'store']);
        Route::match(['put', 'patch'], '/cupones/{id}', [CuponController::class, 'update']);
        Route::delete('/cupones/{id}', [CuponController::class, 'destroy']);
        Route::post('/cupones/validar', [CuponController::class, 'validar']);

        // Configuracion del Home (curacion: oferta destacada)
        Route::put('/home-config', [ConfiguracionController::class, 'update']);

        // Configuracion general del panel (por instancia).
        Route::get('/configuracion', [ConfiguracionController::class, 'ajustes']);
        Route::put('/configuracion', [ConfiguracionController::class, 'guardarAjustes']);

        // Inventario (insumos / materia prima) — 100% admin, sin endpoints publicos
        Route::get('/insumos', [InsumoController::class, 'index']);
        Route::get('/insumos/{id}', [InsumoController::class, 'show']);
        Route::post('/insumos', [InsumoController::class, 'store']);
        Route::match(['put', 'patch'], '/insumos/{id}', [InsumoController::class, 'update']);
        Route::delete('/insumos/{id}', [InsumoController::class, 'destroy']);
        Route::post('/insumos/{id}/toma-fisica', [InsumoController::class, 'tomaFisica']);
        Route::get('/insumos/{id}/movimientos', [InsumoController::class, 'movimientos']);

        // Categorias de la instancia (aisladas: el global scope filtra por instancia).
        Route::get('/categorias', [CategoriaController::class, 'index']);
        Route::post('/categorias', [CategoriaController::class, 'store']);

        // Gestion de usuarios de la instancia (CRUD + permisos por modulo).
        Route::get('/usuarios', [UsuarioController::class, 'index']);
        Route::get('/usuarios/opciones', [UsuarioController::class, 'opciones']);
        Route::post('/usuarios', [UsuarioController::class, 'store']);
        Route::match(['put', 'patch'], '/usuarios/{id}', [UsuarioController::class, 'update']);
        Route::patch('/usuarios/{id}/estado', [UsuarioController::class, 'cambiarEstado']);
        Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy']);

        // Catalogo de modulos (para el modal de asignacion de permisos del frontend).
        Route::get('/modulos', [UsuarioController::class, 'modulos']);

        // Extras / acompañamientos (CRUD completo + asignacion puntual a productos).
        Route::get('/extras', [ExtraController::class, 'index']);
        Route::get('/extras/{id}', [ExtraController::class, 'show']);
        Route::post('/extras', [ExtraController::class, 'store']);
        Route::match(['put', 'patch'], '/extras/{id}', [ExtraController::class, 'update']);
        Route::delete('/extras/{id}', [ExtraController::class, 'destroy']);
        Route::post('/extras/{id}/productos', [ExtraController::class, 'asignarProducto']);
        Route::delete('/extras/{id}/productos/{productoId}', [ExtraController::class, 'desasignarProducto']);

        // Dashboard (resumen: KPIs del dia, ventas de la semana, pedidos nuevos/ultimos).
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Analiticas (ventas mensuales, horas pico, top productos, modalidad).
        Route::get('/analiticas', [AnaliticasController::class, 'index']);
        Route::get('/analiticas/exportar/excel', [AnaliticasController::class, 'exportarExcel']);
        Route::get('/analiticas/exportar/pdf', [AnaliticasController::class, 'exportarPdf']);

        // Pedidos (administracion).
        Route::post('/pedidos/mostrador', [PedidoAdminController::class, 'storeMostrador']);
        Route::get('/pedidos', [PedidoAdminController::class, 'index']);
        Route::get('/pedidos/{id}', [PedidoAdminController::class, 'show']);
        Route::post('/pedidos/{id}/estado', [PedidoAdminController::class, 'cambiarEstado']);
        Route::post('/pedidos/{id}/revertir', [PedidoAdminController::class, 'revertir']);
        Route::post('/pedidos/{id}/pagar', [PedidoAdminController::class, 'pagar']);

        // Sucursales (listado admin incluye inactivas + alta/edicion).
        Route::get('/sucursales', [SucursalController::class, 'indexAdmin']);
        Route::post('/sucursales', [SucursalController::class, 'store']);
        Route::match(['put', 'patch'], '/sucursales/{id}', [SucursalController::class, 'update']);

        // Clientes (analitica de compra, solo lectura)
        Route::get('/clientes', [ClienteController::class, 'index']);
        Route::get('/clientes/{id}/pedidos', [ClienteController::class, 'pedidos']);

        // Notificaciones (bandeja de admin; el front hace polling de index).
        Route::get('/notificaciones', [NotificacionController::class, 'index']);
        Route::post('/notificaciones/leer-todas', [NotificacionController::class, 'marcarTodasLeidas']);
        Route::post('/notificaciones/{id}/leer', [NotificacionController::class, 'marcarLeida']);
        Route::delete('/notificaciones', [NotificacionController::class, 'destroyTodas']);
        Route::delete('/notificaciones/{id}', [NotificacionController::class, 'destroy']);

        // Reseñas (gestión: listar/filtrar, ocultar/mostrar, eliminar, stats).
        Route::get('/resenas', [AdminResenaController::class, 'index']);
        Route::get('/resenas/stats', [AdminResenaController::class, 'stats']);
        Route::post('/resenas/{id}/ocultar', [AdminResenaController::class, 'ocultar']);
        Route::post('/resenas/{id}/mostrar', [AdminResenaController::class, 'mostrar']);
        Route::delete('/resenas/{id}', [AdminResenaController::class, 'destroy']);
    });

// ── Superadministracion (panel AISLADO: login/guard/middleware propios) ──────
Route::prefix('superadmin')->group(function () {
    Route::post('/login', [SuperAdminAuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware(['auth:sanctum', 'superadmin'])->group(function () {
        Route::post('/logout', [SuperAdminAuthController::class, 'logout']);
        Route::get('/me', [SuperAdminAuthController::class, 'me']);

        // CRUD de superadministradores (solo un superadmin puede gestionarlos).
        Route::get('/superadmins', [SuperAdminController::class, 'index']);
        Route::post('/superadmins', [SuperAdminController::class, 'store']);
        Route::match(['put', 'patch'], '/superadmins/{id}', [SuperAdminController::class, 'update']);
        Route::post('/superadmins/{id}/reset-password', [SuperAdminController::class, 'resetPassword']);
        Route::post('/superadmins/{id}/desactivar', [SuperAdminController::class, 'desactivar']);
        Route::delete('/superadmins/{id}', [SuperAdminController::class, 'destroy']);

        // CRUD de instancias (cuentas independientes) + auto-admin temporal.
        Route::get('/instancias', [InstanciaController::class, 'index']);
        Route::post('/instancias', [InstanciaController::class, 'store']);
        Route::match(['put', 'patch'], '/instancias/{id}', [InstanciaController::class, 'update']);
        Route::post('/instancias/{id}/estado', [InstanciaController::class, 'cambiarEstado']);
        Route::delete('/instancias/{id}', [InstanciaController::class, 'destroy']);
    });
});

// Cierre del grupo de middleware LogRequestTiming
});
