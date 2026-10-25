<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdministratorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrgMemberRequestController;
use App\Http\Controllers\PublicOrganisasiController;
use App\Http\Controllers\RegistrasiAnggotaController;
use App\Http\Controllers\RegistrasiBemController;
use App\Http\Controllers\RegistrasiHmjUkmController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StatusRegistrasiController;
use App\Http\Controllers\UniversitasController;
use App\Http\Controllers\VerifikasiBemController;
use App\Http\Controllers\VerifikasiHmjUkmController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfilOrganisasiController;
use App\Http\Controllers\PortofolioController;
use Illuminate\Support\Facades\Route;

// Publik
Route::get('/universitas', [UniversitasController::class, 'index'])
    ->middleware('throttle:60,1');

Route::get('/public/bem', [PublicOrganisasiController::class, 'bem'])
    ->middleware('throttle:60,1');

Route::get('/public/bem/{id}/organisasi', [PublicOrganisasiController::class, 'organisasiDiBawahBem'])
    ->whereNumber('id')
    ->middleware('throttle:60,1');

Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

Route::post('/register/bem', [RegistrasiBemController::class, 'register'])
    ->middleware('throttle:registrasi-bem');

Route::post('/register/hmj-ukm', [RegistrasiHmjUkmController::class, 'register'])
    ->middleware('throttle:registrasi-hmj-ukm');

Route::post('/register/anggota', [RegistrasiAnggotaController::class, 'register'])
    ->middleware('throttle:registrasi-anggota');

// Status Registrasi & Logout Registrasi
Route::middleware(['auth:sanctum', 'akses.status.registrasi'])->group(function () {
    Route::get('/registration/status', [StatusRegistrasiController::class, 'status'])
        ->middleware('throttle:30,1');
    Route::post('/registration/resubmit-sk', [StatusRegistrasiController::class, 'resubmitSk']);
    Route::post('/registration/logout', [StatusRegistrasiController::class, 'logout']);
});

// Butuh token
Route::middleware(['auth:sanctum', 'akun.aktif'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Profil Saya (Semua peran)
    Route::get('/profil', [\App\Http\Controllers\ProfilController::class, 'show']);
    Route::put('/profil', [\App\Http\Controllers\ProfilController::class, 'update']);
    Route::put('/profil/kata-sandi', [\App\Http\Controllers\ProfilController::class, 'updatePassword']);
    Route::post('/profil/foto', [\App\Http\Controllers\ProfilController::class, 'uploadFoto']);
});

// Super Admin
Route::middleware(['auth:sanctum', 'akun.aktif', 'role:super_admin'])
    ->prefix('superadmin')
    ->group(function () {
        Route::get('/users', [\App\Http\Controllers\UserAdminController::class, 'index']);
        Route::post('/users', [\App\Http\Controllers\UserAdminController::class, 'store']);
        Route::put('/users/{id}', [\App\Http\Controllers\UserAdminController::class, 'update'])
            ->whereNumber('id');

        Route::get('/administrators', [AdministratorController::class, 'index']);
        Route::post('/administrators', [AdministratorController::class, 'store']);
        Route::patch('/administrators/{id}/nonaktif', [AdministratorController::class, 'nonaktifkan'])
            ->whereNumber('id');
            Route::patch('/administrators/{id}/status', [AdministratorController::class, 'ubahStatus'])
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

// Akun Organisasi BEM — Verifikasi HMJ/UKM
Route::middleware(['auth:sanctum', 'akun.aktif', 'role:akun_bem'])
    ->prefix('org/registrations/hmj-ukm')
    ->group(function () {
        Route::get('/', [VerifikasiHmjUkmController::class, 'index']);
        Route::get('/{id}', [VerifikasiHmjUkmController::class, 'show'])
            ->whereNumber('id');
        Route::get('/{id}/dokumen/{jenis}', [VerifikasiHmjUkmController::class, 'dokumen'])
            ->whereNumber('id');
        Route::post('/{id}/approve', [VerifikasiHmjUkmController::class, 'approve'])
            ->whereNumber('id');
        Route::post('/{id}/reject', [VerifikasiHmjUkmController::class, 'reject'])
            ->whereNumber('id');
    });

// Akun Organisasi (BEM / HMJ / UKM) — Verifikasi Anggota
Route::middleware(['auth:sanctum', 'akun.aktif', 'role:akun_bem|akun_hmj_ukm'])
    ->prefix('org/member-requests')
    ->group(function () {
        Route::get('/', [OrgMemberRequestController::class, 'index']);
        Route::get('/{id}', [OrgMemberRequestController::class, 'show'])
            ->whereNumber('id');
        Route::get('/{id}/dokumen/ktm', [OrgMemberRequestController::class, 'dokumenKtm'])
            ->whereNumber('id');
        Route::post('/{id}/approve', [OrgMemberRequestController::class, 'approve'])
            ->whereNumber('id');
        Route::post('/{id}/reject', [OrgMemberRequestController::class, 'reject'])
            ->whereNumber('id');
    });

// Akun Organisasi — Ubah Deskripsi Organisasi (ORMV-1-12) & Tambah Portofolio (ORMV-1-13)
Route::middleware(['auth:sanctum', 'akun.aktif', 'peran:akun_organisasi'])
    ->prefix('organisasi')
    ->group(function () {
        Route::get('/deskripsi', [ProfilOrganisasiController::class, 'showDeskripsi']);
        Route::match(['put', 'patch'], '/deskripsi', [ProfilOrganisasiController::class, 'updateDeskripsi']);

        Route::get('/portofolio', [PortofolioController::class, 'index']);
        Route::post('/portofolio', [PortofolioController::class, 'store']);
    });

// Admin Panel — Manajemen Role & Permission & Ringkasan
Route::middleware(['auth:sanctum', 'akun.aktif'])
    ->prefix('admin-panel')
    ->group(function () {
        Route::get('/ringkasan', [RoleController::class, 'ringkasan'])
            ->middleware('role:super_admin');
        Route::get('/permissions', [RoleController::class, 'permissions'])
            ->middleware('permission:view roles');
            
        Route::post('/permissions', [PermissionController::class, 'store'])
            ->middleware('permission:create roles');
        Route::put('/permissions/{id}', [PermissionController::class, 'update'])
            ->middleware('permission:edit roles')
            ->whereNumber('id');
        Route::delete('/permissions/{id}', [PermissionController::class, 'destroy'])
            ->middleware('permission:delete roles')
            ->whereNumber('id');

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

        Route::get('/activities', [\App\Http\Controllers\LogAktivitasAdminController::class, 'index'])
            ->middleware('role:super_admin');
        Route::get('/activities/filter-options', [\App\Http\Controllers\LogAktivitasAdminController::class, 'filterOptions'])
            ->middleware('role:super_admin');
    });

