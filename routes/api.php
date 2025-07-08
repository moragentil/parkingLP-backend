<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\VehiculoController;
use Illuminate\Support\Facades\Route;

// Rutas de autenticación
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Vehículos
    Route::apiResource('vehiculos', VehiculoController::class);
    Route::get('/vehiculo-activo', [VehiculoController::class, 'vehiculoActivo']);
    Route::put('/vehiculos/{id}/ubicacion', [VehiculoController::class, 'actualizarUbicacion']);
});

// Ruta de test
Route::get('/test', function () {
    return response()->json(['message' => 'API funcionando', 'timestamp' => now()]);
});