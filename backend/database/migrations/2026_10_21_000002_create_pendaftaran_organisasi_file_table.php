<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pendaftaran_organisasi_file', function (Blueprint $table) {
            $table->id('id_file');
            $table->foreignId('id_pendaftaran_organisasi')
                ->constrained('pendaftaran_organisasi', 'id_pendaftaran_organisasi')
                ->cascadeOnDelete();
            $table->enum('jenis_dokumen', ['sk_organisasi', 'sk_kepengurusan']);
            $table->string('nama_file_asli', 255);
            $table->string('path_file', 500);
            $table->integer('ukuran_kb');
            $table->timestamp('diunggah_pada')->useCurrent();

            $table->unique(['id_pendaftaran_organisasi', 'jenis_dokumen']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendaftaran_organisasi_file');
    }
};
