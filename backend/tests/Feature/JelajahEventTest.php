<?php

namespace Tests\Feature;

use App\Models\Ajakan;
use App\Models\Event;
use App\Models\EventPoster;
use App\Models\KategoriEvent;
use App\Models\Keanggotaan;
use App\Models\Organisasi;
use App\Models\Pengguna;
use App\Models\ProfilAnggota;
use App\Models\Universitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JelajahEventTest extends TestCase
{
    use RefreshDatabase;

    private Universitas $univ1;
    private Universitas $univ2;

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
        KategoriEvent::firstOrCreate(['nama_kategori' => 'Lomba']);

        $this->univ1 = Universitas::firstOrCreate(['nama_universitas' => 'Universitas Alfa']);
        $this->univ2 = Universitas::firstOrCreate(['nama_universitas' => 'Universitas Beta']);
    }

    private function buatAkunOrganisasi(string $jenis = 'bem', ?Universitas $univ = null, string $statusAkun = 'aktif', string $statusOrg = 'aktif'): array
    {
        $univ = $univ ?? $this->univ1;
        $user = Pengguna::factory()->akunOrganisasi()->create(['status_akun' => $statusAkun]);
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

        $token = $user->createToken('auth_token', ['*'])->plainTextToken;
        return [$user, $org, $token];
    }

    private function buatAnggota(?Universitas $univ = null, ?Organisasi $org = null, string $statusKeanggotaan = 'disetujui'): array
    {
        $univ = $univ ?? $this->univ1;
        $user = Pengguna::factory()->create(['peran' => 'anggota', 'status_akun' => 'aktif']);
        $user->syncRoles('anggota');
        ProfilAnggota::create([
            'id_pengguna'    => $user->id_pengguna,
            'nim'            => '2026' . random_int(10000000, 99999999),
            'nomor_whatsapp' => '081234567890',
            'foto_ktm'       => 'ktm/test.jpg',
            'id_universitas' => $univ->id_universitas,
        ]);

        if ($org) {
            Keanggotaan::create([
                'id_pengguna'          => $user->id_pengguna,
                'id_organisasi'        => $org->id_organisasi,
                'status_keanggotaan'   => $statusKeanggotaan,
                'tanggal_pendaftaran'  => now(),
            ]);
        }

        $token = $user->createToken('auth_token', ['*'])->plainTextToken;
        return [$user, $token];
    }

    private function buatAdministrator(): array
    {
        $user = Pengguna::factory()->create(['peran' => 'administrator', 'status_akun' => 'aktif']);
        $user->syncRoles('administrator');
        $token = $user->createToken('auth_token', ['*'])->plainTextToken;
        return [$user, $token];
    }

    private function buatEvent(Organisasi $org, string $jangkauan = 'multi_universitas', ?string $tanggalEvent = null, ?int $idKategori = null): Event
    {
        $tanggalEvent = $tanggalEvent ?? now()->addDays(30)->toDateString();
        $event = Event::create([
            'id_organisasi'       => $org->id_organisasi,
            'id_kategori'         => $idKategori ?? KategoriEvent::first()->id,
            'judul'               => 'Event Test ' . uniqid(),
            'deskripsi'           => 'Deskripsi event test.',
            'tanggal_event'       => $tanggalEvent,
            'tautan_pendaftaran'  => 'https://example.com/daftar',
            'periode_mulai'       => now()->subDay(),
            'periode_selesai'     => now()->addDays(10),
            'jangkauan_publikasi' => $jangkauan,
            'token_sharelink'     => \Illuminate\Support\Str::random(32),
        ]);

        EventPoster::create([
            'id_event'       => $event->id,
            'nama_file_asli' => 'poster.jpg',
            'path_file'      => 'event/poster/test.jpg',
            'ukuran_file'    => 12345,
            'is_utama'       => true,
        ]);

        return $event;
    }

    // ==========================================
    // ORMV-1-30: Lihat Daftar Event (Jelajah)
    // ==========================================

    public function test_jelajah_event_multi_universitas_dapat_diakses_semua_peran(): void
    {
        [$userBem, $orgBem, $tokenBem] = $this->buatAkunOrganisasi('bem', $this->univ1);
        $event = $this->buatEvent($orgBem, 'multi_universitas');

        // Anggota dari universitas lain bisa melihat
        [$userAnggota, $tokenAnggota] = $this->buatAnggota($this->univ2);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenAnggota)
            ->getJson('/api/events');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data');
    }

    public function test_jelajah_event_satu_universitas_hanya_universitas_sama(): void
    {
        [$userBem, $orgBem, $tokenBem] = $this->buatAkunOrganisasi('bem', $this->univ1);
        $event = $this->buatEvent($orgBem, 'satu_universitas');

        // Anggota dari universitas yang sama bisa melihat
        [$userSama, $tokenSama] = $this->buatAnggota($this->univ1);
        $response1 = $this->withHeader('Authorization', 'Bearer ' . $tokenSama)
            ->getJson('/api/events');
        $response1->assertStatus(200)
            ->assertJsonCount(1, 'data');

        auth()->forgetGuards();

        // Anggota dari universitas lain TIDAK bisa melihat
        [$userLain, $tokenLain] = $this->buatAnggota($this->univ2);
        $response2 = $this->withHeader('Authorization', 'Bearer ' . $tokenLain)
            ->getJson('/api/events');
        $response2->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_jelajah_event_internal_hanya_anggota_organisasi(): void
    {
        [$userBem, $orgBem, $tokenBem] = $this->buatAkunOrganisasi('bem', $this->univ1);
        $event = $this->buatEvent($orgBem, 'internal');

        // Anggota yang disetujui di organisasi bisa melihat
        [$userAnggota, $tokenAnggota] = $this->buatAnggota($this->univ1, $orgBem, 'disetujui');
        $response1 = $this->withHeader('Authorization', 'Bearer ' . $tokenAnggota)
            ->getJson('/api/events');
        $response1->assertStatus(200)
            ->assertJsonCount(1, 'data');

        auth()->forgetGuards();

        // Anggota tanpa keanggotaan di organisasi TIDAK bisa melihat
        [$userLuar, $tokenLuar] = $this->buatAnggota($this->univ1);
        $response2 = $this->withHeader('Authorization', 'Bearer ' . $tokenLuar)
            ->getJson('/api/events');
        $response2->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_jelajah_event_internal_kolaborator_diterima_bisa_melihat(): void
    {
        [$userBem, $orgBem, $tokenBem] = $this->buatAkunOrganisasi('bem', $this->univ1);
        [$userHmj, $orgHmj, $tokenHmj] = $this->buatAkunOrganisasi('hmj', $this->univ2);
        $event = $this->buatEvent($orgBem, 'internal');

        // Ajakan kolaborasi diterima
        Ajakan::create([
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $orgBem->id_organisasi,
            'id_organisasi_tujuan'   => $orgHmj->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'diterima',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenHmj)
            ->getJson('/api/events');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_administrator_bisa_melihat_semua_event(): void
    {
        [$userBem, $orgBem, $tokenBem] = $this->buatAkunOrganisasi('bem', $this->univ1);
        $this->buatEvent($orgBem, 'internal');
        $this->buatEvent($orgBem, 'satu_universitas');
        $this->buatEvent($orgBem, 'multi_universitas');

        [$userAdmin, $tokenAdmin] = $this->buatAdministrator();

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenAdmin)
            ->getJson('/api/events');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_jelajah_event_tanpa_token_401(): void
    {
        $response = $this->getJson('/api/events');
        $response->assertStatus(401);
    }

    public function test_jelajah_event_belum_ada_event(): void
    {
        [$userAnggota, $tokenAnggota] = $this->buatAnggota($this->univ1);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenAnggota)
            ->getJson('/api/events');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Belum ada event',
                'data'    => [],
            ]);
    }

    public function test_jelajah_event_menampilkan_status_pendaftaran_otomatis(): void
    {
        [$userBem, $orgBem, $tokenBem] = $this->buatAkunOrganisasi('bem', $this->univ1);

        // Belum Dibuka: periode mulai di masa depan
        $eventBelumDibuka = Event::create([
            'id_organisasi'       => $orgBem->id_organisasi,
            'id_kategori'         => KategoriEvent::first()->id,
            'judul'               => 'Event Belum Dibuka',
            'deskripsi'           => 'Deskripsi.',
            'tanggal_event'       => now()->addDays(30)->toDateString(),
            'tautan_pendaftaran'  => 'https://example.com/daftar',
            'periode_mulai'       => now()->addDays(5),
            'periode_selesai'     => now()->addDays(20),
            'jangkauan_publikasi' => 'multi_universitas',
        ]);
        EventPoster::create([
            'id_event' => $eventBelumDibuka->id, 'nama_file_asli' => 'p.jpg',
            'path_file' => 'event/poster/p.jpg', 'ukuran_file' => 100, 'is_utama' => true,
        ]);

        // Ditutup: periode selesai di masa lalu
        $eventDitutup = Event::create([
            'id_organisasi'       => $orgBem->id_organisasi,
            'id_kategori'         => KategoriEvent::first()->id,
            'judul'               => 'Event Ditutup',
            'deskripsi'           => 'Deskripsi.',
            'tanggal_event'       => now()->addDays(30)->toDateString(),
            'tautan_pendaftaran'  => 'https://example.com/daftar',
            'periode_mulai'       => now()->subDays(20),
            'periode_selesai'     => now()->subDays(1),
            'jangkauan_publikasi' => 'multi_universitas',
        ]);
        EventPoster::create([
            'id_event' => $eventDitutup->id, 'nama_file_asli' => 'p.jpg',
            'path_file' => 'event/poster/p2.jpg', 'ukuran_file' => 100, 'is_utama' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenBem)
            ->getJson('/api/events');

        $response->assertStatus(200);

        $data = collect($response->json('data'));
        $belumDibuka = $data->firstWhere('judul', 'Event Belum Dibuka');
        $ditutup = $data->firstWhere('judul', 'Event Ditutup');

        $this->assertEquals('Belum Dibuka', $belumDibuka['status_pendaftaran']);
        $this->assertEquals('Ditutup', $ditutup['status_pendaftaran']);
    }

    // ==========================================
    // ORMV-1-31: Cari dan Filter Event
    // ==========================================

    public function test_pencarian_event_berdasarkan_kata_kunci(): void
    {
        [$userBem, $orgBem, $tokenBem] = $this->buatAkunOrganisasi('bem', $this->univ1);

        $event1 = Event::create([
            'id_organisasi' => $orgBem->id_organisasi, 'id_kategori' => KategoriEvent::first()->id,
            'judul' => 'Seminar Nasional AI', 'deskripsi' => 'Tentang kecerdasan buatan.',
            'tanggal_event' => now()->addDays(10)->toDateString(), 'tautan_pendaftaran' => 'https://a.com',
            'periode_mulai' => now()->subDay(), 'periode_selesai' => now()->addDays(5),
            'jangkauan_publikasi' => 'multi_universitas',
        ]);
        EventPoster::create(['id_event' => $event1->id, 'nama_file_asli' => 'p.jpg', 'path_file' => 'x.jpg', 'ukuran_file' => 100, 'is_utama' => true]);

        $event2 = Event::create([
            'id_organisasi' => $orgBem->id_organisasi, 'id_kategori' => KategoriEvent::first()->id,
            'judul' => 'Workshop Laravel', 'deskripsi' => 'Belajar framework PHP.',
            'tanggal_event' => now()->addDays(15)->toDateString(), 'tautan_pendaftaran' => 'https://b.com',
            'periode_mulai' => now()->subDay(), 'periode_selesai' => now()->addDays(10),
            'jangkauan_publikasi' => 'multi_universitas',
        ]);
        EventPoster::create(['id_event' => $event2->id, 'nama_file_asli' => 'p.jpg', 'path_file' => 'y.jpg', 'ukuran_file' => 100, 'is_utama' => true]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenBem)
            ->getJson('/api/events?q=Seminar');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['judul' => 'Seminar Nasional AI']);
    }

    public function test_filter_event_berdasarkan_kategori(): void
    {
        [$userBem, $orgBem, $tokenBem] = $this->buatAkunOrganisasi('bem', $this->univ1);

        $katSeminar = KategoriEvent::where('nama_kategori', 'Seminar')->first();
        $katWorkshop = KategoriEvent::where('nama_kategori', 'Workshop')->first();

        $event1 = $this->buatEvent($orgBem, 'multi_universitas', null, $katSeminar->id);
        $event2 = $this->buatEvent($orgBem, 'multi_universitas', null, $katWorkshop->id);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenBem)
            ->getJson('/api/events?id_kategori=' . $katSeminar->id);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_filter_event_berdasarkan_rentang_tanggal(): void
    {
        [$userBem, $orgBem, $tokenBem] = $this->buatAkunOrganisasi('bem', $this->univ1);

        $this->buatEvent($orgBem, 'multi_universitas', '2026-12-01');
        $this->buatEvent($orgBem, 'multi_universitas', '2027-01-15');

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenBem)
            ->getJson('/api/events?tanggal_mulai=2026-11-01&tanggal_selesai=2026-12-31');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_pencarian_event_tidak_ditemukan(): void
    {
        [$userBem, $orgBem, $tokenBem] = $this->buatAkunOrganisasi('bem', $this->univ1);
        $this->buatEvent($orgBem, 'multi_universitas');

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenBem)
            ->getJson('/api/events?q=TidakAdaEventIni');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Event tidak ditemukan',
                'data'    => [],
            ]);
    }

    // ==========================================
    // ORMV-1-32: Lihat Detail Event
    // ==========================================

    public function test_lihat_detail_event_berhasil(): void
    {
        [$userBem, $orgBem, $tokenBem] = $this->buatAkunOrganisasi('bem', $this->univ1);
        $event = $this->buatEvent($orgBem, 'multi_universitas');

        [$userAnggota, $tokenAnggota] = $this->buatAnggota($this->univ2);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenAnggota)
            ->getJson('/api/events/' . $event->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success', 'message',
                'data' => [
                    'id', 'judul', 'deskripsi', 'tanggal_event', 'status_pendaftaran',
                    'jangkauan_publikasi', 'tautan_pendaftaran', 'organisasi',
                    'poster_utama', 'galeri_poster', 'kolaborator',
                ],
            ]);
    }

    public function test_lihat_detail_event_dengan_kolaborator(): void
    {
        [$userBem, $orgBem, $tokenBem] = $this->buatAkunOrganisasi('bem', $this->univ1);
        [$userHmj, $orgHmj, $tokenHmj] = $this->buatAkunOrganisasi('hmj', $this->univ1);
        $event = $this->buatEvent($orgBem, 'multi_universitas');

        Ajakan::create([
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $orgBem->id_organisasi,
            'id_organisasi_tujuan'   => $orgHmj->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'diterima',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenBem)
            ->getJson('/api/events/' . $event->id);

        $response->assertStatus(200);
        $kolaborator = $response->json('data.kolaborator');
        $this->assertCount(1, $kolaborator);
        $this->assertEquals($orgHmj->id_organisasi, $kolaborator[0]['id_organisasi']);
    }

    public function test_detail_event_tidak_dapat_diakses_403(): void
    {
        [$userBem, $orgBem, $tokenBem] = $this->buatAkunOrganisasi('bem', $this->univ1);
        $event = $this->buatEvent($orgBem, 'internal');

        [$userLuar, $tokenLuar] = $this->buatAnggota($this->univ2);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenLuar)
            ->getJson('/api/events/' . $event->id);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Event tidak dapat diakses',
            ]);
    }

    public function test_detail_event_tidak_ditemukan_404(): void
    {
        [$userAnggota, $tokenAnggota] = $this->buatAnggota($this->univ1);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenAnggota)
            ->getJson('/api/events/999999');

        $response->assertStatus(404);
    }

    // ==========================================
    // ORMV-1-33: Bagikan Event (Sharelink)
    // ==========================================

    public function test_bagikan_event_mendapatkan_sharelink(): void
    {
        [$userBem, $orgBem, $tokenBem] = $this->buatAkunOrganisasi('bem', $this->univ1);
        $event = $this->buatEvent($orgBem, 'multi_universitas');

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenBem)
            ->postJson('/api/events/' . $event->id . '/bagikan');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => ['token_sharelink', 'sharelink'],
            ]);
    }

    public function test_buka_sharelink_event_berhasil(): void
    {
        [$userBem, $orgBem, $tokenBem] = $this->buatAkunOrganisasi('bem', $this->univ1);
        $event = $this->buatEvent($orgBem, 'internal');

        // Anggota yang tidak punya akses langsung bisa buka via sharelink
        [$userLuar, $tokenLuar] = $this->buatAnggota($this->univ2);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenLuar)
            ->getJson('/api/events/share/' . $event->token_sharelink);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => ['id', 'judul', 'deskripsi'],
            ]);
    }

    public function test_buka_sharelink_token_tidak_valid_404(): void
    {
        [$userAnggota, $tokenAnggota] = $this->buatAnggota($this->univ1);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenAnggota)
            ->getJson('/api/events/share/tokenTidakValidSekali123');

        $response->assertStatus(404);
    }

    public function test_buka_sharelink_tanpa_login_401(): void
    {
        $response = $this->getJson('/api/events/share/sometoken');
        $response->assertStatus(401);
    }

    public function test_bagikan_event_generate_token_jika_belum_ada(): void
    {
        [$userBem, $orgBem, $tokenBem] = $this->buatAkunOrganisasi('bem', $this->univ1);

        $event = Event::create([
            'id_organisasi'       => $orgBem->id_organisasi,
            'id_kategori'         => KategoriEvent::first()->id,
            'judul'               => 'Event Tanpa Token',
            'deskripsi'           => 'Deskripsi.',
            'tanggal_event'       => now()->addDays(30)->toDateString(),
            'tautan_pendaftaran'  => 'https://example.com/daftar',
            'periode_mulai'       => now()->subDay(),
            'periode_selesai'     => now()->addDays(10),
            'jangkauan_publikasi' => 'multi_universitas',
            'token_sharelink'     => null,
        ]);
        EventPoster::create([
            'id_event' => $event->id, 'nama_file_asli' => 'p.jpg',
            'path_file' => 'x.jpg', 'ukuran_file' => 100, 'is_utama' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenBem)
            ->postJson('/api/events/' . $event->id . '/bagikan');

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data.token_sharelink'));

        $event->refresh();
        $this->assertNotNull($event->token_sharelink);
    }

    // ==========================================
    // Hak Akses Tambahan
    // ==========================================

    public function test_akun_organisasi_pembuat_bisa_lihat_event_internal(): void
    {
        [$userBem, $orgBem, $tokenBem] = $this->buatAkunOrganisasi('bem', $this->univ1);
        $event = $this->buatEvent($orgBem, 'internal');

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenBem)
            ->getJson('/api/events/' . $event->id);

        $response->assertStatus(200);
    }

    public function test_satu_universitas_kolaborator_diterima_universitas_lain_anggota_bisa_lihat(): void
    {
        [$userBem, $orgBem, $tokenBem] = $this->buatAkunOrganisasi('bem', $this->univ1);
        [$userHmj, $orgHmj, $tokenHmj] = $this->buatAkunOrganisasi('hmj', $this->univ2);
        $event = $this->buatEvent($orgBem, 'satu_universitas');

        Ajakan::create([
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $orgBem->id_organisasi,
            'id_organisasi_tujuan'   => $orgHmj->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'diterima',
        ]);

        // Anggota dari universitas kolaborator juga bisa melihat
        [$userAnggotaBeta, $tokenAnggotaBeta] = $this->buatAnggota($this->univ2);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenAnggotaBeta)
            ->getJson('/api/events');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_jelajah_event_menampilkan_struktur_data_lengkap(): void
    {
        [$userBem, $orgBem, $tokenBem] = $this->buatAkunOrganisasi('bem', $this->univ1);
        $event = $this->buatEvent($orgBem, 'multi_universitas');

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenBem)
            ->getJson('/api/events');

        $response->assertStatus(200);

        $item = $response->json('data.0');
        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('judul', $item);
        $this->assertArrayHasKey('deskripsi', $item);
        $this->assertArrayHasKey('tanggal_event', $item);
        $this->assertArrayHasKey('status_pendaftaran', $item);
        $this->assertArrayHasKey('jangkauan_publikasi', $item);
        $this->assertArrayHasKey('organisasi', $item);
        $this->assertArrayHasKey('poster_utama', $item);
        $this->assertArrayHasKey('kategori', $item);
    }
}
