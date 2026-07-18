<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\SuperAdmin\InstanciaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\CuponController;
use App\Http\Controllers\InsumoController;
use App\Http\Controllers\OfertaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\SuperAdminAuthController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\ClienteController;
use Illuminate\Support\Facades\Route;

// ── Autenticacion ─────────────────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ── Reset de contraseña por correo (publico, con limite de intentos) ─────────
Route::post('/forgot-password', [PasswordResetController::class, 'forgot'])->middleware('throttle:6,1');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:6,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    // Cambio de contraseña propio (NO lleva password.valida: el usuario temporal
    // debe poder entrar aquí justamente para cambiarla).
    Route::post('/cuenta/cambiar-password', [CuentaController::class, 'cambiarPassword']);
});

// ── Catalogo (publico, solo disponibles) ────────────────────────────────────
Route::get('/productos', [ProductoController::class, 'index']);
Route::get('/categorias', [CategoriaController::class, 'index']);
Route::get('/ofertas', [OfertaController::class, 'indexPublic']);
Route::get('/cupones', [CuponController::class, 'indexPublic']);
Route::get('/home-config', [ConfiguracionController::class, 'show']);

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

        // Configuracion del Home (curacion: oferta destacada)
        Route::put('/home-config', [ConfiguracionController::class, 'update']);

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

        // Gestion de usuarios de la instancia (CRUD + permisos por modulo).
        Route::get('/usuarios', [UsuarioController::class, 'index']);
        Route::get('/usuarios/opciones', [UsuarioController::class, 'opciones']);
        Route::post('/usuarios', [UsuarioController::class, 'store']);
        Route::match(['put', 'patch'], '/usuarios/{id}', [UsuarioController::class, 'update']);
        Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy']);

        // Clientes (analitica de compra, solo lectura)
        Route::get('/clientes', [ClienteController::class, 'index']);
        Route::get('/clientes/{id}/pedidos', [ClienteController::class, 'pedidos']);
    });

// ── Superadministracion (panel AISLADO: login/guard/middleware propios) ──────
Route::prefix('superadmin')->group(function () {
    Route::post('/login', [SuperAdminAuthController::class, 'login']);

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
