<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organisasi', function (Blueprint $table) {
            if (Schema::hasColumn('organisasi', 'id_organisasi_induk')) {
                // Hapus foreign key terlebih dahulu jika terdaftar
                $table->dropForeign(['id_organisasi_induk']);
                $table->dropColumn('id_organisasi_induk');
            }
        });

        Schema::table('pendaftaran_organisasi', function (Blueprint $table) {
            if (Schema::hasColumn('pendaftaran_organisasi', 'id_organisasi_induk')) {
                $table->dropForeign(['id_organisasi_induk']);
                $table->dropColumn('id_organisasi_induk');
            }
        });
    }

    public function down(): void
    {
        Schema::table('organisasi', function (Blueprint $table) {
            $table->unsignedBigInteger('id_organisasi_induk')->nullable()->after('id_universitas');
            $table->foreign('id_organisasi_induk')->references('id_organisasi')->on('organisasi')->onDelete('cascade');
        });

        Schema::table('pendaftaran_organisasi', function (Blueprint $table) {
            $table->unsignedBigInteger('id_organisasi_induk')->nullable()->after('id_universitas');
            $table->foreign('id_organisasi_induk')->references('id_organisasi')->on('pendaftaran_organisasi')->onDelete('cascade');
        });
    }
};