<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_poster', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_event');
            $table->string('nama_file_asli', 255);
            $table->string('path_file', 500);
            $table->unsignedInteger('ukuran_file');
            $table->boolean('is_utama')->default(false);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->foreign('id_event')
                ->references('id')->on('event')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_poster');
    }
};
