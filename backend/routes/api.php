<?php

use App\Http\Controllers\AdministratorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegistrasiBemController;
use Illuminate\Support\Facades\Route;

// Publik
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

Route::post('/register/bem', [RegistrasiBemController::class, 'register'])
    ->middleware('throttle:registrasi-bem');

// Butuh token
Route::middleware(['auth:sanctum', 'akun.aktif'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
});

// Super Admin
Route::middleware(['auth:sanctum', 'akun.aktif', 'peran:super_admin'])
    ->prefix('superadmin')
    ->group(function () {
        Route::get('/administrators', [AdministratorController::class, 'index']);
        Route::post('/administrators', [AdministratorController::class, 'store']);
        Route::patch('/administrators/{id}/nonaktif', [AdministratorController::class, 'nonaktifkan'])
            ->whereNumber('id');
    });

