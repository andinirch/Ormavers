<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\Organisasi;
use App\Models\Pengguna;

class NotifikasiService
{
    /**
     * Ambil organisasi aktif milik pengguna yang sedang login.
     */
    public function getOrganisasiAktif(Pengguna $pengguna): ?Organisasi
    {
        $organisasi = Organisasi::where('id_akun_organisasi', $pengguna->id_pengguna)->first();

        if (! $organisasi || $organisasi->status_organisasi !== 'aktif') {
            return null;
        }

        return $organisasi;
    }

    /**
     * ORMV-1-34: Dapatkan daftar notifikasi organisasi terurut dari yang paling baru.
     */
    public function getDaftarNotifikasi(Pengguna $pengguna, array $filters = []): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);
        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        $query = Notifikasi::with([
            'event:id,judul,tanggal_event,status_pendaftaran',
            'ajakan:id,jenis,status',
        ])
        ->where('id_organisasi', $organisasi->id_organisasi);

        // Filter berdasarkan status baca jika diberikan ('1' / '0')
        if (isset($filters['sudah_baca']) && $filters['sudah_baca'] !== '') {
            $query->where('sudah_baca', filter_var($filters['sudah_baca'], FILTER_VALIDATE_BOOLEAN));
        }

        // Filter berdasarkan jenis jika diberikan
        if (! empty($filters['jenis'])) {
            $query->where('jenis', $filters['jenis']);
        }

        $notifikasiList = $query->orderBy('dibuat_pada', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $unreadCount = Notifikasi::where('id_organisasi', $organisasi->id_organisasi)
            ->where('sudah_baca', false)
            ->count();

        if ($notifikasiList->isEmpty()) {
            return [
                'data' => [
                    'pesan'        => 'Belum ada notifikasi',
                    'unread_count' => $unreadCount,
                    'notifikasi'   => [],
                ],
            ];
        }

        return [
            'data' => [
                'pesan'        => 'Daftar notifikasi berhasil diambil',
                'unread_count' => $unreadCount,
                'notifikasi'   => $notifikasiList,
            ],
        ];
    }

    /**
     * Dapatkan jumlah notifikasi yang belum dibaca (untuk badge ikon lonceng).
     */
    public function getUnreadCount(Pengguna $pengguna): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);
        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        $unreadCount = Notifikasi::where('id_organisasi', $organisasi->id_organisasi)
            ->where('sudah_baca', false)
            ->count();

        return [
            'data' => [
                'unread_count' => $unreadCount,
            ],
        ];
    }

    /**
     * ORMV-1-34: Tandai notifikasi tertentu sebagai sudah dibaca.
     */
    public function tandaiDibaca(Pengguna $pengguna, int $idNotifikasi): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);
        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        $notifikasi = Notifikasi::find($idNotifikasi);
        if (! $notifikasi) {
            return [
                'error'  => 'Notifikasi tidak ditemukan',
                'status' => 404,
            ];
        }

        // Validasi kepemilikan notifikasi
        if ($notifikasi->id_organisasi !== $organisasi->id_organisasi) {
            return [
                'error'  => 'Anda tidak memiliki hak akses terhadap notifikasi ini',
                'status' => 403,
            ];
        }

        if (! $notifikasi->sudah_baca) {
            $notifikasi->update([
                'sudah_baca'  => true,
                'dibaca_pada' => now(),
            ]);
        }

        $unreadCount = Notifikasi::where('id_organisasi', $organisasi->id_organisasi)
            ->where('sudah_baca', false)
            ->count();

        return [
            'data' => [
                'pesan'        => 'Notifikasi berhasil ditandai sudah dibaca',
                'unread_count' => $unreadCount,
                'notifikasi'   => $notifikasi->fresh(['event', 'ajakan']),
            ],
        ];
    }

    /**
     * Tandai seluruh notifikasi organisasi sebagai sudah dibaca sekaligus.
     */
    public function tandaiSemuaDibaca(Pengguna $pengguna): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);
        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        Notifikasi::where('id_organisasi', $organisasi->id_organisasi)
            ->where('sudah_baca', false)
            ->update([
                'sudah_baca'  => true,
                'dibaca_pada' => now(),
            ]);

        return [
            'data' => [
                'pesan'        => 'Semua notifikasi berhasil ditandai sudah dibaca',
                'unread_count' => 0,
            ],
        ];
    }
}
