<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_verifikasi', function (Blueprint $table) {
            $table->id('id_riwayat_verifikasi');
            $table->foreignId('id_pendaftaran_organisasi')
                ->nullable()
                ->constrained('pendaftaran_organisasi', 'id_pendaftaran_organisasi')
                ->nullOnDelete();
            $table->unsignedBigInteger('id_pendaftaran_anggota')->nullable();
            $table->foreignId('id_pengguna_verifikator')
                ->constrained('pengguna', 'id_pengguna')
                ->cascadeOnDelete();
            $table->string('status_sebelumnya', 30);
            $table->enum('status_sesudahnya', ['menunggu', 'disetujui', 'ditolak']);
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamp('tanggal_verifikasi')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_verifikasi');
    }
};
