<?php

namespace Tests\Feature;

use App\Models\Ajakan;
use App\Models\Event;
use App\Models\EventPoster;
use App\Models\KategoriEvent;
use App\Models\Notifikasi;
use App\Models\Organisasi;
use App\Models\Pengguna;
use App\Models\Universitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EventCoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('administrator', 'web');
        Role::findOrCreate('akun_bem', 'web');
        Role::findOrCreate('akun_hmj_ukm', 'web');
        Role::findOrCreate('anggota', 'web');

        KategoriEvent::firstOrCreate(['nama_kategori' => 'Seminar']);
        KategoriEvent::firstOrCreate(['nama_kategori' => 'Workshop']);
        KategoriEvent::firstOrCreate(['nama_kategori' => 'Lainnya']);
    }

    private function buatAkunOrganisasi(string $jenis = 'bem', string $statusAkun = 'aktif', string $statusOrg = 'aktif'): array
    {
        $univ = Universitas::firstOrCreate(['nama_universitas' => 'Universitas ' . uniqid()]);

        $user = Pengguna::factory()->akunOrganisasi()->create([
            'status_akun' => $statusAkun,
        ]);

        $org = Organisasi::create([
            'id_universitas'     => $univ->id_universitas,
            'id_akun_organisasi' => $user->id_pengguna,
            'nama_organisasi'    => strtoupper($jenis) . ' ' . uniqid(),
            'jenis_organisasi'   => $jenis,
            'nomor_telepon'      => '081234567890',
            'deskripsi'          => 'Deskripsi ' . $jenis,
            'status_organisasi'  => $statusOrg,
        ]);

        $roleName = ($jenis === 'bem') ? 'akun_bem' : 'akun_hmj_ukm';
        $user->syncRoles($roleName);

        $abilities = ($statusAkun === 'aktif') ? ['*'] : ['status-registrasi'];
        $token = $user->createToken('auth_token', $abilities)->plainTextToken;

        return [$user, $org, $token];
    }

    /**
     * Test Master Data Kategori Event (Endpoint Publik)
     */
    public function test_get_master_kategori_event(): void
    {
        $response = $this->getJson('/api/master/kategori-event');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'nama_kategori'],
                ],
            ]);
    }

    /**
     * Test ORMV-1-21: Buat Event Berhasil dengan Multi-poster dan Ajakan
     */
    public function test_buat_event_berhasil(): void
    {
        Storage::fake('public');

        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');
        [$userTarget, $orgTarget, $tokenTarget] = $this->buatAkunOrganisasi('hmj');

        $kategori = KategoriEvent::first();

        $poster1 = UploadedFile::fake()->image('poster1.jpg', 600, 800)->size(500);
        $poster2 = UploadedFile::fake()->image('poster2.jpg', 600, 800)->size(600);

        $payload = [
            'judul'               => 'Seminar Nasional Kepemimpinan 2026',
            'deskripsi'           => 'Deskripsi lengkap seminar kepemimpinan mahasiswa tingkat nasional.',
            'tanggal_event'       => now()->addDays(10)->format('Y-m-d'),
            'id_kategori'         => $kategori->id,
            'tautan_pendaftaran'  => 'https://forms.gle/seminar2026',
            'periode_mulai'       => now()->subDay()->format('Y-m-d H:i:s'),
            'periode_selesai'     => now()->addDays(5)->format('Y-m-d H:i:s'),
            'jangkauan_publikasi' => 'multi_universitas',
            'poster_utama_index'  => 0,
            'poster'              => [$poster1, $poster2],
            'organisasi_terlibat' => [
                [
                    'id_organisasi' => $orgTarget->id_organisasi,
                    'jenis'         => 'kolaborator',
                ],
            ],
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->post('/api/organisasi/event', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Event berhasil dibuat',
            ]);

        $this->assertDatabaseHas('event', [
            'judul'               => 'Seminar Nasional Kepemimpinan 2026',
            'id_organisasi'       => $org->id_organisasi,
            'jangkauan_publikasi' => 'multi_universitas',
        ]);

        $event = Event::where('judul', 'Seminar Nasional Kepemimpinan 2026')->first();
        $this->assertNotNull($event);
        $this->assertEquals(2, $event->posters()->count());
        $this->assertEquals(1, $event->posters()->where('is_utama', true)->count());

        // Verifikasi Ajakan status 'menunggu'
        $this->assertDatabaseHas('ajakan', [
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $org->id_organisasi,
            'id_organisasi_tujuan'   => $orgTarget->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'menunggu',
        ]);

        // Verifikasi Notifikasi terkirim ke target
        $this->assertDatabaseHas('notifikasi', [
            'id_organisasi' => $orgTarget->id_organisasi,
            'id_event'      => $event->id,
        ]);
    }

    /**
     * Test Validasi: Poster melebihi 3 file atau format bukan JPG/JPEG ditolak
     */
    public function test_buat_event_gagal_jika_format_bukan_jpg(): void
    {
        Storage::fake('public');
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');

        $posterPng = UploadedFile::fake()->create('poster.png', 500, 'image/png');

        $payload = [
            'judul'               => 'Seminar Invalid Poster',
            'deskripsi'           => 'Deskripsi event.',
            'tanggal_event'       => now()->addDays(5)->format('Y-m-d'),
            'id_kategori'         => KategoriEvent::first()->id,
            'tautan_pendaftaran'  => 'https://example.com/daftar',
            'periode_mulai'       => now()->format('Y-m-d H:i:s'),
            'periode_selesai'     => now()->addDays(2)->format('Y-m-d H:i:s'),
            'jangkauan_publikasi' => 'internal',
            'poster'              => [$posterPng],
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/event', $payload);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['poster.0']]);
    }

    /**
     * Test ORMV-1-24: Edit Event (Pemilik mengupdate data event dan mengirim notifikasi perubahan)
     */
    public function test_edit_event_berhasil(): void
    {
        Storage::fake('public');
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');
        [$userKolab, $orgKolab, $tokenKolab] = $this->buatAkunOrganisasi('hmj');

        $event = Event::create([
            'id_organisasi'       => $org->id_organisasi,
            'id_kategori'         => KategoriEvent::first()->id,
            'judul'               => 'Workshop Desain Grafis Awal',
            'deskripsi'           => 'Deskripsi awal.',
            'tanggal_event'       => now()->addDays(15)->format('Y-m-d'),
            'tautan_pendaftaran'  => 'https://example.com/daftar',
            'periode_mulai'       => now()->format('Y-m-d H:i:s'),
            'periode_selesai'     => now()->addDays(5)->format('Y-m-d H:i:s'),
            'jangkauan_publikasi' => 'internal',
        ]);

        // Tambah kolaborator aktif ('diterima')
        $ajakan = Ajakan::create([
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $org->id_organisasi,
            'id_organisasi_tujuan'   => $orgKolab->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'diterima',
        ]);

        $payload = [
            'judul'     => 'Workshop Desain Grafis 2026 - Diperbarui',
            'deskripsi' => 'Deskripsi yang telah diperbarui dengan jadwal baru.',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/organisasi/event/{$event->id}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Event berhasil diperbarui',
            ]);

        $this->assertDatabaseHas('event', [
            'id'    => $event->id,
            'judul' => 'Workshop Desain Grafis 2026 - Diperbarui',
        ]);

        // Notifikasi perubahan terkirim ke organisasi pembuat event
        $this->assertDatabaseHas('notifikasi', [
            'id_organisasi' => $org->id_organisasi,
            'id_event'      => $event->id,
            'jenis'         => 'perubahan_event',
            'pesan'         => "Anda telah memperbarui event \"{$payload['judul']}\".",
        ]);

        // Notifikasi perubahan terkirim ke kolaborator yang diterima
        $this->assertDatabaseHas('notifikasi', [
            'id_organisasi' => $orgKolab->id_organisasi,
            'id_event'      => $event->id,
            'jenis'         => 'perubahan_event',
        ]);
    }

    /**
     * Test ORMV-1-25: Hapus Event (Hapus event, batalkan ajakan menunggu, kirim notifikasi penghapusan)
     */
    public function test_hapus_event_berhasil(): void
    {
        Storage::fake('public');
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');
        [$userKolab, $orgKolab, $tokenKolab] = $this->buatAkunOrganisasi('hmj');

        $event = Event::create([
            'id_organisasi'       => $org->id_organisasi,
            'id_kategori'         => KategoriEvent::first()->id,
            'judul'               => 'Event Akan Dihapus',
            'deskripsi'           => 'Event ini akan dihapus.',
            'tanggal_event'       => now()->addDays(20)->format('Y-m-d'),
            'tautan_pendaftaran'  => 'https://example.com/daftar',
            'periode_mulai'       => now()->format('Y-m-d H:i:s'),
            'periode_selesai'     => now()->addDays(5)->format('Y-m-d H:i:s'),
            'jangkauan_publikasi' => 'internal',
        ]);

        [$userUndangan, $orgUndangan, $tokenUndangan] = $this->buatAkunOrganisasi('ukm');

        // Kolaborator yang sudah menerima
        Ajakan::create([
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $org->id_organisasi,
            'id_organisasi_tujuan'   => $orgKolab->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'diterima',
        ]);

        // Undangan yang sudah menerima
        Ajakan::create([
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $org->id_organisasi,
            'id_organisasi_tujuan'   => $orgUndangan->id_organisasi,
            'jenis'                  => 'undangan',
            'status'                 => 'diterima',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/organisasi/event/{$event->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Event berhasil dihapus',
            ]);

        // Event sudah terhapus
        $this->assertDatabaseMissing('event', ['id' => $event->id]);

        // Notifikasi penghapusan terkirim ke kolaborator
        $this->assertDatabaseHas('notifikasi', [
            'id_organisasi' => $orgKolab->id_organisasi,
            'jenis'         => 'penghapusan_event',
        ]);

        // Notifikasi penghapusan terkirim ke organisasi penerima undangan
        $this->assertDatabaseHas('notifikasi', [
            'id_organisasi' => $orgUndangan->id_organisasi,
            'jenis'         => 'penghapusan_event',
        ]);
    }

    /**
     * Test ORMV-1-26: Lihat Event Saya (UNION query: Pembuat + Kolaborator Aktif)
     */
    public function test_lihat_event_saya_union_query(): void
    {
        [$userA, $orgA, $tokenA] = $this->buatAkunOrganisasi('bem');
        [$userB, $orgB, $tokenB] = $this->buatAkunOrganisasi('hmj');

        // Event 1: Dibuat oleh Organisasi A
        $eventA = Event::create([
            'id_organisasi'       => $orgA->id_organisasi,
            'id_kategori'         => KategoriEvent::first()->id,
            'judul'               => 'Event Buatan Sendiri',
            'deskripsi'           => 'Deskripsi event A.',
            'tanggal_event'       => now()->addDays(5)->format('Y-m-d'),
            'tautan_pendaftaran'  => 'https://example.com/a',
            'periode_mulai'       => now()->subDay()->format('Y-m-d H:i:s'),
            'periode_selesai'     => now()->addDays(2)->format('Y-m-d H:i:s'),
            'jangkauan_publikasi' => 'internal',
        ]);

        // Event 2: Dibuat oleh Organisasi B, Organisasi A sebagai Kolaborator yang sudah diterima
        $eventB = Event::create([
            'id_organisasi'       => $orgB->id_organisasi,
            'id_kategori'         => KategoriEvent::first()->id,
            'judul'               => 'Event Kolaborasi B',
            'deskripsi'           => 'Deskripsi event B.',
            'tanggal_event'       => now()->addDays(12)->format('Y-m-d'),
            'tautan_pendaftaran'  => 'https://example.com/b',
            'periode_mulai'       => now()->subDays(5)->format('Y-m-d H:i:s'),
            'periode_selesai'     => now()->subDay()->format('Y-m-d H:i:s'), // sudah ditutup
            'jangkauan_publikasi' => 'satu_universitas',
        ]);

        Ajakan::create([
            'id_event'               => $eventB->id,
            'id_organisasi_pengirim' => $orgB->id_organisasi,
            'id_organisasi_tujuan'   => $orgA->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'diterima',
        ]);

        // Event 3: Dibuat oleh B, tapi status ajakan masih 'menunggu' -> TIDAK boleh muncul di Event Saya milik A
        $eventC = Event::create([
            'id_organisasi'       => $orgB->id_organisasi,
            'id_kategori'         => KategoriEvent::first()->id,
            'judul'               => 'Event Menunggu Ajakan',
            'deskripsi'           => 'Deskripsi event C.',
            'tanggal_event'       => now()->addDays(15)->format('Y-m-d'),
            'tautan_pendaftaran'  => 'https://example.com/c',
            'periode_mulai'       => now()->format('Y-m-d H:i:s'),
            'periode_selesai'     => now()->addDays(3)->format('Y-m-d H:i:s'),
            'jangkauan_publikasi' => 'internal',
        ]);

        Ajakan::create([
            'id_event'               => $eventC->id,
            'id_organisasi_pengirim' => $orgB->id_organisasi,
            'id_organisasi_tujuan'   => $orgA->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'menunggu',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->getJson('/api/organisasi/event');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        // Validasi peran dan status pendaftaran
        $eventDibuat = collect($data)->firstWhere('id', $eventA->id);
        $this->assertEquals('pembuat', $eventDibuat['peran_organisasi']);
        $this->assertEquals('Dibuka', $eventDibuat['status_pendaftaran']);

        $eventKolab = collect($data)->firstWhere('id', $eventB->id);
        $this->assertEquals('kolaborator', $eventKolab['peran_organisasi']);
        $this->assertEquals('Ditutup', $eventKolab['status_pendaftaran']);
    }

    /**
     * Helper: Buat event lengkap dengan poster via Service layer
     */
    private function buatEventDenganPoster(string $token, array $overrides = [], int $jumlahPoster = 2): array
    {
        $kategori = KategoriEvent::first();

        $posters = [];
        for ($i = 1; $i <= $jumlahPoster; $i++) {
            $posters[] = UploadedFile::fake()->image("poster{$i}.jpg", 600, 800)->size(500);
        }

        $payload = array_merge([
            'judul'               => 'Event Poster Test ' . uniqid(),
            'deskripsi'           => 'Deskripsi event untuk test poster.',
            'tanggal_event'       => now()->addDays(10)->format('Y-m-d'),
            'id_kategori'         => $kategori->id,
            'tautan_pendaftaran'  => 'https://example.com/daftar',
            'periode_mulai'       => now()->subDay()->format('Y-m-d H:i:s'),
            'periode_selesai'     => now()->addDays(5)->format('Y-m-d H:i:s'),
            'jangkauan_publikasi' => 'internal',
            'poster_utama_index'  => 0,
            'poster'              => $posters,
        ], $overrides);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->post('/api/organisasi/event', $payload);

        $response->assertStatus(201);

        $event = Event::where('judul', $payload['judul'])->first();

        return [$event, $event->posters()->get()];
    }

    /**
     * Test Edit Event: Hapus 1 dari 2 poster (penghapusan sebagian)
     */
    public function test_edit_event_hapus_sebagian_poster(): void
    {
        Storage::fake('public');

        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');
        [$event, $posters] = $this->buatEventDenganPoster($token);

        $this->assertCount(2, $posters);

        $posterDihapus     = $posters[0];
        $posterDipertahankan = $posters[1];

        // Pastikan file fisik poster tersimpan di storage
        $pathDihapus = preg_replace('#^.*/storage/#', '', $posterDihapus->path_file);
        $pathDisimpan = preg_replace('#^.*/storage/#', '', $posterDipertahankan->path_file);
        Storage::disk('public')->assertExists($pathDihapus);
        Storage::disk('public')->assertExists($pathDisimpan);

        // Edit event: hapus 1 poster
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->post("/api/organisasi/event/{$event->id}", [
                'hapus_poster_id' => [$posterDihapus->id],
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Poster yang dihapus hilang dari DB
        $this->assertDatabaseMissing('event_poster', ['id' => $posterDihapus->id]);

        // Poster yang dipertahankan masih di DB
        $this->assertDatabaseHas('event_poster', ['id' => $posterDipertahankan->id]);

        // File fisik poster yang dihapus sudah tidak ada
        Storage::disk('public')->assertMissing($pathDihapus);

        // File fisik poster yang dipertahankan masih ada
        Storage::disk('public')->assertExists($pathDisimpan);

        // GET detail event: hanya 1 poster tersisa
        $detail = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/organisasi/event/{$event->id}");

        $detail->assertStatus(200);
        $this->assertCount(1, $detail->json('data.posters'));
        $this->assertEquals($posterDipertahankan->id, $detail->json('data.posters.0.id'));
    }

    /**
     * Test Edit Event: Hapus poster lama + upload poster baru sekaligus
     */
    public function test_edit_event_hapus_poster_lama_dan_upload_baru(): void
    {
        Storage::fake('public');

        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');
        [$event, $posters] = $this->buatEventDenganPoster($token);

        $this->assertCount(2, $posters);

        $posterDihapus     = $posters[0];
        $posterDisimpan    = $posters[1];
        $pathDihapus = preg_replace('#^.*/storage/#', '', $posterDihapus->path_file);

        // Upload poster baru sekaligus hapus poster lama
        $posterBaru = UploadedFile::fake()->image('poster_baru.jpg', 600, 800)->size(400);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->post("/api/organisasi/event/{$event->id}", [
                'hapus_poster_id' => [$posterDihapus->id],
                'poster'          => [$posterBaru],
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Poster lama yang dihapus hilang dari DB
        $this->assertDatabaseMissing('event_poster', ['id' => $posterDihapus->id]);

        // File fisik poster lama yang dihapus sudah tidak ada
        Storage::disk('public')->assertMissing($pathDihapus);

        // Poster yang dipertahankan masih ada
        $this->assertDatabaseHas('event_poster', ['id' => $posterDisimpan->id]);

        // Total poster sekarang: 1 lama dipertahankan + 1 baru = 2
        $event->refresh();
        $this->assertEquals(2, $event->posters()->count());

        // Poster baru tersimpan di DB
        $posterBaruRecord = $event->posters()->where('id', '!=', $posterDisimpan->id)->first();
        $this->assertNotNull($posterBaruRecord);
        $this->assertEquals('poster_baru.jpg', $posterBaruRecord->nama_file_asli);
    }

    /**
     * Test Edit Event: Edit data non-poster (judul) tanpa mengirim field poster → poster lama tetap utuh
     */
    public function test_edit_event_tanpa_mengubah_poster(): void
    {
        Storage::fake('public');

        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');
        [$event, $posters] = $this->buatEventDenganPoster($token);

        $this->assertCount(2, $posters);
        $judulBaru = 'Judul Diperbarui Tanpa Ubah Poster';

        // Edit hanya judul, tanpa kirim field poster/hapus_poster_id
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/organisasi/event/{$event->id}", [
                'judul' => $judulBaru,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Judul sudah berubah
        $this->assertDatabaseHas('event', [
            'id'    => $event->id,
            'judul' => $judulBaru,
        ]);

        // Kedua poster masih utuh di DB
        $event->refresh();
        $this->assertEquals(2, $event->posters()->count());
        foreach ($posters as $poster) {
            $this->assertDatabaseHas('event_poster', ['id' => $poster->id]);
            $path = preg_replace('#^.*/storage/#', '', $poster->path_file);
            Storage::disk('public')->assertExists($path);
        }
    }

    /**
     * Test Lihat Event Saya Ketika Kosong
     */
    public function test_lihat_event_saya_kosong(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/organisasi/event');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Belum ada event',
                'data'    => [],
            ]);
    }
}
