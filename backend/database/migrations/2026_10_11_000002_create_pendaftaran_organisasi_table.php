<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pendaftaran_organisasi', function (Blueprint $table) {
            $table->id('id_pendaftaran_organisasi');
            $table->foreignId('id_organisasi')
                ->nullable()
                ->constrained('organisasi', 'id_organisasi')
                ->nullOnDelete();
            $table->foreignId('id_pengguna_pendaftar')
                ->constrained('pengguna', 'id_pengguna')
                ->cascadeOnDelete();
            $table->enum('jenis_organisasi', ['bem', 'hmj', 'ukm']);
            $table->string('nama_organisasi', 150);
            $table->string('nama_instansi', 150);
            $table->string('nomor_telepon_organisasi', 20);
            $table->string('nama_penanggung_jawab', 150);
            $table->string('nim_penanggung_jawab', 30);
            $table->string('nomor_whatsapp', 20);
            $table->string('foto_ktm', 500);
            $table->string('dokumen_sk_organisasi', 500);
            $table->string('dokumen_sk_kepengurusan', 500);
            $table->enum('status_pendaftaran', ['menunggu', 'disetujui', 'ditolak'])
                ->default('menunggu');
            $table->timestamp('tanggal_pendaftaran')->useCurrent();
            $table->timestamp('dibuat_pada')->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendaftaran_organisasi');
    }
};
