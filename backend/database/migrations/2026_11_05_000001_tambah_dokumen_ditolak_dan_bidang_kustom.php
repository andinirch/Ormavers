<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom dokumen_ditolak pada pendaftaran_organisasi
        Schema::table('pendaftaran_organisasi', function (Blueprint $table) {
            if (! Schema::hasColumn('pendaftaran_organisasi', 'dokumen_ditolak')) {
                $table->json('dokumen_ditolak')->nullable()->after('alasan_penolakan');
            }
        });

        // 2. Tambah kolom dokumen_ditolak pada keanggotaan
        Schema::table('keanggotaan', function (Blueprint $table) {
            if (! Schema::hasColumn('keanggotaan', 'dokumen_ditolak')) {
                $table->json('dokumen_ditolak')->nullable()->after('alasan_penolakan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pendaftaran_organisasi', function (Blueprint $table) {
            if (Schema::hasColumn('pendaftaran_organisasi', 'dokumen_ditolak')) {
                $table->dropColumn('dokumen_ditolak');
            }
        });

        Schema::table('keanggotaan', function (Blueprint $table) {
            if (Schema::hasColumn('keanggotaan', 'dokumen_ditolak')) {
                $table->dropColumn('dokumen_ditolak');
            }
        });
    }
};
