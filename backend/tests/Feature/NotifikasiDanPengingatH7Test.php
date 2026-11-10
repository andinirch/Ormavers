<?php

namespace Tests\Feature;

use App\Models\Ajakan;
use App\Models\Event;
use App\Models\KategoriEvent;
use App\Models\Notifikasi;
use App\Models\Organisasi;
use App\Models\Pengguna;
use App\Models\Universitas;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotifikasiDanPengingatH7Test extends TestCase
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

        $token = $user->createToken('auth_token', ['*'])->plainTextToken;

        return [$user, $org, $token];
    }

    // ==========================================
    // 1. ORMV-1-34: LIHAT NOTIFIKASI
    // ==========================================

    public function test_organisasi_melihat_daftar_notifikasi_terbaru(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');

        // Buat 2 notifikasi untuk organisasi ini dengan waktu berbeda
        $notifLama = Notifikasi::create([
            'id_organisasi' => $org->id_organisasi,
            'jenis'         => 'ajakan',
            'pesan'         => 'Notifikasi lama',
            'sudah_baca'    => false,
            'dibuat_pada'   => now()->subHour(),
        ]);

        $notifBaru = Notifikasi::create([
            'id_organisasi' => $org->id_organisasi,
            'jenis'         => 'pengingat_event',
            'pesan'         => 'Notifikasi baru',
            'sudah_baca'    => false,
            'dibuat_pada'   => now(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/organisasi/notifikasi');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daftar notifikasi berhasil diambil',
                'data'    => [
                    'unread_count' => 2,
                ],
            ]);

        $daftar = $response->json('data.notifikasi');
        $this->assertCount(2, $daftar);
        // Terurut dari yang paling baru
        $this->assertEquals($notifBaru->id, $daftar[0]['id']);
        $this->assertEquals($notifLama->id, $daftar[1]['id']);
        $this->assertEquals('pengingat_event', $daftar[0]['jenis']);
        $this->assertFalse($daftar[0]['sudah_baca']);
        $this->assertNotNull($daftar[0]['target_url']);
    }

    public function test_organisasi_melihat_notifikasi_kosong(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/organisasi/notifikasi');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Belum ada notifikasi',
                'data'    => [
                    'pesan'        => 'Belum ada notifikasi',
                    'unread_count' => 0,
                    'notifikasi'   => [],
                ],
            ]);
    }

    public function test_get_unread_count_badge_lonceng(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('hmj');

        Notifikasi::create([
            'id_organisasi' => $org->id_organisasi,
            'jenis'         => 'ajakan',
            'pesan'         => 'Notif 1',
            'sudah_baca'    => false,
        ]);

        Notifikasi::create([
            'id_organisasi' => $org->id_organisasi,
            'jenis'         => 'undangan',
            'pesan'         => 'Notif 2',
            'sudah_baca'    => true, // sudah dibaca
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/organisasi/notifikasi/unread-count');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'unread_count' => 1,
                ],
            ]);
    }

    // ==========================================
    // 2. ORMV-1-34: PENANDAAN DIBACA & REDIRECTION
    // ==========================================

    public function test_tandai_notifikasi_sebagai_sudah_dibaca(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');

        $kategori = KategoriEvent::first();
        $event = Event::create([
            'id_organisasi'       => $org->id_organisasi,
            'id_kategori'         => $kategori->id,
            'judul'               => 'Seminar Nasional AI',
            'deskripsi'           => 'Deskripsi Seminar',
            'tanggal_event'       => now()->addDays(7)->toDateString(),
            'tautan_pendaftaran'  => 'https://example.com/daftar',
            'periode_mulai'       => now()->subDays(1),
            'periode_selesai'     => now()->addDays(5),
            'jangkauan_publikasi' => 'multi_universitas',
        ]);

        $notif = Notifikasi::create([
            'id_organisasi' => $org->id_organisasi,
            'jenis'         => 'pengingat_event',
            'id_event'      => $event->id,
            'pesan'         => "Pengingat: Event '{$event->judul}' H-7.",
            'sudah_baca'    => false,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/organisasi/notifikasi/{$notif->id}/baca");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notifikasi berhasil ditandai sudah dibaca',
                'data'    => [
                    'unread_count' => 0,
                    'notifikasi'   => [
                        'id'          => $notif->id,
                        'sudah_baca'  => true,
                        'target_url'  => "/events/{$event->id}",
                        'target_type' => 'detail_event',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('notifikasi', [
            'id'         => $notif->id,
            'sudah_baca' => 1,
        ]);
        $this->assertNotNull($notif->fresh()->dibaca_pada);
    }

    public function test_tandai_semua_notifikasi_sudah_dibaca(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');

        Notifikasi::create([
            'id_organisasi' => $org->id_organisasi,
            'jenis'         => 'ajakan',
            'pesan'         => 'Notif 1',
            'sudah_baca'    => false,
        ]);

        Notifikasi::create([
            'id_organisasi' => $org->id_organisasi,
            'jenis'         => 'jawaban',
            'pesan'         => 'Notif 2',
            'sudah_baca'    => false,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/organisasi/notifikasi/baca-semua');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Semua notifikasi berhasil ditandai sudah dibaca',
                'data'    => [
                    'unread_count' => 0,
                ],
            ]);

        $this->assertEquals(0, Notifikasi::where('id_organisasi', $org->id_organisasi)->where('sudah_baca', false)->count());
    }

    public function test_keamanan_organisasi_tidak_bisa_baca_notifikasi_organisasi_lain(): void
    {
        [$userA, $orgA, $tokenA] = $this->buatAkunOrganisasi('bem');
        [$userB, $orgB, $tokenB] = $this->buatAkunOrganisasi('hmj');

        $notifOrgB = Notifikasi::create([
            'id_organisasi' => $orgB->id_organisasi,
            'jenis'         => 'ajakan',
            'pesan'         => 'Notif milik Org B',
            'sudah_baca'    => false,
        ]);

        // User A mencoba menandai notif milik Org B
        $response = $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->postJson("/api/organisasi/notifikasi/{$notifOrgB->id}/baca");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses terhadap notifikasi ini',
            ]);

        // Pastikan status notifikasi milik Org B tidak berubah
        $this->assertDatabaseHas('notifikasi', [
            'id'         => $notifOrgB->id,
            'sudah_baca' => 0,
        ]);
    }

    // ==========================================
    // 3. ORMV-1-35: ARTISAN COMMAND PENGINGAT EVENT H-7
    // ==========================================

    public function test_artisan_command_kirim_pengingat_event_h7_ke_pembuat_dan_kolaborator_diterima(): void
    {
        [$pembuatUser, $pembuatOrg] = $this->buatAkunOrganisasi('bem');
        [$kolabUser1, $kolabOrg1] = $this->buatAkunOrganisasi('hmj');
        [$kolabUser2, $kolabOrg2] = $this->buatAkunOrganisasi('ukm');
        [$kolabUser3, $kolabOrg3] = $this->buatAkunOrganisasi('hmj');

        $kategori = KategoriEvent::first();

        // Event yang berlangsung tepat 7 hari lagi dari sekarang
        $tanggalH7 = Carbon::today()->addDays(7)->toDateString();

        $eventH7 = Event::create([
            'id_organisasi'       => $pembuatOrg->id_organisasi,
            'id_kategori'         => $kategori->id,
            'judul'               => 'Grand Workshop Ormavers',
            'deskripsi'           => 'Workshop seru',
            'tanggal_event'       => $tanggalH7,
            'tautan_pendaftaran'  => 'https://example.com/daftar',
            'periode_mulai'       => now()->subDays(1),
            'periode_selesai'     => now()->addDays(5),
            'jangkauan_publikasi' => 'multi_universitas',
        ]);

        // Kolaborator 1: status diterima (HARUS dapat notifikasi)
        Ajakan::create([
            'id_event'               => $eventH7->id,
            'id_organisasi_pengirim' => $pembuatOrg->id_organisasi,
            'id_organisasi_tujuan'   => $kolabOrg1->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'diterima',
            'djawab_pada'            => now(),
        ]);

        // Kolaborator 2: status diterima (HARUS dapat notifikasi)
        Ajakan::create([
            'id_event'               => $eventH7->id,
            'id_organisasi_pengirim' => $pembuatOrg->id_organisasi,
            'id_organisasi_tujuan'   => $kolabOrg2->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'diterima',
            'djawab_pada'            => now(),
        ]);

        // Kolaborator 3: status 'menunggu' (TIDAK BOLEH dapat notifikasi)
        Ajakan::create([
            'id_event'               => $eventH7->id,
            'id_organisasi_pengirim' => $pembuatOrg->id_organisasi,
            'id_organisasi_tujuan'   => $kolabOrg3->id_organisasi,
            'jenis'                  => 'kolaborator',
            'status'                 => 'menunggu',
        ]);

        // Undangan: status 'diterima' (TIDAK BOLEH dapat notifikasi pengingat H-7 kolaborator)
        [$undanganUser, $undanganOrg] = $this->buatAkunOrganisasi('ukm');
        Ajakan::create([
            'id_event'               => $eventH7->id,
            'id_organisasi_pengirim' => $pembuatOrg->id_organisasi,
            'id_organisasi_tujuan'   => $undanganOrg->id_organisasi,
            'jenis'                  => 'undangan',
            'status'                 => 'diterima',
        ]);

        // Jalankan Artisan Command
        $this->artisan('event:kirim-pengingat-h7')
            ->expectsOutputToContain('Selesai memproses 1 event. Sebanyak 3 notifikasi pengingat berhasil dikirim.')
            ->assertExitCode(0);

        // 1. Pembuat event menerima notifikasi
        $this->assertDatabaseHas('notifikasi', [
            'id_organisasi' => $pembuatOrg->id_organisasi,
            'id_event'      => $eventH7->id,
            'jenis'         => 'pengingat_event',
            'sudah_baca'    => 0,
        ]);

        // 2. Kolaborator 1 menerima notifikasi
        $this->assertDatabaseHas('notifikasi', [
            'id_organisasi' => $kolabOrg1->id_organisasi,
            'id_event'      => $eventH7->id,
            'jenis'         => 'pengingat_event',
            'sudah_baca'    => 0,
        ]);

        // 3. Kolaborator 2 menerima notifikasi
        $this->assertDatabaseHas('notifikasi', [
            'id_organisasi' => $kolabOrg2->id_organisasi,
            'id_event'      => $eventH7->id,
            'jenis'         => 'pengingat_event',
            'sudah_baca'    => 0,
        ]);

        // 4. Kolaborator status 'menunggu' TIDAK menerima notifikasi
        $this->assertDatabaseMissing('notifikasi', [
            'id_organisasi' => $kolabOrg3->id_organisasi,
            'id_event'      => $eventH7->id,
            'jenis'         => 'pengingat_event',
        ]);

        // 5. Undangan TIDAK menerima notifikasi
        $this->assertDatabaseMissing('notifikasi', [
            'id_organisasi' => $undanganOrg->id_organisasi,
            'id_event'      => $eventH7->id,
            'jenis'         => 'pengingat_event',
        ]);

        // Uji Idempotency: Jalankan command untuk kedua kalinya di hari yang sama
        $this->artisan('event:kirim-pengingat-h7')
            ->expectsOutputToContain('Selesai memproses 1 event. Sebanyak 0 notifikasi pengingat berhasil dikirim.')
            ->assertExitCode(0);

        // Jumlah total notifikasi pengingat di database tetap 3 (tidak bertambah jadi 6)
        $this->assertEquals(3, Notifikasi::where('id_event', $eventH7->id)->where('jenis', 'pengingat_event')->count());
    }

    public function test_artisan_command_tidak_mengirim_jika_bukan_h7(): void
    {
        [$pembuatUser, $pembuatOrg] = $this->buatAkunOrganisasi('bem');
        $kategori = KategoriEvent::first();

        // Event H-6 (bukan H-7)
        Event::create([
            'id_organisasi'       => $pembuatOrg->id_organisasi,
            'id_kategori'         => $kategori->id,
            'judul'               => 'Event H-6',
            'deskripsi'           => 'Event bukan H-7',
            'tanggal_event'       => Carbon::today()->addDays(6)->toDateString(),
            'tautan_pendaftaran'  => 'https://example.com/daftar',
            'periode_mulai'       => now()->subDays(1),
            'periode_selesai'     => now()->addDays(5),
            'jangkauan_publikasi' => 'multi_universitas',
        ]);

        // Event H-8 (bukan H-7)
        Event::create([
            'id_organisasi'       => $pembuatOrg->id_organisasi,
            'id_kategori'         => $kategori->id,
            'judul'               => 'Event H-8',
            'deskripsi'           => 'Event bukan H-7',
            'tanggal_event'       => Carbon::today()->addDays(8)->toDateString(),
            'tautan_pendaftaran'  => 'https://example.com/daftar',
            'periode_mulai'       => now()->subDays(1),
            'periode_selesai'     => now()->addDays(5),
            'jangkauan_publikasi' => 'multi_universitas',
        ]);

        $this->artisan('event:kirim-pengingat-h7')
            ->expectsOutputToContain('Tidak ada event yang berlangsung pada tanggal')
            ->assertExitCode(0);

        $this->assertEquals(0, Notifikasi::where('jenis', 'pengingat_event')->count());
    }
}
