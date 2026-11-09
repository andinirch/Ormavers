<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajakan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_event');
            $table->unsignedBigInteger('id_organisasi_pengirim');
            $table->unsignedBigInteger('id_organisasi_tujuan');
            $table->enum('jenis', ['kolaborator', 'undangan']);
            $table->enum('status', ['menunggu', 'diterima', 'ditolak'])->default('menunggu');
            $table->text('alasan_penolakan')->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->foreign('id_event')
                ->references('id')->on('event')
                ->onDelete('cascade');

            $table->foreign('id_organisasi_pengirim')
                ->references('id_organisasi')->on('organisasi')
                ->onDelete('cascade');

            $table->foreign('id_organisasi_tujuan')
                ->references('id_organisasi')->on('organisasi')
                ->onDelete('cascade');

            $table->unique(['id_event', 'id_organisasi_tujuan', 'jenis'], 'ajakan_event_tujuan_jenis_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajakan');
    }
};
