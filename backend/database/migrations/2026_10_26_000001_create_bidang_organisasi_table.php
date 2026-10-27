<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bidang_organisasi', function (Blueprint $table) {
            $table->id('id_bidang');
            $table->string('nama_bidang', 100)->unique();
            $table->timestamp('dibuat_pada')->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        // Tambah FK constraint pada kolom id_bidang di tabel organisasi
        Schema::table('organisasi', function (Blueprint $table) {
            $table->foreign('id_bidang')
                  ->references('id_bidang')
                  ->on('bidang_organisasi')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('organisasi', function (Blueprint $table) {
            $table->dropForeign(['id_bidang']);
        });

        Schema::dropIfExists('bidang_organisasi');
    }
};
