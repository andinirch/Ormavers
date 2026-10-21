<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite tidak mendukung penghapusan foreign key individual via ALTER TABLE.
            // Gunakan table rebuild dengan menonaktifkan foreign key sementara.
            Schema::create('pendaftaran_organisasi_temp', function (Blueprint $table) {
                $table->id('id_pendaftaran_organisasi');
                $table->foreignId('id_organisasi')
                    ->unique()
                    ->constrained('organisasi', 'id_organisasi');
                $table->enum('status_pendaftaran', ['menunggu', 'disetujui', 'ditolak'])
                    ->default('menunggu');
                $table->text('alasan_penolakan')->nullable();
                $table->foreignId('id_pengguna_verifikator')
                    ->nullable()
                    ->constrained('pengguna', 'id_pengguna')
                    ->nullOnDelete();
                $table->timestamp('tanggal_pendaftaran')->useCurrent();
                $table->timestamp('tanggal_verifikasi')->nullable();
                $table->timestamp('diperbarui_pada')->nullable();
            });

            DB::statement('INSERT INTO pendaftaran_organisasi_temp (
                id_pendaftaran_organisasi, id_organisasi, status_pendaftaran, alasan_penolakan,
                id_pengguna_verifikator, tanggal_pendaftaran, tanggal_verifikasi, diperbarui_pada
            ) SELECT
                id_pendaftaran_organisasi, id_organisasi, status_pendaftaran, alasan_penolakan,
                id_pengguna_verifikator, tanggal_pendaftaran, tanggal_verifikasi, diperbarui_pada
            FROM pendaftaran_organisasi');

            DB::statement('PRAGMA foreign_keys = OFF');
            Schema::drop('pendaftaran_organisasi');
            Schema::rename('pendaftaran_organisasi_temp', 'pendaftaran_organisasi');
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            // MySQL: Hapus foreign key sebelum kolomnya
            Schema::table('pendaftaran_organisasi', function (Blueprint $table) {
                $table->dropForeign(['id_pengguna_pendaftar']);
                $table->dropForeign(['id_organisasi']);
            });

            Schema::table('pendaftaran_organisasi', function (Blueprint $table) {
                $table->unsignedBigInteger('id_organisasi')->nullable(false)->unique()->change();
                $table->foreign('id_organisasi')->references('id_organisasi')->on('organisasi');

                if (! Schema::hasColumn('pendaftaran_organisasi', 'id_pengguna_verifikator')) {
                    $table->unsignedBigInteger('id_pengguna_verifikator')->nullable()->after('alasan_penolakan');
                }
                $table->foreign('id_pengguna_verifikator')->references('id_pengguna')->on('pengguna')->nullOnDelete();

                $table->dropColumn([
                    'id_pengguna_pendaftar',
                    'jenis_organisasi',
                    'nama_instansi',
                    'nama_organisasi',
                    'nomor_telepon_organisasi',
                    'nama_penanggung_jawab',
                    'nim_penanggung_jawab',
                    'nomor_whatsapp',
                    'foto_ktm',
                    'dokumen_sk_organisasi',
                    'dokumen_sk_kepengurusan',
                    'dibuat_pada',
                ]);
            });
        }
    }

    public function down(): void
    {
        // down logic jika diperlukan
    }
};
