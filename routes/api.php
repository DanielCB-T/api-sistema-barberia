<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Todas las rutas de esta API van aquí, nunca en routes/web.php.
| Se sirven bajo el prefijo /api automáticamente (ver bootstrap/app.php).
|
*/

// ============================================================
// Rutas públicas
// ============================================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Catálogo de servicios: lectura pública (así lo consume el sitio público).
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{service}', [ServiceController::class, 'show']);

// ============================================================
// Rutas protegidas: requieren token válido (Authorization: Bearer <token>)
// ============================================================
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- Citas: cliente, barbero y admin comparten los mismos endpoints;
    // el propio controlador filtra qué puede ver/hacer cada rol. ---
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::get('/appointments/{appointment}', [AppointmentController::class, 'show']);
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update']);
    Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);

    // --- Solo admin ---
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/services', [ServiceController::class, 'store']);
        Route::put('/services/{service}', [ServiceController::class, 'update']);
        Route::delete('/services/{service}', [ServiceController::class, 'destroy']);
    });

    // --- Admin o el propio usuario (la lógica exacta vive en el controlador) ---
    Route::get('/users/{user}', [UserController::class, 'show']);
});
