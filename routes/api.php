<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UsuarioController;
use App\Http\Controllers\API\VehiculoController;
use App\Http\Controllers\API\ZonaController;
use App\Http\Controllers\API\EstacionamientoController;
use App\Http\Controllers\API\AlarmaController;
use Illuminate\Support\Facades\Route;

// Rutas de autenticación
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Usuarios
    Route::apiResource('usuarios', UsuarioController::class);
    
    // Vehículos
    Route::apiResource('vehiculos', VehiculoController::class);
    Route::get('/mis-vehiculos', [VehiculoController::class, 'misVehiculos']);
    
    // Zonas
    Route::apiResource('zonas', ZonaController::class);
    Route::post('/verificar-zona', [ZonaController::class, 'verificarZona']);
    
    // Estacionamientos
    Route::apiResource('estacionamientos', EstacionamientoController::class);
    Route::post('/estacionar', [EstacionamientoController::class, 'estacionar']);
    Route::post('/finalizar-estacionamiento/{id}', [EstacionamientoController::class, 'finalizar']);
    Route::get('/estacionamiento-activo', [EstacionamientoController::class, 'estacionamientoActivo']);
    
    // Alarmas
    Route::apiResource('alarmas', AlarmaController::class);
    Route::post('/programar-alarma', [AlarmaController::class, 'programarAlarma']);
});

// Rutas públicas
Route::get('/zonas-publicas', [ZonaController::class, 'zonasPublicas']);