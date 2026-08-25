<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\PrestacionController;
use App\Http\Controllers\DemandaController;
use App\Http\Controllers\ExportController;

// Ruta pública
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas con Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Proyectos
    Route::get('/proyectos', [ProyectoController::class, 'index']);
    Route::post('/proyectos', [ProyectoController::class, 'store']);

    // Prestaciones
    Route::get('/prestaciones', [PrestacionController::class, 'index']);
    Route::get('/prestaciones-demanda', [DemandaController::class, 'show']);

    // Cálculo
    Route::post('/calcular-demanda', [DemandaController::class, 'calcular']);
    Route::get('/resultados/{proyecto}', [DemandaController::class, 'resultados']);

    // Exportación
    Route::get('/exportar/excel/{proyecto}', [ExportController::class, 'excel']);
    Route::get('/exportar/pdf/{proyecto}', [ExportController::class, 'pdf']);
});