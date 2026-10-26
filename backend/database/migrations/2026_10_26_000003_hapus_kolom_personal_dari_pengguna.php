<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->dropColumn(['nim', 'nomor_whatsapp', 'foto_ktm', 'foto_profil']);
        });
    }

    public function down(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->string('foto_profil', 500)->nullable()->after('status_akun');
            $table->string('nim', 30)->nullable()->after('diperbarui_pada');
            $table->string('nomor_whatsapp', 20)->nullable()->after('nim');
            $table->string('foto_ktm', 500)->nullable()->after('nomor_whatsapp');
        });
    }
};
