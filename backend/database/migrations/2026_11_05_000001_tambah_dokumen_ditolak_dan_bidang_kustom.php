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

        // 3. Tambah kolom bidang_kustom pada organisasi
        Schema::table('organisasi', function (Blueprint $table) {
            if (! Schema::hasColumn('organisasi', 'bidang_kustom')) {
                $table->string('bidang_kustom', 100)->nullable()->after('id_bidang');
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

        Schema::table('organisasi', function (Blueprint $table) {
            if (Schema::hasColumn('organisasi', 'bidang_kustom')) {
                $table->dropColumn('bidang_kustom');
            }
        });
    }
};
