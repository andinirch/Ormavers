<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Publik
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

// Butuh token
Route::middleware(['auth:sanctum', 'akun.aktif'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
});
