<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengguna', function (Blueprint $table) {
            $table->id('id_pengguna');
            $table->string('nama', 150);
            $table->string('username', 100)->unique();
            $table->string('kata_sandi', 255);
            $table->enum('peran', ['anggota', 'akun_organisasi', 'administrator', 'super_admin']);
            $table->enum('status_akun', ['menunggu', 'aktif', 'nonaktif'])->default('menunggu');
            $table->string('foto_profil', 500)->nullable();
            $table->timestamp('dibuat_pada')->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('pengguna');
    }
};