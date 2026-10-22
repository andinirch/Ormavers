<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keanggotaan', function (Blueprint $table) {
            $table->id('id_keanggotaan');
            $table->foreignId('id_pengguna')
                ->unique()
                ->constrained('pengguna', 'id_pengguna')
                ->cascadeOnDelete();
            $table->foreignId('id_organisasi')
                ->constrained('organisasi', 'id_organisasi')
                ->cascadeOnDelete();
            $table->enum('status_keanggotaan', ['menunggu', 'disetujui', 'ditolak', 'nonaktif'])
                ->default('menunggu');
            $table->text('alasan_penolakan')->nullable();
            $table->foreignId('id_pengguna_verifikator')
                ->nullable()
                ->constrained('pengguna', 'id_pengguna')
                ->nullOnDelete();
            $table->timestamp('tanggal_pendaftaran')->useCurrent();
            $table->timestamp('tanggal_verifikasi')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keanggotaan');
    }
};
