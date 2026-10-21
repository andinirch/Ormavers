<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'sqlite') {
            Schema::table('riwayat_verifikasi', function (Blueprint $table) {
                $table->dropForeign(['id_pendaftaran_organisasi']);
                $table->dropForeign(['id_pengguna_verifikator']);
            });
        }

        Schema::dropIfExists('riwayat_verifikasi');
    }

    public function down(): void
    {
        // down logic jika diperlukan
    }
};
