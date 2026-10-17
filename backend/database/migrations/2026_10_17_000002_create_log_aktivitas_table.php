<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id('id_log');
            $table->foreignId('id_pengguna')
                ->constrained('pengguna', 'id_pengguna')
                ->restrictOnDelete();
            $table->string('jenis_aktivitas', 50)->index();
            $table->text('keterangan')->nullable();
            $table->timestamp('waktu_aktivitas')->useCurrent()->index();

            $table->index('id_pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
    }
};
