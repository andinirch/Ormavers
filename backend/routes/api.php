<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdministratorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegistrasiBemController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\VerifikasiBemController;
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
         Route::get('/users', [AdministratorController::class, 'users']);
         Route::post('/administrators', [AdministratorController::class, 'store']);
        Route::patch('/administrators/{id}/nonaktif', [AdministratorController::class, 'nonaktifkan'])
            ->whereNumber('id');
    });

// Administrator — Verifikasi BEM
Route::middleware(['auth:sanctum', 'akun.aktif', 'peran:administrator'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/registrations/bem', [VerifikasiBemController::class, 'index']);
        Route::get('/registrations/bem/{id}', [VerifikasiBemController::class, 'show'])
            ->whereNumber('id');
        Route::get('/registrations/bem/{id}/dokumen/{jenis}', [VerifikasiBemController::class, 'dokumen'])
            ->whereNumber('id');
        Route::post('/registrations/bem/{id}/approve', [VerifikasiBemController::class, 'approve'])
            ->whereNumber('id');
        Route::post('/registrations/bem/{id}/reject', [VerifikasiBemController::class, 'reject'])
            ->whereNumber('id');
    });

// Admin Panel — Manajemen Role & Permission
Route::middleware(['auth:sanctum', 'akun.aktif'])
    ->prefix('admin-panel')
    ->group(function () {
        Route::get('/permissions', [RoleController::class, 'permissions'])
            ->middleware('permission:view roles');
        Route::get('/roles', [RoleController::class, 'index'])
            ->middleware('permission:view roles');
        Route::get('/roles/{id}', [RoleController::class, 'show'])
            ->middleware('permission:view roles')
            ->whereNumber('id');
        Route::post('/roles', [RoleController::class, 'store'])
            ->middleware('permission:create roles');
        Route::put('/roles/{id}', [RoleController::class, 'update'])
            ->middleware('permission:edit roles')
            ->whereNumber('id');
         Route::delete('/roles/{id}', [RoleController::class, 'destroy'])
             ->middleware('permission:delete roles')
             ->whereNumber('id');
         Route::get('/activities', [ActivityLogController::class, 'index'])
             ->middleware('peran:super_admin');
     });

