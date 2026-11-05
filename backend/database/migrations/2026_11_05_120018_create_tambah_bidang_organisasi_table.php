<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tambah_bidang_organisasi', function (Blueprint $table) {
            $table->id('id_tambah_bidang');
            $table->unsignedBigInteger('id_organisasi');
            $table->unsignedBigInteger('id_bidang'); // Foreign Key ke master bidang "Lainnya"
            $table->string('nama_bidang_kustom');   // Teks bebas yang diketik user
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->foreign('id_organisasi')->references('id_organisasi')->on('organisasi')->onDelete('cascade');
            $table->foreign('id_bidang')->references('id_bidang')->on('bidang_organisasi')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tambah_bidang_organisasi');
    }
};
