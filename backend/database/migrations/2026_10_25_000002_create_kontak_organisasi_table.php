<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kontak_organisasi', function (Blueprint $table) {
            $table->id('id_kontak');
            $table->foreignId('id_organisasi')
                ->constrained('organisasi', 'id_organisasi')
                ->cascadeOnDelete();
            $table->string('jenis', 50);
            $table->string('tautan', 255);
            $table->timestamp('dibuat_pada')->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kontak_organisasi');
    }
};
