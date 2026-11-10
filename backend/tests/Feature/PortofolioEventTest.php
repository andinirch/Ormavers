<?php

namespace Tests\Feature;

use App\Models\Ajakan;
use App\Models\Event;
use App\Models\EventPoster;
use App\Models\KategoriEvent;
use App\Models\Organisasi;
use App\Models\Pengguna;
use App\Models\Portofolio;
use App\Models\Universitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PortofolioEventTest extends TestCase
{
    use RefreshDatabase;

    private Universitas $univ;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('administrator', 'web');
        Role::findOrCreate('akun_bem', 'web');
        Role::findOrCreate('akun_hmj_ukm', 'web');
        Role::findOrCreate('anggota', 'web');

        KategoriEvent::firstOrCreate(['nama_kategori' => 'Seminar']);

        $this->univ = Universitas::firstOrCreate(['nama_universitas' => 'Universitas Test Portofolio']);
    }

    private function buatAkunOrganisasi(string $jenis = 'bem'): array
    {
        $user = Pengguna::factory()->akunOrganisasi()->create(['status_akun' => 'aktif']);
        $org = Organisasi::create([
            'id_universitas'     => $this->univ->id_universitas,
            'id_akun_organisasi' => $user->id_pengguna,
            'nama_organisasi'    => strtoupper($jenis) . ' ' . uniqid(),
            'jenis_organisasi'   => $jenis,
            'nomor_telepon'      => '081234567890',
            'deskripsi'          => 'Deskripsi organisasi',
            'status_organisasi'  => 'aktif',
        ]);

        $roleName = ($jenis === 'bem') ? 'akun_bem' : 'akun_hmj_ukm';
        $user->syncRoles($roleName);

        $token = $user->createToken('auth_token', ['*'])->plainTextToken;
        return [$user, $org, $token];
    }

    private function buatEventSelesai(Organisasi $org, ?string $tanggal = null): Event
    {
        $tanggal = $tanggal ?? now()->subDays(10)->toDateString();
        $event = Event::create([
            'id_organisasi'       => $org->id_organisasi,
            'id_kategori'         => KategoriEvent::first()->id,
            'judul'               => 'Event Selesai ' . uniqid(),
            'deskripsi'           => 'Deskripsi event selesai.',
            'tanggal_event'       => $tanggal,
            'tautan_pendaftaran'  => 'https://example.com/daftar',
            'periode_mulai'       => now()->subDays(30),
            'periode_selesai'     => now()->subDays(15),
            'jangkauan_publikasi' => 'multi_universitas',
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

    private function buatEventBelumSelesai(Organisasi $org): Event
    {
        $event = Event::create([
            'id_organisasi'       => $org->id_organisasi,
            'id_kategori'         => KategoriEvent::first()->id,
            'judul'               => 'Event Aktif ' . uniqid(),
            'deskripsi'           => 'Deskripsi event aktif.',
            'tanggal_event'       => now()->addDays(10)->toDateString(),
            'tautan_pendaftaran'  => 'https://example.com/daftar',
            'periode_mulai'       => now()->subDay(),
            'periode_selesai'     => now()->addDays(5),
            'jangkauan_publikasi' => 'multi_universitas',
            'token_sharelink'     => \Illuminate\Support\Str::random(32),
        ]);

        EventPoster::create([
            'id_event'       => $event->id,
            'nama_file_asli' => 'poster.jpg',
            'path_file'      => 'event/poster/active.jpg',
            'ukuran_file'    => 12345,
            'is_utama'       => true,
        ]);

        return $event;
    }

    // ==========================================
    // ORMV-1-27: Daftar Event Selesai untuk Portofolio
    // ==========================================

    public function test_daftar_event_selesai_berhasil(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');
        $eventSelesai = $this->buatEventSelesai($org);
        $eventAktif = $this->buatEventBelumSelesai($org);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/organisasi/events-selesai');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = collect($response->json('data'));
        $this->assertTrue($data->contains('id', $eventSelesai->id));
        $this->assertFalse($data->contains('id', $eventAktif->id));
    }

    public function test_daftar_event_selesai_kolaborator_diterima(): void
    {
        [$userBem, $orgBem, $tokenBem] = $this->buatAkunOrganisasi('bem');
        [$userHmj, $orgHmj, $tokenHmj] = $this->buatAkunOrganisasi('hmj');

        $event = $this->buatEventSelesai($orgBem);

        Ajakan::create([
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $orgBem->id_organisasi,
            'id_organisasi_tujuan'   => $orgHmj->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'diterima',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenHmj)
            ->getJson('/api/organisasi/events-selesai');

        $response->assertStatus(200);
        $data = collect($response->json('data'));
        $this->assertTrue($data->contains('id', $event->id));
    }

    public function test_daftar_event_selesai_menandai_sudah_masuk_portofolio(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');
        $event = $this->buatEventSelesai($org);

        Portofolio::create([
            'id_organisasi' => $org->id_organisasi,
            'id_event'      => $event->id,
            'judul'         => $event->judul,
            'tahun'         => 2026,
            'deskripsi'     => $event->deskripsi,
            'dokumentasi'   => 'https://example.com/doc.pdf',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/organisasi/events-selesai');

        $response->assertStatus(200);
        $data = collect($response->json('data'));
        $eventItem = $data->firstWhere('id', $event->id);
        $this->assertTrue($eventItem['sudah_masuk_portofolio']);
    }

    public function test_daftar_event_selesai_tanpa_token_401(): void
    {
        $response = $this->getJson('/api/organisasi/events-selesai');
        $response->assertStatus(401);
    }

    public function test_daftar_event_selesai_peran_bukan_organisasi_403(): void
    {
        $user = Pengguna::factory()->create(['peran' => 'anggota', 'status_akun' => 'aktif']);
        $user->syncRoles('anggota');
        $token = $user->createToken('auth_token', ['*'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/organisasi/events-selesai');

        $response->assertStatus(403);
    }

    // ==========================================
    // ORMV-1-27: Prefill Portofolio dari Event
    // ==========================================

    public function test_prefill_portofolio_dari_event_berhasil(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');
        $event = $this->buatEventSelesai($org);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/organisasi/events-selesai/' . $event->id . '/prefill');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => ['id_event', 'judul', 'tahun', 'deskripsi'],
            ]);

        $this->assertEquals($event->judul, $response->json('data.judul'));
    }

    public function test_prefill_portofolio_event_belum_selesai_422(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');
        $event = $this->buatEventBelumSelesai($org);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/organisasi/events-selesai/' . $event->id . '/prefill');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Event belum selesai',
            ]);
    }

    public function test_prefill_portofolio_bukan_pembuat_atau_kolaborator_403(): void
    {
        [$userBem, $orgBem, $tokenBem] = $this->buatAkunOrganisasi('bem');
        [$userHmj, $orgHmj, $tokenHmj] = $this->buatAkunOrganisasi('hmj');

        $event = $this->buatEventSelesai($orgBem);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenHmj)
            ->getJson('/api/organisasi/events-selesai/' . $event->id . '/prefill');

        $response->assertStatus(403);
    }

    // ==========================================
    // ORMV-1-27: Tambah Event ke Portofolio
    // ==========================================

    public function test_tambah_event_ke_portofolio_berhasil_dengan_string_dokumentasi(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');
        $event = $this->buatEventSelesai($org);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/events-selesai/' . $event->id . '/portofolio', [
                'dokumentasi' => 'https://example.com/foto-dokumentasi.jpg',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Portofolio berhasil ditambahkan']);

        $this->assertDatabaseHas('portofolio', [
            'id_organisasi' => $org->id_organisasi,
            'id_event'      => $event->id,
            'judul'         => $event->judul,
        ]);
    }

    public function test_tambah_event_ke_portofolio_berhasil_dengan_file_dokumentasi(): void
    {
        Storage::fake('public');
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');
        $event = $this->buatEventSelesai($org);

        $file = UploadedFile::fake()->image('dokumentasi.jpg', 800, 600)->size(500);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/events-selesai/' . $event->id . '/portofolio', [
                'dokumentasi' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('portofolio', [
            'id_organisasi' => $org->id_organisasi,
            'id_event'      => $event->id,
        ]);
    }

    public function test_tambah_event_ke_portofolio_dengan_data_kustom(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');
        $event = $this->buatEventSelesai($org);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/events-selesai/' . $event->id . '/portofolio', [
                'judul'       => 'Judul Portofolio Kustom',
                'tahun'       => 2025,
                'deskripsi'   => 'Deskripsi kustom portofolio.',
                'dokumentasi' => 'https://example.com/doc.pdf',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('portofolio', [
            'id_organisasi' => $org->id_organisasi,
            'id_event'      => $event->id,
            'judul'         => 'Judul Portofolio Kustom',
            'tahun'         => 2025,
            'deskripsi'     => 'Deskripsi kustom portofolio.',
        ]);
    }

    public function test_tambah_event_ke_portofolio_kolaborator_diterima_berhasil(): void
    {
        [$userBem, $orgBem, $tokenBem] = $this->buatAkunOrganisasi('bem');
        [$userHmj, $orgHmj, $tokenHmj] = $this->buatAkunOrganisasi('hmj');

        $event = $this->buatEventSelesai($orgBem);

        Ajakan::create([
            'id_event'               => $event->id,
            'id_organisasi_pengirim' => $orgBem->id_organisasi,
            'id_organisasi_tujuan'   => $orgHmj->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'diterima',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenHmj)
            ->postJson('/api/organisasi/events-selesai/' . $event->id . '/portofolio', [
                'dokumentasi' => 'https://example.com/doc-hmj.jpg',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('portofolio', [
            'id_organisasi' => $orgHmj->id_organisasi,
            'id_event'      => $event->id,
        ]);
    }

    public function test_tambah_event_ke_portofolio_event_belum_selesai_ditolak(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');
        $event = $this->buatEventBelumSelesai($org);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/events-selesai/' . $event->id . '/portofolio', [
                'dokumentasi' => 'https://example.com/doc.jpg',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Event belum selesai',
            ]);
    }

    public function test_tambah_event_ke_portofolio_bukan_pembuat_atau_kolaborator_ditolak(): void
    {
        [$userBem, $orgBem, $tokenBem] = $this->buatAkunOrganisasi('bem');
        [$userHmj, $orgHmj, $tokenHmj] = $this->buatAkunOrganisasi('hmj');

        $event = $this->buatEventSelesai($orgBem);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenHmj)
            ->postJson('/api/organisasi/events-selesai/' . $event->id . '/portofolio', [
                'dokumentasi' => 'https://example.com/doc.jpg',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Anda bukan pembuat atau kolaborator event ini',
            ]);
    }

    public function test_tambah_event_ke_portofolio_tanpa_dokumentasi_ditolak(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');
        $event = $this->buatEventSelesai($org);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/events-selesai/' . $event->id . '/portofolio', []);

        $response->assertStatus(422);
    }

    public function test_tambah_event_ke_portofolio_event_tidak_ditemukan_404(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/events-selesai/999999/portofolio', [
                'dokumentasi' => 'https://example.com/doc.jpg',
            ]);

        $response->assertStatus(404);
    }

    public function test_tambah_event_ke_portofolio_mengembalikan_daftar_terurut_tahun_desc(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');

        // Portofolio manual 2020
        Portofolio::create([
            'id_organisasi' => $org->id_organisasi,
            'judul'         => 'Porto Lama',
            'tahun'         => 2020,
            'deskripsi'     => 'Deskripsi.',
            'dokumentasi'   => 'https://example.com/old.jpg',
        ]);

        $event = $this->buatEventSelesai($org, '2025-06-15');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/events-selesai/' . $event->id . '/portofolio', [
                'dokumentasi' => 'https://example.com/new.jpg',
            ]);

        $response->assertStatus(201);

        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertGreaterThanOrEqual($data[1]['tahun'], $data[0]['tahun']);
    }

    public function test_tambah_event_ke_portofolio_tanpa_token_401(): void
    {
        $response = $this->postJson('/api/organisasi/events-selesai/1/portofolio', [
            'dokumentasi' => 'https://example.com/doc.jpg',
        ]);

        $response->assertStatus(401);
    }

    public function test_portofolio_event_backward_compatibility_portofolio_manual(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');

        // Tambah portofolio manual (tanpa id_event)
        $response1 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/portofolio', [
                'judul'       => 'Portofolio Manual',
                'tahun'       => 2024,
                'deskripsi'   => 'Deskripsi portofolio manual.',
                'dokumentasi' => 'https://example.com/manual.jpg',
            ]);

        $response1->assertStatus(201);
        $this->assertDatabaseHas('portofolio', [
            'id_organisasi' => $org->id_organisasi,
            'judul'         => 'Portofolio Manual',
            'id_event'      => null,
        ]);

        auth()->forgetGuards();

        // Tambah portofolio dari event
        $event = $this->buatEventSelesai($org);
        $response2 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/events-selesai/' . $event->id . '/portofolio', [
                'dokumentasi' => 'https://example.com/event-doc.jpg',
            ]);

        $response2->assertStatus(201);

        $this->assertDatabaseHas('portofolio', [
            'id_organisasi' => $org->id_organisasi,
            'id_event'      => $event->id,
        ]);

        // Pastikan keduanya tersimpan
        $this->assertEquals(2, Portofolio::where('id_organisasi', $org->id_organisasi)->count());
    }
}
