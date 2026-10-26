<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profil_anggota', function (Blueprint $table) {
            $table->id('id_profil_anggota');
            $table->unsignedBigInteger('id_pengguna')->unique();
            $table->string('nim', 30)->nullable();
            $table->string('nomor_whatsapp', 20)->nullable();
            $table->string('foto_ktm', 500)->nullable();
            $table->string('foto_profil', 500)->nullable();
            $table->unsignedBigInteger('id_universitas')->nullable();
            $table->timestamp('dibuat_pada')->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->foreign('id_pengguna')
                  ->references('id_pengguna')
                  ->on('pengguna')
                  ->cascadeOnDelete();

            $table->foreign('id_universitas')
                  ->references('id_universitas')
                  ->on('universitas')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profil_anggota');
    }
};
