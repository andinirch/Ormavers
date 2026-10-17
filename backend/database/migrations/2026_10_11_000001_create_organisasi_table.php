<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisasi', function (Blueprint $table) {
            $table->id('id_organisasi');
            $table->foreignId('id_pengguna_akun')
                ->nullable()
                ->unique()
                ->constrained('pengguna', 'id_pengguna')
                ->nullOnDelete();
            $table->foreignId('id_organisasi_induk')
                ->nullable()
                ->constrained('organisasi', 'id_organisasi')
                ->nullOnDelete();
            $table->string('nama_organisasi', 150);
            $table->enum('jenis_organisasi', ['bem', 'hmj', 'ukm']);
            $table->string('nama_instansi', 150);
            $table->string('nomor_telepon', 20)->nullable();
            $table->string('nomor_whatsapp', 20)->nullable();
            $table->text('deskripsi')->nullable();
            $table->text('alamat')->nullable();
            $table->string('logo', 500)->nullable();
            $table->string('portofolio', 500)->nullable();
            $table->date('masa_berlaku_sk')->nullable();
            $table->enum('status_organisasi', ['menunggu', 'aktif', 'ditolak', 'nonaktif'])
                ->default('menunggu');
            $table->timestamp('dibuat_pada')->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisasi');
    }
};
