<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // 1. Buat baris universitas dari nilai nama_instansi yang berbeda (di-trim)
        $instansiPendaftaran = Schema::hasColumn('pendaftaran_organisasi', 'nama_instansi')
            ? DB::table('pendaftaran_organisasi')->whereNotNull('nama_instansi')->pluck('nama_instansi')
            : collect();

        $instansiOrganisasi = Schema::hasColumn('organisasi', 'nama_instansi')
            ? DB::table('organisasi')->whereNotNull('nama_instansi')->pluck('nama_instansi')
            : collect();

        $semuaInstansi = $instansiPendaftaran->merge($instansiOrganisasi)
            ->map(fn ($v) => trim($v))
            ->filter(fn ($v) => $v !== '')
            ->unique();

        $universitasMap = [];
        foreach ($semuaInstansi as $nama) {
            $univ = DB::table('universitas')->where('nama_universitas', $nama)->first();
            if (! $univ) {
                $id = DB::table('universitas')->insertGetId([
                    'nama_universitas' => $nama,
                    'logo'             => null,
                    'dibuat_pada'      => now(),
                    'diperbarui_pada'  => now(),
                ]);
                $universitasMap[$nama] = $id;
            } else {
                $universitasMap[$nama] = $univ->id_universitas;
            }
        }

        // Pastikan kolom id_pengguna_verifikator ada pada pendaftaran_organisasi untuk penampungan data
        if (! Schema::hasColumn('pendaftaran_organisasi', 'id_pengguna_verifikator')) {
            Schema::table('pendaftaran_organisasi', function (Blueprint $table) {
                $table->unsignedBigInteger('id_pengguna_verifikator')->nullable()->after('alasan_penolakan');
            });
        }

        // 2. Untuk tiap pendaftaran:
        $pendaftaranList = DB::table('pendaftaran_organisasi')->get();
        foreach ($pendaftaranList as $p) {
            $namaInstansi = isset($p->nama_instansi) ? trim($p->nama_instansi) : '';
            $univId = $universitasMap[$namaInstansi] ?? null;

            // Jika belum ada universitas terpilih (misal instansi kosong), fallback ke record pertama universitas jika ada
            if (! $univId && ! empty($universitasMap)) {
                $univId = reset($universitasMap);
            }

            $organisasiId = $p->id_organisasi ?? null;

            // Buat baris organisasi jika belum ada
            if (! $organisasiId) {
                $organisasiId = DB::table('organisasi')->insertGetId([
                    'id_universitas'     => $univId,
                    'id_organisasi_induk'=> null,
                    'id_akun_organisasi' => $p->id_pengguna_pendaftar,
                    'nama_organisasi'    => $p->nama_organisasi,
                    'jenis_organisasi'   => $p->jenis_organisasi,
                    'nomor_telepon'      => $p->nomor_telepon_organisasi ?? '0',
                    'status_organisasi'  => 'menunggu',
                    'nama_instansi'      => $namaInstansi,
                    'dibuat_pada'        => now(),
                    'diperbarui_pada'    => now(),
                ]);

                DB::table('pendaftaran_organisasi')
                    ->where('id_pendaftaran_organisasi', $p->id_pendaftaran_organisasi)
                    ->update(['id_organisasi' => $organisasiId]);
            } else {
                // Untuk organisasi yang sudah ada, isi id_universitas dan nomor_telepon
                $updateOrg = [];
                if ($univId) {
                    $updateOrg['id_universitas'] = $univId;
                }
                if (isset($p->nomor_telepon_organisasi)) {
                    $updateOrg['nomor_telepon'] = $p->nomor_telepon_organisasi;
                }
                if (! empty($updateOrg)) {
                    DB::table('organisasi')->where('id_organisasi', $organisasiId)->update($updateOrg);
                }
            }

            // Salin nim_penanggung_jawab, nomor_whatsapp, foto_ktm ke pengguna pendaftar
            if (isset($p->id_pengguna_pendaftar)) {
                $updatePengguna = [];
                if (isset($p->nim_penanggung_jawab)) {
                    $updatePengguna['nim'] = $p->nim_penanggung_jawab;
                }
                if (isset($p->nomor_whatsapp)) {
                    $updatePengguna['nomor_whatsapp'] = $p->nomor_whatsapp;
                }
                if (isset($p->foto_ktm)) {
                    $updatePengguna['foto_ktm'] = $p->foto_ktm;
                }
                if (! empty($updatePengguna)) {
                    DB::table('pengguna')
                        ->where('id_pengguna', $p->id_pengguna_pendaftar)
                        ->update($updatePengguna);
                }
            }

            // Buat 2 baris pendaftaran_organisasi_file per pendaftaran
            if (isset($p->dokumen_sk_organisasi) && $p->dokumen_sk_organisasi) {
                $ukuranKb = 0;
                try {
                    if (Storage::disk('local')->exists($p->dokumen_sk_organisasi)) {
                        $ukuranKb = (int) ceil(Storage::disk('local')->size($p->dokumen_sk_organisasi) / 1024);
                    }
                } catch (\Throwable) {}

                DB::table('pendaftaran_organisasi_file')->insertOrIgnore([
                    'id_pendaftaran_organisasi' => $p->id_pendaftaran_organisasi,
                    'jenis_dokumen'             => 'sk_organisasi',
                    'nama_file_asli'            => basename($p->dokumen_sk_organisasi),
                    'path_file'                 => $p->dokumen_sk_organisasi,
                    'ukuran_kb'                 => $ukuranKb,
                    'diunggah_pada'             => $p->tanggal_pendaftaran ?? now(),
                ]);
            }

            if (isset($p->dokumen_sk_kepengurusan) && $p->dokumen_sk_kepengurusan) {
                $ukuranKb = 0;
                try {
                    if (Storage::disk('local')->exists($p->dokumen_sk_kepengurusan)) {
                        $ukuranKb = (int) ceil(Storage::disk('local')->size($p->dokumen_sk_kepengurusan) / 1024);
                    }
                } catch (\Throwable) {}

                DB::table('pendaftaran_organisasi_file')->insertOrIgnore([
                    'id_pendaftaran_organisasi' => $p->id_pendaftaran_organisasi,
                    'jenis_dokumen'             => 'sk_kepengurusan',
                    'nama_file_asli'            => basename($p->dokumen_sk_kepengurusan),
                    'path_file'                 => $p->dokumen_sk_kepengurusan,
                    'ukuran_kb'                 => $ukuranKb,
                    'diunggah_pada'             => $p->tanggal_pendaftaran ?? now(),
                ]);
            }
        }

        // 3. Isi id_pengguna_verifikator dari riwayat_verifikasi terakhir dan salin ke log_aktivitas
        if (Schema::hasTable('riwayat_verifikasi')) {
            $riwayatList = DB::table('riwayat_verifikasi')
                ->orderBy('id_riwayat_verifikasi', 'asc')
                ->get();

            foreach ($riwayatList as $rw) {
                if ($rw->id_pendaftaran_organisasi) {
                    DB::table('pendaftaran_organisasi')
                        ->where('id_pendaftaran_organisasi', $rw->id_pendaftaran_organisasi)
                        ->update(['id_pengguna_verifikator' => $rw->id_pengguna_verifikator]);
                }

                $pendaftarRow = $rw->id_pendaftaran_organisasi
                    ? DB::table('pendaftaran_organisasi')->where('id_pendaftaran_organisasi', $rw->id_pendaftaran_organisasi)->first()
                    : null;
                $namaOrg = $pendaftarRow ? ($pendaftarRow->nama_organisasi ?? 'BEM') : 'BEM';

                $keterangan = $rw->status_sesudahnya === 'disetujui'
                    ? "Menyetujui pendaftaran BEM {$namaOrg}"
                    : "Menolak pendaftaran BEM {$namaOrg} dengan alasan: {$rw->catatan_verifikasi}";

                DB::table('log_aktivitas')->insert([
                    'id_pengguna'     => $rw->id_pengguna_verifikator,
                    'jenis_aktivitas' => 'verifikasi',
                    'keterangan'      => $keterangan,
                    'waktu_aktivitas' => $rw->tanggal_verifikasi ?? now(),
                ]);
            }
        }

        // 4. Terapkan constraint NOT NULL, UNIQUE, FK, dan drop kolom lama pada organisasi
        if ($driver === 'mysql') {
            Schema::table('organisasi', function (Blueprint $table) {
                // Tambahkan baris drop foreign key ini untuk melepas aturan SET NULL lama
                try {
                    $table->dropForeign('organisasi_id_pengguna_akun_foreign');
                } catch (\Throwable $e) {}

                $table->unsignedBigInteger('id_universitas')->nullable(false)->change();
                $table->unsignedBigInteger('id_akun_organisasi')->nullable(false)->change();
                $table->string('nomor_telepon', 20)->nullable(false)->change();
                $table->foreign('id_universitas')->references('id_universitas')->on('universitas');
                $table->unique(['id_universitas', 'nama_organisasi']);
            });
        } else {
            Schema::table('organisasi', function (Blueprint $table) {
                $table->foreign('id_universitas')->references('id_universitas')->on('universitas');
                $table->unique(['id_universitas', 'nama_organisasi']);
            });
        }

        $dropCols = array_filter(
            ['nama_instansi', 'nomor_whatsapp', 'portofolio', 'masa_berlaku_sk'],
            fn ($col) => Schema::hasColumn('organisasi', $col)
        );

        if (! empty($dropCols)) {
            Schema::table('organisasi', function (Blueprint $table) use ($dropCols) {
                $table->dropColumn($dropCols);
            });
        }
    }

    public function down(): void
    {
        // Rollback tidak mengembalikan riwayat_verifikasi yang sudah diubah
    }
};
