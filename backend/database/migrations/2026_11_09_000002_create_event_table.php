<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_organisasi');
            $table->unsignedBigInteger('id_kategori')->nullable();
            $table->string('judul', 255);
            $table->text('deskripsi');
            $table->date('tanggal_event');
            $table->string('tag', 255)->nullable();
            $table->string('tautan_pendaftaran', 500);
            $table->dateTime('periode_mulai');
            $table->dateTime('periode_selesai');
            $table->enum('jangkauan_publikasi', ['internal', 'satu_universitas', 'multi_universitas']);
            $table->string('token_sharelink', 64)->nullable()->unique();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->foreign('id_organisasi')
                ->references('id_organisasi')->on('organisasi')
                ->onDelete('cascade');

            $table->foreign('id_kategori')
                ->references('id')->on('kategori_event')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event');
    }
};
