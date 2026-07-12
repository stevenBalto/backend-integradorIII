<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\CuponController;
use App\Http\Controllers\OfertaController;
use App\Http\Controllers\ProductoController;
use Illuminate\Support\Facades\Route;

// ── Autenticacion ─────────────────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

// ── Catalogo (publico, solo disponibles) ────────────────────────────────────
Route::get('/productos', [ProductoController::class, 'index']);
Route::get('/categorias', [CategoriaController::class, 'index']);
Route::get('/ofertas', [OfertaController::class, 'indexPublic']);
Route::get('/cupones', [CuponController::class, 'indexPublic']);

// ── Catalogo (administracion) ───────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:super_admin,admin_sede'])
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
    });
