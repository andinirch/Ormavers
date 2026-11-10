<?php

namespace App\Services;

use App\Models\Ajakan;
use App\Models\Event;
use App\Models\EventPoster;
use App\Models\Notifikasi;
use App\Models\Organisasi;
use App\Models\Pengguna;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventService
{
    /**
     * Dapatkan organisasi aktif milik akun pengguna yang login.
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
     * ORMV-1-21: Buat Event Baru
     */
    public function buatEvent(Pengguna $pengguna, array $data, array $posters = []): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);
        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        if (empty($posters)) {
            return [
                'error'  => 'Minimal unggah 1 poster event',
                'status' => 422,
            ];
        }

        if (count($posters) > 3) {
            return [
                'error'  => 'Maksimal 3 poster yang dapat diunggah',
                'status' => 422,
            ];
        }

        return DB::transaction(function () use ($organisasi, $data, $posters) {
            $event = Event::create([
                'id_organisasi'       => $organisasi->id_organisasi,
                'id_kategori'         => $data['id_kategori'] ?? null,
                'judul'               => $data['judul'],
                'deskripsi'           => $data['deskripsi'],
                'tanggal_event'       => $data['tanggal_event'],
                'tag'                 => $data['tag'] ?? null,
                'tautan_pendaftaran'  => $data['tautan_pendaftaran'],
                'periode_mulai'       => $data['periode_mulai'],
                'periode_selesai'     => $data['periode_selesai'],
                'jangkauan_publikasi' => $data['jangkauan_publikasi'],
                'token_sharelink'     => Str::random(32),
            ]);

            // Simpan poster-poster
            $utamaIndex = isset($data['poster_utama_index']) ? (int) $data['poster_utama_index'] : 0;
            if ($utamaIndex < 0 || $utamaIndex >= count($posters)) {
                $utamaIndex = 0;
            }

            foreach ($posters as $idx => $file) {
                if ($file instanceof UploadedFile) {
                    $path = $file->store('event/poster', 'public');
                    $url = Storage::disk('public')->url($path);

                    EventPoster::create([
                        'id_event'       => $event->id,
                        'nama_file_asli' => $file->getClientOriginalName(),
                        'path_file'      => $url,
                        'ukuran_file'    => $file->getSize(),
                        'is_utama'       => ($idx === $utamaIndex),
                    ]);
                }
            }

            // Keterlibatan Organisasi Lain (Ajakan)
            if (! empty($data['organisasi_terlibat']) && is_array($data['organisasi_terlibat'])) {
                foreach ($data['organisasi_terlibat'] as $item) {
                    $idTujuan = (int) $item['id_organisasi'];
                    $jenis = $item['jenis'];

                    // Jangan ajak diri sendiri
                    if ($idTujuan === $organisasi->id_organisasi) {
                        continue;
                    }

                    $ajakan = Ajakan::firstOrCreate(
                        [
                            'id_event'             => $event->id,
                            'id_organisasi_tujuan' => $idTujuan,
                            'jenis'                => $jenis,
                        ],
                        [
                            'id_organisasi_pengirim' => $organisasi->id_organisasi,
                            'status'                 => 'menunggu',
                        ]
                    );

                    // Buat Notifikasi untuk organisasi tujuan
                    $labelPeran = $jenis === 'kolaborator' ? 'kolaborator' : 'undangan';
                    Notifikasi::create([
                        'id_organisasi' => $idTujuan,
                        'id_event'      => $event->id,
                        'id_ajakan'     => $ajakan->id,
                        'pesan'         => "Organisasi {$organisasi->nama_organisasi} mengajak Anda sebagai {$labelPeran} dalam event: {$event->judul}",
                    ]);
                }
            }

            $event->load(['kategori', 'posters', 'ajakan.tujuan']);

            return [
                'data' => $event,
            ];
        });
    }

    /**
     * ORMV-1-24: Edit Event
     */
    public function editEvent(Pengguna $pengguna, int $idEvent, array $data, ?array $posters = null): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);
        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        $event = Event::with('posters')->find($idEvent);
        if (! $event) {
            return [
                'error'  => 'Event tidak ditemukan',
                'status' => 404,
            ];
        }

        // Hanya pembuat yang berhak mengedit
        if ($event->id_organisasi !== $organisasi->id_organisasi) {
            return [
                'error'  => 'Anda tidak memiliki hak untuk mengedit event ini',
                'status' => 403,
            ];
        }

        return DB::transaction(function () use ($organisasi, $event, $data, $posters) {
            // Update data event dasar
            $updateFields = [];
            foreach ([
                'judul', 'deskripsi', 'tanggal_event', 'id_kategori',
                'tag', 'tautan_pendaftaran', 'periode_mulai',
                'periode_selesai', 'jangkauan_publikasi',
            ] as $field) {
                if (array_key_exists($field, $data)) {
                    $updateFields[$field] = $data[$field];
                }
            }

            if (! empty($updateFields)) {
                $event->update($updateFields);
            }

            // Hapus poster yang ditandai untuk dihapus
            if (! empty($data['hapus_poster_id']) && is_array($data['hapus_poster_id'])) {
                $posterHapus = EventPoster::where('id_event', $event->id)
                    ->whereIn('id', $data['hapus_poster_id'])
                    ->get();

                foreach ($posterHapus as $ph) {
                    $this->deletePosterFile($ph->path_file);
                    $ph->delete();
                }
            }

            // Tambah poster baru bila ada
            if (! empty($posters)) {
                $jumlahPosterSaatIni = EventPoster::where('id_event', $event->id)->count();
                $sisaSlot = 3 - $jumlahPosterSaatIni;

                if (count($posters) > $sisaSlot) {
                    throw new \Exception("Maksimal total poster adalah 3. Anda hanya dapat menambah {$sisaSlot} poster lagi.");
                }

                foreach ($posters as $file) {
                    if ($file instanceof UploadedFile) {
                        $path = $file->store('event/poster', 'public');
                        $url = Storage::disk('public')->url($path);

                        EventPoster::create([
                            'id_event'       => $event->id,
                            'nama_file_asli' => $file->getClientOriginalName(),
                            'path_file'      => $url,
                            'ukuran_file'    => $file->getSize(),
                            'is_utama'       => false,
                        ]);
                    }
                }
            }

            // Atur poster utama jika diberikan ID
            if (! empty($data['poster_utama_id'])) {
                EventPoster::where('id_event', $event->id)->update(['is_utama' => false]);
                EventPoster::where('id_event', $event->id)
                    ->where('id', $data['poster_utama_id'])
                    ->update(['is_utama' => true]);
            } else {
                // Pastikan minimal ada 1 poster utama jika poster masih ada
                $adaUtama = EventPoster::where('id_event', $event->id)->where('is_utama', true)->exists();
                if (! $adaUtama) {
                    $posterPertama = EventPoster::where('id_event', $event->id)->first();
                    if ($posterPertama) {
                        $posterPertama->update(['is_utama' => true]);
                    }
                }
            }

            // Keterlibatan Organisasi Tambahan (Ajakan Baru)
            if (! empty($data['organisasi_terlibat']) && is_array($data['organisasi_terlibat'])) {
                foreach ($data['organisasi_terlibat'] as $item) {
                    $idTujuan = (int) $item['id_organisasi'];
                    $jenis = $item['jenis'];

                    if ($idTujuan === $organisasi->id_organisasi) {
                        continue;
                    }

                    $ajakanEksis = Ajakan::where('id_event', $event->id)
                        ->where('id_organisasi_tujuan', $idTujuan)
                        ->first();

                    if (! $ajakanEksis) {
                        $ajakan = Ajakan::create([
                            'id_event'               => $event->id,
                            'id_organisasi_pengirim' => $organisasi->id_organisasi,
                            'id_organisasi_tujuan'   => $idTujuan,
                            'jenis'                  => $jenis,
                            'status'                 => 'menunggu',
                        ]);

                        $labelPeran = $jenis === 'kolaborator' ? 'kolaborator' : 'undangan';
                        Notifikasi::create([
                            'id_organisasi' => $idTujuan,
                            'id_event'      => $event->id,
                            'id_ajakan'     => $ajakan->id,
                            'pesan'         => "Organisasi {$organisasi->nama_organisasi} mengajak Anda sebagai {$labelPeran} dalam event: {$event->judul}",
                        ]);
                    }
                }
            }

            // Kirim notifikasi perubahan ke seluruh kolaborator dan undangan yang telah menerima
            $ajakanDiterima = Ajakan::where('id_event', $event->id)
                ->where('status', 'diterima')
                ->get();

            foreach ($ajakanDiterima as $ajakan) {
                Notifikasi::create([
                    'id_organisasi' => $ajakan->id_organisasi_tujuan,
                    'id_event'      => $event->id,
                    'id_ajakan'     => $ajakan->id,
                    'pesan'         => "Event \"{$event->judul}\" telah diperbarui oleh {$organisasi->nama_organisasi}.",
                ]);
            }

            $event->load(['kategori', 'posters', 'ajakan.tujuan']);

            return [
                'data' => $event,
            ];
        });
    }

    /**
     * ORMV-1-25: Hapus Event
     */
    public function hapusEvent(Pengguna $pengguna, int $idEvent): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);
        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        $event = Event::with('posters')->find($idEvent);
        if (! $event) {
            return [
                'error'  => 'Event tidak ditemukan',
                'status' => 404,
            ];
        }

        // Hanya pembuat yang berhak menghapus
        if ($event->id_organisasi !== $organisasi->id_organisasi) {
            return [
                'error'  => 'Anda tidak memiliki hak untuk menghapus event ini',
                'status' => 403,
            ];
        }

        return DB::transaction(function () use ($organisasi, $event) {
            $judulEvent = $event->judul;

            // 1. Ambil kolaborator & undangan yang sudah menerima untuk dikirimi notifikasi penghapusan
            $ajakanDiterima = Ajakan::where('id_event', $event->id)
                ->where('status', 'diterima')
                ->get();

            foreach ($ajakanDiterima as $ajakan) {
                Notifikasi::create([
                    'id_organisasi' => $ajakan->id_organisasi_tujuan,
                    'id_event'      => null, // event akan terhapus
                    'id_ajakan'     => null,
                    'pesan'         => "Event \"{$judulEvent}\" telah dihapus oleh pembuat ({$organisasi->nama_organisasi}).",
                ]);
            }

            // 2. Batalkan ajakan yang berstatus 'menunggu'
            Ajakan::where('id_event', $event->id)
                ->where('status', 'menunggu')
                ->delete();

            // 3. Hapus file fisik poster di storage
            foreach ($event->posters as $poster) {
                $this->deletePosterFile($poster->path_file);
            }

            // 4. Hapus event (relasi poster & ajakan sisa terhapus via database cascade)
            $event->delete();

            return [
                'data' => [
                    'pesan' => "Event '{$judulEvent}' berhasil dihapus",
                ],
            ];
        });
    }

    /**
     * ORMV-1-26: Lihat Event Saya
     * Query UNION: Event yang dibuat sendiri + Event sebagai kolaborator aktif ('status = diterima')
     */
    public function getEventSaya(Pengguna $pengguna): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);
        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        $idOrganisasi = $organisasi->id_organisasi;

        // Query 1: Event yang dibuat oleh organisasi ini (peran = 'pembuat')
        $queryDibuat = DB::table('event as e')
            ->select([
                'e.id',
                'e.judul',
                'e.deskripsi',
                'e.tanggal_event',
                'e.id_kategori',
                'e.tag',
                'e.tautan_pendaftaran',
                'e.periode_mulai',
                'e.periode_selesai',
                'e.jangkauan_publikasi',
                'e.token_sharelink',
                'e.dibuat_pada',
                DB::raw("'pembuat' as peran_organisasi"),
            ])
            ->where('e.id_organisasi', $idOrganisasi);

        // Query 2: Event yang diikuti sebagai kolaborator aktif (peran = 'kolaborator')
        $queryKolaborator = DB::table('event as e')
            ->join('ajakan as a', 'e.id', '=', 'a.id_event')
            ->select([
                'e.id',
                'e.judul',
                'e.deskripsi',
                'e.tanggal_event',
                'e.id_kategori',
                'e.tag',
                'e.tautan_pendaftaran',
                'e.periode_mulai',
                'e.periode_selesai',
                'e.jangkauan_publikasi',
                'e.token_sharelink',
                'e.dibuat_pada',
                DB::raw("'kolaborator' as peran_organisasi"),
            ])
            ->where('a.id_organisasi_tujuan', $idOrganisasi)
            ->where('a.jenis', 'kolaborator')
            ->where('a.status', 'diterima');

        // UNION keduanya dan urutkan tanggal event DESC
        $rawEvents = $queryDibuat
            ->union($queryKolaborator)
            ->orderBy('tanggal_event', 'desc')
            ->get();

        if ($rawEvents->isEmpty()) {
            return [
                'data' => [],
            ];
        }

        // Ambil ID semua event untuk preload poster utama dan kategori secara efisien
        $eventIds = $rawEvents->pluck('id')->all();
        $posters = EventPoster::whereIn('id_event', $eventIds)
            ->where('is_utama', true)
            ->get()
            ->keyBy('id_event');

        // Preload poster cadangan jika ada event yang belum punya poster utama
        $fallbackPosters = EventPoster::whereIn('id_event', $eventIds)
            ->get()
            ->groupBy('id_event');

        $kategoriList = DB::table('kategori_event')->get()->keyBy('id');

        $now = now();
        $formatted = $rawEvents->map(function ($ev) use ($posters, $fallbackPosters, $kategoriList, $now) {
            $mulai = $ev->periode_mulai ? \Carbon\Carbon::parse($ev->periode_mulai) : null;
            $selesai = $ev->periode_selesai ? \Carbon\Carbon::parse($ev->periode_selesai) : null;

            if ($mulai && $now->lt($mulai)) {
                $statusPendaftaran = 'Belum Dibuka';
            } elseif ($selesai && $now->gt($selesai)) {
                $statusPendaftaran = 'Ditutup';
            } else {
                $statusPendaftaran = 'Dibuka';
            }

            $posterUtama = $posters->get($ev->id);
            if (! $posterUtama && isset($fallbackPosters[$ev->id])) {
                $posterUtama = $fallbackPosters[$ev->id]->first();
            }

            $kategori = $ev->id_kategori ? ($kategoriList->get($ev->id_kategori)?->nama_kategori ?? null) : null;

            return [
                'id'                  => (int) $ev->id,
                'judul'               => $ev->judul,
                'deskripsi'           => $ev->deskripsi,
                'tanggal_event'       => $ev->tanggal_event,
                'id_kategori'         => $ev->id_kategori,
                'nama_kategori'       => $kategori,
                'tag'                 => $ev->tag,
                'tautan_pendaftaran'  => $ev->tautan_pendaftaran,
                'periode_mulai'       => $ev->periode_mulai,
                'periode_selesai'     => $ev->periode_selesai,
                'status_pendaftaran'  => $statusPendaftaran,
                'jangkauan_publikasi' => $ev->jangkauan_publikasi,
                'token_sharelink'     => $ev->token_sharelink,
                'peran_organisasi'    => $ev->peran_organisasi,
                'poster_utama'        => $posterUtama ? [
                    'id'             => $posterUtama->id,
                    'nama_file_asli' => $posterUtama->nama_file_asli,
                    'path_file'      => $posterUtama->path_file,
                ] : null,
                'dibuat_pada'         => $ev->dibuat_pada,
            ];
        });

        return [
            'data' => $formatted,
        ];
    }

    /**
     * Ambil rincian lengkap satu event (untuk edit formulir atau detail).
     */
    public function getDetailEvent(Pengguna $pengguna, int $idEvent): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);
        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        $event = Event::with([
            'organisasi:id_organisasi,nama_organisasi,logo',
            'kategori:id,nama_kategori',
            'posters',
            'ajakan.tujuan:id_organisasi,nama_organisasi,logo',
        ])->find($idEvent);

        if (! $event) {
            return [
                'error'  => 'Event tidak ditemukan',
                'status' => 404,
            ];
        }

        // Tentukan peran organisasi pengguna terhadap event ini
        $peran = null;
        if ($event->id_organisasi === $organisasi->id_organisasi) {
            $peran = 'pembuat';
        } else {
            $ajakan = Ajakan::where('id_event', $event->id)
                ->where('id_organisasi_tujuan', $organisasi->id_organisasi)
                ->first();
            if ($ajakan && $ajakan->status === 'diterima') {
                $peran = $ajakan->jenis;
            }
        }

        $data = $event->toArray();
        $data['peran_organisasi'] = $peran;

        return [
            'data' => $data,
        ];
    }

    /**
     * Helper untuk menghapus berkas poster dari disk public.
     */
    protected function deletePosterFile(string $url): void
    {
        $parsedPath = parse_url($url, PHP_URL_PATH);
        $relativeStoragePath = preg_replace('#^/storage/#', '', $parsedPath ?? $url);

        if ($relativeStoragePath && Storage::disk('public')->exists($relativeStoragePath)) {
            Storage::disk('public')->delete($relativeStoragePath);
        }
    }
}
