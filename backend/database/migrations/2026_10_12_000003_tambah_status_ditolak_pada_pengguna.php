<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->enum('status_akun', ['menunggu', 'aktif', 'nonaktif', 'ditolak'])
                ->default('menunggu')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->enum('status_akun', ['menunggu', 'aktif', 'nonaktif'])
                ->default('menunggu')
                ->change();
        });
    }
};
