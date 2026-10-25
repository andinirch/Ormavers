<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portofolio', function (Blueprint $table) {
            $table->id('id_portofolio');
            $table->foreignId('id_organisasi')
                ->constrained('organisasi', 'id_organisasi')
                ->cascadeOnDelete();
            $table->string('judul', 200);
            $table->integer('tahun');
            $table->text('deskripsi');
            $table->string('dokumentasi', 500);
            $table->timestamp('dibuat_pada')->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portofolio');
    }
};
