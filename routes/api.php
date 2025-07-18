<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\VehiculoController;
use App\Http\Controllers\API\EstacionamientoController;
use App\Http\Controllers\API\ZonaController;
use App\Http\Controllers\API\AlarmaController;
use App\Http\Controllers\API\UsuarioController;
use Illuminate\Support\Facades\Route;

// Rutas de autenticación
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Usuarios
    Route::apiResource('usuarios', UsuarioController::class);
    Route::post('/usuarios/{id}/cambiar-password', [UsuarioController::class, 'cambiarPassword']);
    Route::get('/usuarios/buscar', [UsuarioController::class, 'buscar']);
    
    // Vehículos
    Route::apiResource('vehiculos', VehiculoController::class);
    Route::get('/vehiculo-activo', [VehiculoController::class, 'vehiculoActivo']);
    Route::put('/vehiculos/{id}/ubicacion', [VehiculoController::class, 'actualizarUbicacion']);
    
    // Estacionamientos
    Route::apiResource('estacionamientos', EstacionamientoController::class);
    Route::post('/estacionamientos/{id}/finalizar', [EstacionamientoController::class, 'finalizar']);
    Route::post('/verificar-zona', [EstacionamientoController::class, 'verificarZona']);
    Route::get('/estacionamiento-activo', [EstacionamientoController::class, 'estacionamientoActivo']);
    
    // Zonas
    Route::apiResource('zonas', ZonaController::class);
    Route::get('/zonas-activas', [ZonaController::class, 'zonasActivas']);
    Route::get('/zonas-por-tipo', [ZonaController::class, 'zonasPorTipo']);
    Route::post('/zonas/{id}/verificar-punto', [ZonaController::class, 'verificarPunto']);
    Route::post('/zonas-cercanas', [ZonaController::class, 'zonasCercanas']);
    Route::post('/zonas/{id}/activar', [ZonaController::class, 'activarZona']);
    Route::post('/zonas/{id}/desactivar', [ZonaController::class, 'desactivarZona']);
    
    // Tarifas
    Route::get('/tarifas-horarias', [ZonaController::class, 'tarifasHorarias']);
    
    // Alarmas
    Route::apiResource('alarmas', AlarmaController::class);
    Route::post('/alarmas/{id}/marcar-enviada', [AlarmaController::class, 'marcarEnviada']);
    Route::get('/alarmas-activas', [AlarmaController::class, 'alarmasActivas']);
    Route::get('/alarmas-pendientes', [AlarmaController::class, 'alarmasPendientes']);
    Route::post('/alarmas/{id}/desactivar', [AlarmaController::class, 'desactivar']);
});

// Rutas públicas
Route::get('/test', function () {
    return response()->json(['message' => 'API funcionando', 'timestamp' => now()]);
});