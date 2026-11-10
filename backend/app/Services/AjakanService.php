<?php

namespace App\Services;

use App\Models\Ajakan;
use App\Models\Event;
use App\Models\Notifikasi;
use App\Models\Organisasi;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AjakanService
{
    /**
     * Dapatkan organisasi aktif milik akun pengguna yang sedang login.
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
     * ORMV-1-22 & ORMV-1-23:
     * Dapatkan daftar organisasi yang tersedia untuk diajak kolaborasi atau diundang ke event,
     * lengkap dengan pencarian, filter, dan status ajakan saat ini.
     */
    public function getOrganisasiTersedia(Pengguna $pengguna, int $idEvent, string $jenis, array $filters = []): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);
        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        $event = Event::find($idEvent);
        if (! $event) {
            return [
                'error'  => 'Event tidak ditemukan',
                'status' => 404,
            ];
        }

        // Hanya pemilik event yang dapat mengakses daftar organisasi untuk event-nya
        if ($event->id_organisasi !== $organisasi->id_organisasi) {
            return [
                'error'  => 'Anda tidak memiliki hak akses untuk mengelola ajakan event ini',
                'status' => 403,
            ];
        }

        // Query organisasi yang aktif dan bukan diri sendiri
        $query = Organisasi::with(['universitas:id_universitas,nama_universitas', 'bidang:id_bidang,nama_bidang'])
            ->where('status_organisasi', 'aktif')
            ->where('id_organisasi', '!=', $organisasi->id_organisasi);

        // Filter pencarian nama / singkatan
        if (! empty($filters['q']) || ! empty($filters['search'])) {
            $keyword = $filters['q'] ?? $filters['search'];
            $query->where('nama_organisasi', 'like', "%{$keyword}%");
        }

        // Filter universitas
        if (! empty($filters['id_universitas'])) {
            $query->where('id_universitas', (int) $filters['id_universitas']);
        }

        // Filter jenis organisasi (bem, hmj, ukm)
        if (! empty($filters['jenis_organisasi'])) {
            $query->where('jenis_organisasi', $filters['jenis_organisasi']);
        }

        // Filter bidang organisasi
        if (! empty($filters['id_bidang'])) {
            $query->where('id_bidang', (int) $filters['id_bidang']);
        }

        $organisasiList = $query->orderBy('nama_organisasi', 'asc')->get();

        // Ambil status ajakan untuk event dan jenis ini
        $ajakanMap = Ajakan::where('id_event', $event->id)
            ->where('jenis', $jenis)
            ->get()
            ->keyBy('id_organisasi_tujuan');

        $resultData = $organisasiList->map(function ($org) use ($ajakanMap) {
            $ajakan = $ajakanMap->get($org->id_organisasi);
            $statusAjakan = $ajakan ? $ajakan->status : 'belum_diajak';

            return [
                'id_organisasi'    => $org->id_organisasi,
                'nama_organisasi'  => $org->nama_organisasi,
                'jenis_organisasi' => $org->jenis_organisasi,
                'logo'             => $org->logo,
                'universitas'      => $org->universitas ? [
                    'id'   => $org->universitas->id_universitas,
                    'nama' => $org->universitas->nama_universitas,
                ] : null,
                'bidang'           => $org->bidang ? [
                    'id'   => $org->bidang->id_bidang,
                    'nama' => $org->bidang->nama_bidang,
                ] : null,
                'status_ajakan'    => $statusAjakan,
                'id_ajakan'        => $ajakan?->id,
                'dapat_diajak'     => in_array($statusAjakan, ['belum_diajak', 'ditolak', 'dibatalkan']),
            ];
        });

        return [
            'data' => $resultData,
        ];
    }

    /**
     * ORMV-1-22 & ORMV-1-23:
     * Kirim ajakan kolaborasi atau undangan ke satu atau lebih organisasi.
     */
    public function kirimAjakan(Pengguna $pengguna, int $idEvent, string $jenis, array $organisasiIds): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);
        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        $event = Event::find($idEvent);
        if (! $event) {
            return [
                'error'  => 'Event tidak ditemukan',
                'status' => 404,
            ];
        }

        if ($event->id_organisasi !== $organisasi->id_organisasi) {
            return [
                'error'  => 'Anda tidak memiliki hak untuk mengirim ajakan/undangan pada event ini',
                'status' => 403,
            ];
        }

        // Pastikan organisasi_ids tidak kosong
        $targetIds = array_values(array_unique(array_filter(array_map('intval', $organisasiIds))));
        if (empty($targetIds)) {
            return [
                'error'  => 'Pilih minimal satu organisasi tujuan',
                'status' => 422,
            ];
        }

        // Cegah mengajak diri sendiri
        if (in_array($organisasi->id_organisasi, $targetIds, true)) {
            $labelAksi = $jenis === 'kolaborator' ? 'mengajak' : 'mengundang';
            return [
                'error'  => "Tidak dapat {$labelAksi} organisasi Anda sendiri",
                'status' => 422,
            ];
        }

        // Ambil data organisasi target
        $targetList = Organisasi::whereIn('id_organisasi', $targetIds)
            ->where('status_organisasi', 'aktif')
            ->get()
            ->keyBy('id_organisasi');

        if ($targetList->count() !== count($targetIds)) {
            return [
                'error'  => 'Beberapa organisasi tujuan tidak valid atau tidak aktif',
                'status' => 422,
            ];
        }

        // Periksa duplikasi atau status yang masih menunggu / sudah diterima
        $existingAjakan = Ajakan::where('id_event', $event->id)
            ->where('jenis', $jenis)
            ->whereIn('id_organisasi_tujuan', $targetIds)
            ->get()
            ->keyBy('id_organisasi_tujuan');

        foreach ($targetIds as $tid) {
            $orgTarget = $targetList->get($tid);
            $namaTarget = $orgTarget ? $orgTarget->nama_organisasi : "ID {$tid}";

            if ($existing = $existingAjakan->get($tid)) {
                if ($existing->status === 'menunggu') {
                    $labelPesan = $jenis === 'kolaborator'
                        ? "Organisasi '{$namaTarget}' masih memiliki ajakan kolaborasi yang berstatus menunggu"
                        : "Organisasi '{$namaTarget}' masih memiliki undangan yang berstatus menunggu";
                    return [
                        'error'  => $labelPesan,
                        'status' => 422,
                    ];
                }

                if ($existing->status === 'diterima') {
                    $labelPesan = $jenis === 'kolaborator'
                        ? "Organisasi '{$namaTarget}' sudah menjadi kolaborator dalam event ini"
                        : "Organisasi '{$namaTarget}' sudah menerima undangan untuk event ini";
                    return [
                        'error'  => $labelPesan,
                        'status' => 422,
                    ];
                }
            }
        }

        // Proses penyimpanan ajakan dan pembuatan notifikasi dalam DB Transaction
        return DB::transaction(function () use ($organisasi, $event, $jenis, $targetIds, $targetList) {
            $createdAjakan = [];
            $labelPeran = $jenis === 'kolaborator' ? 'kolaborator' : 'undangan';
            $labelNotif = $jenis === 'kolaborator' ? 'ajakan kolaborasi' : 'undangan';

            foreach ($targetIds as $tid) {
                $ajakan = Ajakan::updateOrCreate(
                    [
                        'id_event'             => $event->id,
                        'id_organisasi_tujuan' => $tid,
                        'jenis'                => $jenis,
                    ],
                    [
                        'id_organisasi_pengirim' => $organisasi->id_organisasi,
                        'status'                 => 'menunggu',
                        'alasan_penolakan'       => null,
                        'djawab_pada'            => null,
                    ]
                );

                Notifikasi::create([
                    'id_organisasi' => $tid,
                    'id_event'      => $event->id,
                    'id_ajakan'     => $ajakan->id,
                    'pesan'         => "Organisasi {$organisasi->nama_organisasi} mengirimkan {$labelNotif} sebagai {$labelPeran} dalam event: {$event->judul}",
                ]);

                $ajakan->load(['tujuan:id_organisasi,nama_organisasi,logo,jenis_organisasi']);
                $createdAjakan[] = $ajakan;
            }

            $pesanSukses = $jenis === 'kolaborator'
                ? 'Ajakan kolaborasi berhasil dikirim'
                : 'Undangan organisasi berhasil dikirim';

            return [
                'data' => [
                    'pesan'   => $pesanSukses,
                    'ajakan'  => $createdAjakan,
                ],
            ];
        });
    }

    /**
     * ORMV-1-22 & ORMV-1-23:
     * Batalkan ajakan atau undangan yang belum dijawab ('menunggu').
     */
    public function batalkanAjakan(Pengguna $pengguna, int $idEvent, int $idAjakan): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);
        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        $ajakan = Ajakan::where('id', $idAjakan)
            ->where('id_event', $idEvent)
            ->first();

        if (! $ajakan) {
            return [
                'error'  => 'Ajakan atau undangan tidak ditemukan',
                'status' => 404,
            ];
        }

        if ($ajakan->id_organisasi_pengirim !== $organisasi->id_organisasi) {
            return [
                'error'  => 'Anda tidak memiliki hak untuk membatalkan ajakan/undangan ini',
                'status' => 403,
            ];
        }

        if ($ajakan->status !== 'menunggu') {
            return [
                'error'  => "Hanya ajakan/undangan berstatus 'menunggu' yang dapat dibatalkan",
                'status' => 422,
            ];
        }

        $ajakan->update([
            'status' => 'dibatalkan',
        ]);

        $label = $ajakan->jenis === 'kolaborator' ? 'Ajakan kolaborasi' : 'Undangan';

        return [
            'data' => [
                'pesan' => "{$label} berhasil dibatalkan",
                'ajakan' => $ajakan->fresh(['tujuan:id_organisasi,nama_organisasi']),
            ],
        ];
    }

    /**
     * ORMV-1-28:
     * Lihat daftar Ajakan dan Undangan yang diterima oleh organisasi pengguna login.
     */
    public function getAjakanMasuk(Pengguna $pengguna, array $filters = []): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);
        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        $query = Ajakan::with([
            'event' => function ($q) {
                $q->with([
                    'kategori:id,nama_kategori',
                    'posterUtama:id,id_event,path_file,nama_file_asli',
                ]);
            },
            'pengirim' => function ($q) {
                $q->with('universitas:id_universitas,nama_universitas')
                    ->select('id_organisasi', 'id_universitas', 'nama_organisasi', 'jenis_organisasi', 'logo');
            },
        ])
        ->where('id_organisasi_tujuan', $organisasi->id_organisasi);

        // Filter berdasarkan status jika ada
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter berdasarkan jenis jika ada (kolaborator/undangan)
        if (! empty($filters['jenis'])) {
            $query->where('jenis', $filters['jenis']);
        }

        $ajakanList = $query->orderBy('dibuat_pada', 'desc')->get();

        if ($ajakanList->isEmpty()) {
            return [
                'data' => [],
            ];
        }

        $formatted = $ajakanList->map(function ($item) {
            $ev = $item->event;
            $pengirim = $item->pengirim;

            return [
                'id'               => $item->id,
                'jenis'            => $item->jenis,
                'status'           => $item->status,
                'alasan_penolakan' => $item->alasan_penolakan,
                'dibuat_pada'      => $item->dibuat_pada,
                'djawab_pada'      => $item->djawab_pada,
                'event'            => $ev ? [
                    'id'                 => $ev->id,
                    'judul'              => $ev->judul,
                    'deskripsi'          => $ev->deskripsi,
                    'tanggal_event'      => $ev->tanggal_event,
                    'nama_kategori'      => $ev->kategori?->nama_kategori,
                    'status_pendaftaran' => $ev->status_pendaftaran,
                    'poster_utama'       => $ev->posterUtama ? [
                        'id'        => $ev->posterUtama->id,
                        'path_file' => $ev->posterUtama->path_file,
                    ] : null,
                ] : null,
                'organisasi_pengirim' => $pengirim ? [
                    'id'               => $pengirim->id_organisasi,
                    'nama_organisasi'  => $pengirim->nama_organisasi,
                    'jenis_organisasi' => $pengirim->jenis_organisasi,
                    'logo'             => $pengirim->logo,
                    'nama_universitas' => $pengirim->universitas?->nama_universitas,
                ] : null,
            ];
        });

        return [
            'data' => $formatted,
        ];
    }

    /**
     * ORMV-1-29:
     * Lihat detail satu ajakan/undangan masuk untuk ditampilkan pada popup dialog.
     */
    public function getDetailAjakanMasuk(Pengguna $pengguna, int $idAjakan): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);
        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        $ajakan = Ajakan::with([
            'event' => function ($q) {
                $q->with([
                    'kategori:id,nama_kategori',
                    'posters',
                    'kolaborator.tujuan:id_organisasi,nama_organisasi,logo,jenis_organisasi',
                ]);
            },
            'pengirim' => function ($q) {
                $q->with([
                    'universitas:id_universitas,nama_universitas',
                    'kontak',
                ]);
            },
        ])
        ->where('id', $idAjakan)
        ->where('id_organisasi_tujuan', $organisasi->id_organisasi)
        ->first();

        if (! $ajakan) {
            return [
                'error'  => 'Ajakan atau undangan tidak ditemukan',
                'status' => 404,
            ];
        }

        return [
            'data' => $ajakan,
        ];
    }

    /**
     * ORMV-1-29:
     * Jawab ajakan atau undangan (terima atau tolak dengan alasan).
     */
    public function jawabAjakan(Pengguna $pengguna, int $idAjakan, string $keputusan, ?string $alasanPenolakan = null): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);
        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        $ajakan = Ajakan::with('event')->find($idAjakan);
        if (! $ajakan) {
            return [
                'error'  => 'Ajakan atau undangan tidak ditemukan',
                'status' => 404,
            ];
        }

        // Validasi organisasi penerima
        if ($ajakan->id_organisasi_tujuan !== $organisasi->id_organisasi) {
            return [
                'error'  => 'Anda tidak memiliki hak untuk menjawab ajakan/undangan ini',
                'status' => 403,
            ];
        }

        // Hanya ajakan berstatus 'menunggu' yang dapat dijawab
        if ($ajakan->status !== 'menunggu') {
            return [
                'error'  => 'Ajakan atau undangan ini sudah pernah dijawab sebelumnya atau telah dibatalkan',
                'status' => 422,
            ];
        }

        $event = $ajakan->event;
        $labelJenis = $ajakan->jenis === 'kolaborator' ? 'ajakan kolaborasi' : 'undangan';

        // Logika penolakan
        if ($keputusan === 'tolak') {
            $alasan = trim((string) $alasanPenolakan);
            if (empty($alasan)) {
                return [
                    'error'  => 'Alasan penolakan wajib diisi jika menolak ajakan atau undangan',
                    'status' => 422,
                ];
            }

            return DB::transaction(function () use ($organisasi, $ajakan, $event, $alasan, $labelJenis) {
                $ajakan->update([
                    'status'           => 'ditolak',
                    'alasan_penolakan' => $alasan,
                    'djawab_pada'      => now(),
                ]);

                // Kirim notifikasi ke organisasi pengirim beserta alasan
                Notifikasi::create([
                    'id_organisasi' => $ajakan->id_organisasi_pengirim,
                    'id_event'      => $event?->id,
                    'id_ajakan'     => $ajakan->id,
                    'pesan'         => "Organisasi {$organisasi->nama_organisasi} telah menolak {$labelJenis} untuk event: '{$event?->judul}'. Alasan: {$alasan}",
                ]);

                return [
                    'data' => [
                        'pesan'  => "{$labelJenis} berhasil ditolak",
                        'ajakan' => $ajakan->fresh(['event', 'pengirim']),
                    ],
                ];
            });
        }

        // Logika penerimaan
        if ($keputusan === 'terima') {
            return DB::transaction(function () use ($organisasi, $ajakan, $event, $labelJenis) {
                $ajakan->update([
                    'status'           => 'diterima',
                    'alasan_penolakan' => null,
                    'djawab_pada'      => now(),
                ]);

                // Kirim notifikasi ke organisasi pengirim
                Notifikasi::create([
                    'id_organisasi' => $ajakan->id_organisasi_pengirim,
                    'id_event'      => $event?->id,
                    'id_ajakan'     => $ajakan->id,
                    'pesan'         => "Organisasi {$organisasi->nama_organisasi} telah menerima {$labelJenis} untuk event: '{$event?->judul}'",
                ]);

                return [
                    'data' => [
                        'pesan'  => "{$labelJenis} berhasil diterima",
                        'ajakan' => $ajakan->fresh(['event', 'pengirim']),
                    ],
                ];
            });
        }

        return [
            'error'  => 'Keputusan tidak valid',
            'status' => 422,
        ];
    }

    /**
     * Dapatkan daftar ajakan/undangan yang dikirim oleh pembuat event (rekap status pengirim).
     */
    public function getAjakanEvent(Pengguna $pengguna, int $idEvent, ?string $jenis = null): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);
        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        $event = Event::find($idEvent);
        if (! $event) {
            return [
                'error'  => 'Event tidak ditemukan',
                'status' => 404,
            ];
        }

        if ($event->id_organisasi !== $organisasi->id_organisasi) {
            return [
                'error'  => 'Anda tidak memiliki hak untuk melihat ajakan event ini',
                'status' => 403,
            ];
        }

        $query = Ajakan::with(['tujuan:id_organisasi,nama_organisasi,logo,jenis_organisasi'])
            ->where('id_event', $event->id)
            ->where('id_organisasi_pengirim', $organisasi->id_organisasi);

        if ($jenis) {
            $query->where('jenis', $jenis);
        }

        $list = $query->orderBy('dibuat_pada', 'desc')->get();

        return [
            'data' => $list,
        ];
    }
}
