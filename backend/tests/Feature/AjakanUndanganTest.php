<?php

namespace Tests\Feature;

use App\Models\Ajakan;
use App\Models\BidangOrganisasi;
use App\Models\Event;
use App\Models\KategoriEvent;
use App\Models\Notifikasi;
use App\Models\Organisasi;
use App\Models\Pengguna;
use App\Models\Universitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AjakanUndanganTest extends TestCase
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
    }

    private function buatAkunOrganisasi(string $nama = 'Organisasi Test', string $jenis = 'bem', string $statusOrg = 'aktif'): array
    {
        $univ = Universitas::firstOrCreate(['nama_universitas' => 'Universitas ' . uniqid()]);
        $bidang = BidangOrganisasi::firstOrCreate(['nama_bidang' => 'Teknologi Informasi']);

        $user = Pengguna::factory()->akunOrganisasi()->create([
            'status_akun' => 'aktif',
        ]);

        $org = Organisasi::create([
            'id_universitas'     => $univ->id_universitas,
            'id_bidang'          => $bidang->id_bidang,
            'id_akun_organisasi' => $user->id_pengguna,
            'nama_organisasi'    => $nama . ' ' . uniqid(),
            'jenis_organisasi'   => $jenis,
            'nomor_telepon'      => '081234567890',
            'deskripsi'          => 'Deskripsi ' . $nama,
            'status_organisasi'  => $statusOrg,
        ]);

        $roleName = ($jenis === 'bem') ? 'akun_bem' : 'akun_hmj_ukm';
        $user->syncRoles($roleName);

        $token = $user->createToken('auth_token', ['*'])->plainTextToken;

        return [$user, $org, $token];
    }

    private function buatEvent(Organisasi $pemilik, string $judul = 'Event Test'): Event
    {
        return Event::create([
            'id_organisasi'       => $pemilik->id_organisasi,
            'id_kategori'         => KategoriEvent::first()->id,
            'judul'               => $judul,
            'deskripsi'           => 'Deskripsi acara: ' . $judul,
            'tanggal_event'       => now()->addDays(7)->format('Y-m-d'),
            'tautan_pendaftaran'  => 'https://example.com/daftar',
            'periode_mulai'       => now()->subDay()->format('Y-m-d H:i:s'),
            'periode_selesai'     => now()->addDays(5)->format('Y-m-d H:i:s'),
            'jangkauan_publikasi' => 'multi_universitas',
        ]);
    }

    // ==========================================
    // ORMV-1-22: AJAK KOLABORASI
    // ==========================================

    public function test_organisasi_tersedia_kolaborasi_dengan_filter_dan_search(): void
    {
        [$userPembuat, $orgPembuat, $tokenPembuat] = $this->buatAkunOrganisasi('BEM Utama', 'bem');
        [$userTarget1, $orgTarget1] = $this->buatAkunOrganisasi('Himpunan Informatika', 'hmj');
        [$userTarget2, $orgTarget2] = $this->buatAkunOrganisasi('UKM Robotika', 'ukm');

        $event = $this->buatEvent($orgPembuat, 'Grand Workshop Tech');

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenPembuat)
            ->getJson("/api/organisasi/event/{$event->id}/organisasi-tersedia?jenis=kolaborator&search=Informatika");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daftar organisasi tersedia berhasil diambil',
            ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals($orgTarget1->id_organisasi, $data[0]['id_organisasi']);
        $this->assertEquals('belum_diajak', $data[0]['status_ajakan']);
        $this->assertTrue($data[0]['dapat_diajak']);
    }

    public function test_ajak_kolaborasi_berhasil_multi_organisasi(): void
    {
        [$userPembuat, $orgPembuat, $tokenPembuat] = $this->buatAkunOrganisasi('BEM Utama', 'bem');
        [$userTarget1, $orgTarget1] = $this->buatAkunOrganisasi('Himpunan Informatika', 'hmj');
        [$userTarget2, $orgTarget2] = $this->buatAkunOrganisasi('UKM Musik', 'ukm');

        $event = $this->buatEvent($orgPembuat, 'Festival Budaya');

        $payload = [
            'organisasi_ids' => [
                $orgTarget1->id_organisasi,
                $orgTarget2->id_organisasi,
            ],
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenPembuat)
            ->postJson("/api/organisasi/event/{$event->id}/ajak-kolaborasi", $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Ajakan kolaborasi berhasil dikirim',
            ]);

        $this->assertDatabaseHas('ajakan', [
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $orgPembuat->id_organisasi,
            'id_organisasi_tujuan'   => $orgTarget1->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'menunggu',
        ]);

        $this->assertDatabaseHas('ajakan', [
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $orgPembuat->id_organisasi,
            'id_organisasi_tujuan'   => $orgTarget2->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'menunggu',
        ]);

        // Verifikasi notifikasi terkirim ke kedua organisasi
        $this->assertDatabaseHas('notifikasi', [
            'id_organisasi' => $orgTarget1->id_organisasi,
            'id_event'      => $event->id,
        ]);
        $this->assertDatabaseHas('notifikasi', [
            'id_organisasi' => $orgTarget2->id_organisasi,
            'id_event'      => $event->id,
        ]);
    }

    public function test_ajak_kolaborasi_gagal_jika_belum_memilih_organisasi(): void
    {
        [$userPembuat, $orgPembuat, $tokenPembuat] = $this->buatAkunOrganisasi('BEM Utama', 'bem');
        $event = $this->buatEvent($orgPembuat);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenPembuat)
            ->postJson("/api/organisasi/event/{$event->id}/ajak-kolaborasi", [
                'organisasi_ids' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['organisasi_ids']]);
    }

    public function test_ajak_kolaborasi_gagal_jika_mengajak_diri_sendiri(): void
    {
        [$userPembuat, $orgPembuat, $tokenPembuat] = $this->buatAkunOrganisasi('BEM Utama', 'bem');
        $event = $this->buatEvent($orgPembuat);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenPembuat)
            ->postJson("/api/organisasi/event/{$event->id}/ajak-kolaborasi", [
                'organisasi_ids' => [$orgPembuat->id_organisasi],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Tidak dapat mengajak organisasi Anda sendiri',
            ]);
    }

    public function test_ajak_kolaborasi_gagal_jika_sudah_menjadi_kolaborator(): void
    {
        [$userPembuat, $orgPembuat, $tokenPembuat] = $this->buatAkunOrganisasi('BEM Utama', 'bem');
        [$userTarget, $orgTarget] = $this->buatAkunOrganisasi('Himpunan SI', 'hmj');
        $event = $this->buatEvent($orgPembuat);

        // Organisasi target sudah diterima sebagai kolaborator
        Ajakan::create([
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $orgPembuat->id_organisasi,
            'id_organisasi_tujuan'   => $orgTarget->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'diterima',
            'djawab_pada'            => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenPembuat)
            ->postJson("/api/organisasi/event/{$event->id}/ajak-kolaborasi", [
                'organisasi_ids' => [$orgTarget->id_organisasi],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
        $this->assertStringContainsString('sudah menjadi kolaborator', $response->json('message'));
    }

    public function test_ajak_kolaborasi_gagal_jika_masih_memiliki_ajakan_berstatus_menunggu(): void
    {
        [$userPembuat, $orgPembuat, $tokenPembuat] = $this->buatAkunOrganisasi('BEM Utama', 'bem');
        [$userTarget, $orgTarget] = $this->buatAkunOrganisasi('Himpunan SI', 'hmj');
        $event = $this->buatEvent($orgPembuat);

        // Status masih menunggu
        Ajakan::create([
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $orgPembuat->id_organisasi,
            'id_organisasi_tujuan'   => $orgTarget->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'menunggu',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenPembuat)
            ->postJson("/api/organisasi/event/{$event->id}/ajak-kolaborasi", [
                'organisasi_ids' => [$orgTarget->id_organisasi],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
        $this->assertStringContainsString('masih memiliki ajakan kolaborasi yang berstatus menunggu', $response->json('message'));
    }

    public function test_batalkan_ajakan_kolaborasi_berhasil(): void
    {
        [$userPembuat, $orgPembuat, $tokenPembuat] = $this->buatAkunOrganisasi('BEM Utama', 'bem');
        [$userTarget, $orgTarget] = $this->buatAkunOrganisasi('Himpunan SI', 'hmj');
        $event = $this->buatEvent($orgPembuat);

        $ajakan = Ajakan::create([
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $orgPembuat->id_organisasi,
            'id_organisasi_tujuan'   => $orgTarget->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'menunggu',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenPembuat)
            ->postJson("/api/organisasi/event/{$event->id}/kolaborasi/{$ajakan->id}/batalkan");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Ajakan kolaborasi berhasil dibatalkan',
            ]);

        $this->assertDatabaseHas('ajakan', [
            'id'     => $ajakan->id,
            'status' => 'dibatalkan',
        ]);
    }

    public function test_batalkan_ajakan_kolaborasi_gagal_jika_status_bukan_menunggu(): void
    {
        [$userPembuat, $orgPembuat, $tokenPembuat] = $this->buatAkunOrganisasi('BEM Utama', 'bem');
        [$userTarget, $orgTarget] = $this->buatAkunOrganisasi('Himpunan SI', 'hmj');
        $event = $this->buatEvent($orgPembuat);

        $ajakan = Ajakan::create([
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $orgPembuat->id_organisasi,
            'id_organisasi_tujuan'   => $orgTarget->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'diterima',
            'djawab_pada'            => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenPembuat)
            ->postJson("/api/organisasi/event/{$event->id}/kolaborasi/{$ajakan->id}/batalkan");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => "Hanya ajakan/undangan berstatus 'menunggu' yang dapat dibatalkan",
            ]);
    }

    // ==========================================
    // ORMV-1-23: UNDANG ORGANISASI
    // ==========================================

    public function test_undang_organisasi_berhasil(): void
    {
        [$userPembuat, $orgPembuat, $tokenPembuat] = $this->buatAkunOrganisasi('BEM Utama', 'bem');
        [$userTarget, $orgTarget] = $this->buatAkunOrganisasi('BEM Kampus Luar', 'bem');
        $event = $this->buatEvent($orgPembuat, 'Seminar Internasional');

        $payload = [
            'organisasi_ids' => [$orgTarget->id_organisasi],
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenPembuat)
            ->postJson("/api/organisasi/event/{$event->id}/undang", $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Undangan organisasi berhasil dikirim',
            ]);

        $this->assertDatabaseHas('ajakan', [
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $orgPembuat->id_organisasi,
            'id_organisasi_tujuan'   => $orgTarget->id_organisasi,
            'jenis'                  => 'undangan',
            'status'                 => 'menunggu',
        ]);

        $this->assertDatabaseHas('notifikasi', [
            'id_organisasi' => $orgTarget->id_organisasi,
            'id_event'      => $event->id,
        ]);
    }

    public function test_undang_organisasi_gagal_jika_masih_berstatus_menunggu(): void
    {
        [$userPembuat, $orgPembuat, $tokenPembuat] = $this->buatAkunOrganisasi('BEM Utama', 'bem');
        [$userTarget, $orgTarget] = $this->buatAkunOrganisasi('BEM Kampus Luar', 'bem');
        $event = $this->buatEvent($orgPembuat);

        Ajakan::create([
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $orgPembuat->id_organisasi,
            'id_organisasi_tujuan'   => $orgTarget->id_organisasi,
            'jenis'                  => 'undangan',
            'status'                 => 'menunggu',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenPembuat)
            ->postJson("/api/organisasi/event/{$event->id}/undang", [
                'organisasi_ids' => [$orgTarget->id_organisasi],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
        $this->assertStringContainsString('masih memiliki undangan yang berstatus menunggu', $response->json('message'));
    }

    public function test_batalkan_undangan_berhasil(): void
    {
        [$userPembuat, $orgPembuat, $tokenPembuat] = $this->buatAkunOrganisasi('BEM Utama', 'bem');
        [$userTarget, $orgTarget] = $this->buatAkunOrganisasi('BEM Kampus Luar', 'bem');
        $event = $this->buatEvent($orgPembuat);

        $ajakan = Ajakan::create([
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $orgPembuat->id_organisasi,
            'id_organisasi_tujuan'   => $orgTarget->id_organisasi,
            'jenis'                  => 'undangan',
            'status'                 => 'menunggu',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenPembuat)
            ->postJson("/api/organisasi/event/{$event->id}/undangan/{$ajakan->id}/batalkan");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Undangan berhasil dibatalkan',
            ]);

        $this->assertDatabaseHas('ajakan', [
            'id'     => $ajakan->id,
            'status' => 'dibatalkan',
        ]);
    }

    // ==========================================
    // ORMV-1-28: LIHAT AJAKAN DAN UNDANGAN
    // ==========================================

    public function test_lihat_ajakan_masuk_berisi_data_kartu(): void
    {
        [$userPengirim, $orgPengirim] = $this->buatAkunOrganisasi('BEM Pengirim', 'bem');
        [$userPenerima, $orgPenerima, $tokenPenerima] = $this->buatAkunOrganisasi('HMJ Penerima', 'hmj');
        $event = $this->buatEvent($orgPengirim, 'Lomba Inovasi Nasional');

        Ajakan::create([
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $orgPengirim->id_organisasi,
            'id_organisasi_tujuan'   => $orgPenerima->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'menunggu',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenPenerima)
            ->getJson('/api/organisasi/ajakan-masuk');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daftar ajakan masuk berhasil diambil',
            ]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('kolaborator', $data[0]['jenis']);
        $this->assertEquals('menunggu', $data[0]['status']);
        $this->assertEquals('Lomba Inovasi Nasional', $data[0]['event']['judul']);
        $this->assertEquals($orgPengirim->nama_organisasi, $data[0]['organisasi_pengirim']['nama_organisasi']);
    }

    public function test_lihat_ajakan_masuk_ketika_kosong(): void
    {
        [$userPenerima, $orgPenerima, $tokenPenerima] = $this->buatAkunOrganisasi('HMJ Penerima', 'hmj');

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenPenerima)
            ->getJson('/api/organisasi/ajakan-masuk');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tidak ada ajakan/undangan terbaru',
                'data'    => [],
            ]);
    }

    // ==========================================
    // ORMV-1-29: JAWAB AJAKAN DAN UNDANGAN
    // ==========================================

    public function test_lihat_detail_ajakan_masuk_popup(): void
    {
        [$userPengirim, $orgPengirim] = $this->buatAkunOrganisasi('BEM Pengirim', 'bem');
        [$userPenerima, $orgPenerima, $tokenPenerima] = $this->buatAkunOrganisasi('HMJ Penerima', 'hmj');
        $event = $this->buatEvent($orgPengirim, 'Seminar AI 2026');

        $ajakan = Ajakan::create([
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $orgPengirim->id_organisasi,
            'id_organisasi_tujuan'   => $orgPenerima->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'menunggu',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenPenerima)
            ->getJson("/api/organisasi/ajakan-masuk/{$ajakan->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Detail ajakan berhasil diambil',
            ]);

        $this->assertEquals('Seminar AI 2026', $response->json('data.event.judul'));
    }

    public function test_terima_ajakan_kolaborasi_berhasil_dan_notifikasi_terkirim(): void
    {
        [$userPengirim, $orgPengirim] = $this->buatAkunOrganisasi('BEM Pengirim', 'bem');
        [$userPenerima, $orgPenerima, $tokenPenerima] = $this->buatAkunOrganisasi('HMJ Penerima', 'hmj');
        $event = $this->buatEvent($orgPengirim, 'Hackathon Kampus');

        $ajakan = Ajakan::create([
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $orgPengirim->id_organisasi,
            'id_organisasi_tujuan'   => $orgPenerima->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'menunggu',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenPenerima)
            ->postJson("/api/organisasi/ajakan-masuk/{$ajakan->id}/jawab", [
                'keputusan' => 'terima',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'ajakan kolaborasi berhasil diterima',
            ]);

        $this->assertDatabaseHas('ajakan', [
            'id'     => $ajakan->id,
            'status' => 'diterima',
        ]);

        // Verifikasi djawab_pada terisi
        $this->assertNotNull(Ajakan::find($ajakan->id)->djawab_pada);

        // Verifikasi notifikasi hasil jawaban terkirim ke pengirim
        $this->assertDatabaseHas('notifikasi', [
            'id_organisasi' => $orgPengirim->id_organisasi,
            'id_event'      => $event->id,
        ]);

        // Verifikasi has_kolaborasi pada event bernilai true
        $this->assertTrue($event->fresh()->has_kolaborasi);
    }

    public function test_terima_undangan_berhasil_dan_event_visibel_untuk_organisasi(): void
    {
        [$userPengirim, $orgPengirim] = $this->buatAkunOrganisasi('BEM Pengirim', 'bem');
        [$userPenerima, $orgPenerima, $tokenPenerima] = $this->buatAkunOrganisasi('HMJ Penerima', 'hmj');
        $event = $this->buatEvent($orgPengirim, 'Studi Banding');

        $ajakan = Ajakan::create([
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $orgPengirim->id_organisasi,
            'id_organisasi_tujuan'   => $orgPenerima->id_organisasi,
            'jenis'                  => 'undangan',
            'status'                 => 'menunggu',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenPenerima)
            ->postJson("/api/organisasi/ajakan-masuk/{$ajakan->id}/jawab", [
                'keputusan' => 'terima',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'undangan berhasil diterima',
            ]);

        $this->assertDatabaseHas('ajakan', [
            'id'     => $ajakan->id,
            'status' => 'diterima',
        ]);

        // Verifikasi query scope visibilitas jelajah event: event terlihat oleh penerima undangan
        $visibel = Event::visibelUntukOrganisasi($orgPenerima->id_organisasi)->where('id', $event->id)->exists();
        $this->assertTrue($visibel);
    }

    public function test_tolak_ajakan_berhasil_dengan_alasan_penolakan(): void
    {
        [$userPengirim, $orgPengirim] = $this->buatAkunOrganisasi('BEM Pengirim', 'bem');
        [$userPenerima, $orgPenerima, $tokenPenerima] = $this->buatAkunOrganisasi('HMJ Penerima', 'hmj');
        $event = $this->buatEvent($orgPengirim, 'Expo Karir');

        $ajakan = Ajakan::create([
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $orgPengirim->id_organisasi,
            'id_organisasi_tujuan'   => $orgPenerima->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'menunggu',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenPenerima)
            ->postJson("/api/organisasi/ajakan-masuk/{$ajakan->id}/jawab", [
                'keputusan'        => 'tolak',
                'alasan_penolakan' => 'Jadwal bertabrakan dengan agenda internal organisasi.',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'ajakan kolaborasi berhasil ditolak',
            ]);

        $this->assertDatabaseHas('ajakan', [
            'id'               => $ajakan->id,
            'status'           => 'ditolak',
            'alasan_penolakan' => 'Jadwal bertabrakan dengan agenda internal organisasi.',
        ]);

        // Verifikasi notifikasi hasil jawaban penolakan beserta alasan terkirim ke pengirim
        $notif = Notifikasi::where('id_organisasi', $orgPengirim->id_organisasi)
            ->where('id_event', $event->id)
            ->first();
        $this->assertNotNull($notif);
        $this->assertStringContainsString('Jadwal bertabrakan', $notif->pesan);
    }

    public function test_tolak_ajakan_gagal_jika_alasan_penolakan_kosong(): void
    {
        [$userPengirim, $orgPengirim] = $this->buatAkunOrganisasi('BEM Pengirim', 'bem');
        [$userPenerima, $orgPenerima, $tokenPenerima] = $this->buatAkunOrganisasi('HMJ Penerima', 'hmj');
        $event = $this->buatEvent($orgPengirim);

        $ajakan = Ajakan::create([
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $orgPengirim->id_organisasi,
            'id_organisasi_tujuan'   => $orgPenerima->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'menunggu',
        ]);

        // Alasan penolakan kosong
        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenPenerima)
            ->postJson("/api/organisasi/ajakan-masuk/{$ajakan->id}/jawab", [
                'keputusan'        => 'tolak',
                'alasan_penolakan' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['alasan_penolakan']]);
    }

    public function test_jawab_ajakan_gagal_jika_status_bukan_menunggu(): void
    {
        [$userPengirim, $orgPengirim] = $this->buatAkunOrganisasi('BEM Pengirim', 'bem');
        [$userPenerima, $orgPenerima, $tokenPenerima] = $this->buatAkunOrganisasi('HMJ Penerima', 'hmj');
        $event = $this->buatEvent($orgPengirim);

        $ajakan = Ajakan::create([
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $orgPengirim->id_organisasi,
            'id_organisasi_tujuan'   => $orgPenerima->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'dibatalkan',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenPenerima)
            ->postJson("/api/organisasi/ajakan-masuk/{$ajakan->id}/jawab", [
                'keputusan' => 'terima',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Ajakan atau undangan ini sudah pernah dijawab sebelumnya atau telah dibatalkan',
            ]);
    }
}
