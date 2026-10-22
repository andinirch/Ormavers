<?php

namespace App\Services;

use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;

class ActivityLogService
{
    public function daftarAktivitas(): array
    {
        $activities = collect();

        DB::table('riwayat_verifikasi as rv')
            ->leftJoin('pendaftaran_organisasi as po', 'po.id_pendaftaran_organisasi', '=', 'rv.id_pendaftaran_organisasi')
            ->join('pengguna as p', 'p.id_pengguna', '=', 'rv.id_pengguna_verifikator')
            ->select('rv.id_riwayat_verifikasi', 'rv.status_sesudahnya', 'rv.catatan_verifikasi', 'rv.tanggal_verifikasi', 'p.nama as pengguna', 'po.nama_organisasi')
            ->orderByDesc('rv.tanggal_verifikasi')
            ->limit(100)
            ->get()
            ->each(function ($item) use ($activities) {
                $status = $item->status_sesudahnya === 'disetujui' ? 'menyetujui' : 'menolak';
                $nama = $item->nama_organisasi ?? 'pendaftaran organisasi';
                $activities->push([
                    'id' => 'verifikasi-'.$item->id_riwayat_verifikasi,
                    'tipe' => 'Verifikasi',
                    'pengguna' => $item->pengguna,
                    'teks' => "{$item->pengguna} {$status} {$nama}",
                    'waktu' => $item->tanggal_verifikasi,
                ]);
            });

        DB::table('pendaftaran_organisasi as po')
            ->leftJoin('pengguna as p', 'p.id_pengguna', '=', 'po.id_pengguna_pendaftar')
            ->select('po.id_pendaftaran_organisasi', 'po.nama_organisasi', 'po.nama_instansi', 'po.tanggal_pendaftaran', 'p.nama as pengguna')
            ->where('po.jenis_organisasi', 'bem')
            ->orderByDesc('po.tanggal_pendaftaran')
            ->limit(100)
            ->get()
            ->each(function ($item) use ($activities) {
                $activities->push([
                    'id' => 'pendaftaran-'.$item->id_pendaftaran_organisasi,
                    'tipe' => 'Pendaftaran',
                    'pengguna' => $item->pengguna ?? 'Pengguna',
                    'teks' => "Pendaftaran {$item->nama_organisasi} dari {$item->nama_instansi}",
                    'waktu' => $item->tanggal_pendaftaran,
                ]);
            });

        Pengguna::query()
            ->select('id_pengguna', 'nama', 'peran', 'dibuat_pada')
            ->orderByDesc('dibuat_pada')
            ->limit(100)
            ->get()
            ->each(function (Pengguna $item) use ($activities) {
                $activities->push([
                    'id' => 'akun-'.$item->id_pengguna,
                    'tipe' => 'Akun',
                    'pengguna' => $item->nama,
                    'teks' => "Akun {$item->peran} {$item->nama} dibuat",
                    'waktu' => $item->dibuat_pada,
                ]);
            });

        DB::table('personal_access_tokens as t')
            ->join('pengguna as p', 'p.id_pengguna', '=', 't.tokenable_id')
            ->where('t.tokenable_type', Pengguna::class)
            ->select('t.id', 't.created_at', 'p.nama as pengguna')
            ->orderByDesc('t.created_at')
            ->limit(100)
            ->get()
            ->each(function ($item) use ($activities) {
                $activities->push([
                    'id' => 'login-'.$item->id,
                    'tipe' => 'Login',
                    'pengguna' => $item->pengguna,
                    'teks' => "{$item->pengguna} login",
                    'waktu' => $item->created_at,
                ]);
            });

        return $activities->sortByDesc('waktu')->take(100)->values()->all();
    }
}
