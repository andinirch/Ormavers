<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_organisasi');
            $table->unsignedBigInteger('id_event')->nullable();
            $table->unsignedBigInteger('id_ajakan')->nullable();
            $table->text('pesan');
            $table->timestamp('dibaca_pada')->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->foreign('id_organisasi')
                ->references('id_organisasi')->on('organisasi')
                ->onDelete('cascade');

            $table->foreign('id_event')
                ->references('id')->on('event')
                ->onDelete('cascade');

            $table->foreign('id_ajakan')
                ->references('id')->on('ajakan')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
