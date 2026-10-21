<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Konversi nilai status lama 'ditolak' menjadi 'menunggu' SEBELUM enum diubah
        DB::table('organisasi')
            ->where('status_organisasi', 'ditolak')
            ->update(['status_organisasi' => 'menunggu']);

        Schema::table('organisasi', function (Blueprint $table) {
            if (! Schema::hasColumn('organisasi', 'id_universitas')) {
                $table->unsignedBigInteger('id_universitas')->nullable()->after('id_organisasi');
            }

            if (Schema::hasColumn('organisasi', 'id_pengguna_akun') && ! Schema::hasColumn('organisasi', 'id_akun_organisasi')) {
                $table->renameColumn('id_pengguna_akun', 'id_akun_organisasi');
            }

            if (! Schema::hasColumn('organisasi', 'id_bidang')) {
                $table->unsignedBigInteger('id_bidang')->nullable()->after('id_organisasi_induk');
            }

            if (! Schema::hasColumn('organisasi', 'tanggal_mulai_sk')) {
                $table->date('tanggal_mulai_sk')->nullable()->after('logo');
            }

            if (! Schema::hasColumn('organisasi', 'tanggal_berakhir_sk')) {
                $table->date('tanggal_berakhir_sk')->nullable()->after('tanggal_mulai_sk');
            }
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE organisasi MODIFY COLUMN status_organisasi ENUM('menunggu', 'aktif', 'ditangguhkan', 'nonaktif') NOT NULL DEFAULT 'menunggu'");
        }
    }

    public function down(): void
    {
        Schema::table('organisasi', function (Blueprint $table) {
            if (Schema::hasColumn('organisasi', 'id_akun_organisasi') && ! Schema::hasColumn('organisasi', 'id_pengguna_akun')) {
                $table->renameColumn('id_akun_organisasi', 'id_pengguna_akun');
            }
            if (Schema::hasColumn('organisasi', 'id_universitas')) {
                $table->dropColumn('id_universitas');
            }
            if (Schema::hasColumn('organisasi', 'id_bidang')) {
                $table->dropColumn('id_bidang');
            }
            if (Schema::hasColumn('organisasi', 'tanggal_mulai_sk')) {
                $table->dropColumn('tanggal_mulai_sk');
            }
            if (Schema::hasColumn('organisasi', 'tanggal_berakhir_sk')) {
                $table->dropColumn('tanggal_berakhir_sk');
            }
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE organisasi MODIFY COLUMN status_organisasi ENUM('menunggu', 'aktif', 'ditolak', 'nonaktif') NOT NULL DEFAULT 'menunggu'");
        }
    }
};
