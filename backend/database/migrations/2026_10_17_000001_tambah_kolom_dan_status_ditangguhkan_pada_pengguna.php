<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->string('nim', 30)->nullable();
            $table->string('nomor_whatsapp', 20)->nullable();
            $table->string('foto_ktm', 500)->nullable();
            $table->enum('status_akun', ['menunggu', 'aktif', 'ditolak', 'ditangguhkan', 'nonaktif'])
                ->default('menunggu')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->enum('status_akun', ['menunggu', 'aktif', 'nonaktif', 'ditolak'])
                ->default('menunggu')
                ->change();
            $table->dropColumn(['nim', 'nomor_whatsapp', 'foto_ktm']);
        });
    }
};
