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

        // Notifikasi perubahan terkirim ke kolaborator yang diterima
        $this->assertDatabaseHas('notifikasi', [
            'id_organisasi' => $orgKolab->id_organisasi,
            'id_event'      => $event->id,
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

        // Kolaborator yang sudah menerima
        Ajakan::create([
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $org->id_organisasi,
            'id_organisasi_tujuan'   => $orgKolab->id_organisasi,
            'jenis'                  => 'kolaborator',
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
