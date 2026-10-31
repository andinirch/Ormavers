<?php

namespace Tests\Feature;

use App\Models\LogAktivitas;
use App\Models\Organisasi;
use App\Models\PendaftaranOrganisasi;
use App\Models\PendaftaranOrganisasiFile;
use App\Models\Pengguna;
use App\Models\Universitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerifikasiHmjUkmTest extends TestCase
{
    use RefreshDatabase;

    private function tokenPengguna(Pengguna $pengguna): string
    {
        return $pengguna->createToken('auth_token', ['*'])->plainTextToken;
    }

    private function buatBem(string $namaUniv = 'Universitas Contoh', string $statusOrg = 'aktif'): array
    {
        $univ = Universitas::firstOrCreate(['nama_universitas' => $namaUniv]);

        $pengguna = Pengguna::factory()->akunOrganisasi()->create([
            'status_akun' => ($statusOrg === 'aktif' ? 'aktif' : 'menunggu'),
        ]);

        $organisasi = Organisasi::create([
            'id_universitas'     => $univ->id_universitas,
            'id_organisasi_induk'=> null,
            'id_akun_organisasi' => $pengguna->id_pengguna,
            'nama_organisasi'    => 'BEM ' . $namaUniv,
            'jenis_organisasi'   => 'bem',
            'nomor_telepon'      => '02112345678',
            'status_organisasi'  => $statusOrg,
        ]);

        return [$pengguna, $organisasi, $this->tokenPengguna($pengguna)];
    }

    private function buatPendaftaranHmj(Organisasi $bemInduk, array $overrides = []): array
    {
        Storage::fake('local');

        $pendaftar = Pengguna::factory()->akunOrganisasi()->menunggu()->create([
            'nama'           => $overrides['nama_penanggung_jawab'] ?? 'Ketua HMJ',
            'username'       => 'ketua_hmj_' . uniqid(),
        ]);

        \App\Models\ProfilAnggota::create([
            'id_pengguna'    => $pendaftar->id_pengguna,
            'nim'            => '123456',
            'nomor_whatsapp' => '081234567890',
            'foto_ktm'       => 'pendaftaran/ktm/ktm.jpg',
            'id_universitas' => $bemInduk->id_universitas,
        ]);
        $roleHmj = \Spatie\Permission\Models\Role::findOrCreate('akun_hmj_ukm', 'web');
        $pendaftar->syncRoles($roleHmj);

        Storage::disk('local')->put('pendaftaran/ktm/ktm.jpg', 'fake-ktm-content');
        Storage::disk('local')->put('pendaftaran/sk_organisasi/sk_org.pdf', 'fake-sk-org-content');
        Storage::disk('local')->put('pendaftaran/sk_kepengurusan/sk_kep.pdf', 'fake-sk-kep-content');

        $organisasi = Organisasi::create([
            'id_universitas'     => $bemInduk->id_universitas,
            'id_organisasi_induk'=> $bemInduk->id_organisasi,
            'id_akun_organisasi' => $pendaftar->id_pengguna,
            'nama_organisasi'    => $overrides['nama_organisasi'] ?? ('HMJ ' . uniqid()),
            'jenis_organisasi'   => $overrides['jenis_organisasi'] ?? 'hmj',
            'nomor_telepon'      => '0812345678',
            'status_organisasi'  => $overrides['status_organisasi'] ?? 'menunggu',
        ]);

        $pendaftaran = PendaftaranOrganisasi::create([
            'id_organisasi'       => $organisasi->id_organisasi,
            'status_pendaftaran'  => $overrides['status_pendaftaran'] ?? 'menunggu',
            'alasan_penolakan'    => $overrides['alasan_penolakan'] ?? null,
            'tanggal_pendaftaran' => $overrides['tanggal_pendaftaran'] ?? now(),
        ]);

        PendaftaranOrganisasiFile::create([
            'id_pendaftaran_organisasi' => $pendaftaran->id_pendaftaran_organisasi,
            'jenis_dokumen'             => 'sk_organisasi',
            'nama_file_asli'            => 'sk_org.pdf',
            'path_file'                 => 'pendaftaran/sk_organisasi/sk_org.pdf',
            'ukuran_kb'                 => 100,
            'diunggah_pada'             => now(),
        ]);

        PendaftaranOrganisasiFile::create([
            'id_pendaftaran_organisasi' => $pendaftaran->id_pendaftaran_organisasi,
            'jenis_dokumen'             => 'sk_kepengurusan',
            'nama_file_asli'            => 'sk_kep.pdf',
            'path_file'                 => 'pendaftaran/sk_kepengurusan/sk_kep.pdf',
            'ukuran_kb'                 => 100,
            'diunggah_pada'             => now(),
        ]);

        return [$pendaftar, $organisasi, $pendaftaran];
    }

    // -------------------------------------------------------------------------
    // HAPPY PATH
    // -------------------------------------------------------------------------

    public function test_bem_induk_melihat_hanya_pendaftaran_miliknya_status_menunggu(): void
    {
        [$userBem1, $bem1, $tokenBem1] = $this->buatBem('Univ 1');
        [$userBem2, $bem2, $tokenBem2] = $this->buatBem('Univ 2');

        [$p1, $org1, $pend1] = $this->buatPendaftaranHmj($bem1, ['tanggal_pendaftaran' => now()->subDay()]);
        [$p2, $org2, $pend2] = $this->buatPendaftaranHmj($bem1, ['tanggal_pendaftaran' => now()]);
        [$p3, $org3, $pend3] = $this->buatPendaftaranHmj($bem2); // milik BEM lain

        $response = $this->withHeader('Authorization', "Bearer {$tokenBem1}")
            ->getJson('/api/org/registrations/hmj-ukm');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daftar pendaftaran HMJ/UKM berhasil diambil',
            ]);

        $list = $response->json('data.pendaftaran');
        $this->assertCount(2, $list);
        $this->assertEquals($pend1->id_pendaftaran_organisasi, $list[0]['id_pendaftaran_organisasi']);
        $this->assertEquals($pend2->id_pendaftaran_organisasi, $list[1]['id_pendaftaran_organisasi']);
    }

    public function test_bem_induk_melihat_detail_dan_mengunduh_dokumen(): void
    {
        [$userBem, $bem, $tokenBem] = $this->buatBem();
        [$pendaftar, $org, $pend] = $this->buatPendaftaranHmj($bem);

        // Detail
        $detailRes = $this->withHeader('Authorization', "Bearer {$tokenBem}")
            ->getJson("/api/org/registrations/hmj-ukm/{$pend->id_pendaftaran_organisasi}");

        $detailRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'pendaftaran' => [
                        'id_pendaftaran_organisasi' => $pend->id_pendaftaran_organisasi,
                        'nama_organisasi'           => $org->nama_organisasi,
                        'penanggung_jawab'          => [
                            'nama'     => $pendaftar->nama,
                            'username' => $pendaftar->username,
                        ],
                    ],
                ],
            ]);

        // Dokumen KTM
        $ktmRes = $this->withHeader('Authorization', "Bearer {$tokenBem}")
            ->get("/api/org/registrations/hmj-ukm/{$pend->id_pendaftaran_organisasi}/dokumen/ktm");
        $ktmRes->assertStatus(200);

        // Dokumen SK Organisasi
        $skOrgRes = $this->withHeader('Authorization', "Bearer {$tokenBem}")
            ->get("/api/org/registrations/hmj-ukm/{$pend->id_pendaftaran_organisasi}/dokumen/sk-organisasi");
        $skOrgRes->assertStatus(200);

        // Dokumen SK Kepengurusan
        $skKepRes = $this->withHeader('Authorization', "Bearer {$tokenBem}")
            ->get("/api/org/registrations/hmj-ukm/{$pend->id_pendaftaran_organisasi}/dokumen/sk-kepengurusan");
        $skKepRes->assertStatus(200);
    }

    public function test_setujui_hmj_mengaktifkan_organisasi_dan_pendaftar_dan_cabut_token(): void
    {
        [$userBem, $bem, $tokenBem] = $this->buatBem();
        [$pendaftar, $org, $pend] = $this->buatPendaftaranHmj($bem);

        // Buat token lama untuk pendaftar
        $tokenPendaftar = $pendaftar->createToken('auth_token', ['status-registrasi'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$tokenBem}")
            ->postJson("/api/org/registrations/hmj-ukm/{$pend->id_pendaftaran_organisasi}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Registrasi HMJ/UKM berhasil disetujui',
            ]);

        // Organisasi aktif
        $org->refresh();
        $this->assertEquals('aktif', $org->status_organisasi);

        // Pendaftaran disetujui
        $pend->refresh();
        $this->assertEquals('disetujui', $pend->status_pendaftaran);
        $this->assertEquals($userBem->id_pengguna, $pend->id_pengguna_verifikator);
        $this->assertNotNull($pend->tanggal_verifikasi);

        // Pengguna aktif & role tetap akun_hmj_ukm
        $pendaftar->refresh();
        $this->assertEquals('aktif', $pendaftar->status_akun);
        $this->assertTrue($pendaftar->hasRole('akun_hmj_ukm'));
        $this->assertFalse($pendaftar->hasRole('akun_bem'));

        // Token lama dicabut
        $this->assertCount(0, $pendaftar->tokens);

        // Log verifikasi tercatat
        $log = LogAktivitas::where('id_pengguna', $userBem->id_pengguna)
            ->where('jenis_aktivitas', 'verifikasi')
            ->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString("Menyetujui pendaftaran HMJ {$org->nama_organisasi}", $log->keterangan);
    }

    public function test_tolak_hmj_menyimpan_alasan_dan_status_pendaftar_ditolak(): void
    {
        [$userBem, $bem, $tokenBem] = $this->buatBem();
        [$pendaftar, $org, $pend] = $this->buatPendaftaranHmj($bem);
        $idPend = $pend->id_pendaftaran_organisasi;
        $idOrg = $org->id_organisasi;
        $idPengguna = $pendaftar->id_pengguna;

        $response = $this->withHeader('Authorization', "Bearer {$tokenBem}")
            ->postJson("/api/org/registrations/hmj-ukm/{$idPend}/reject", [
                'alasan' => 'Berkas SK kepengurusan tidak ada cap stempel resmi.',
            ]);

     $response->assertStatus(200)
    ->assertJson([
        'success' => true,
        'message' => 'Pendaftaran HMJ berhasil ditolak',
    ]);

        // 1. Data pendaftaran terhapus secara permanen (hard delete)
        $this->assertDatabaseMissing('pendaftaran_organisasi', [
            'id_pendaftaran_organisasi' => $idPend,
        ]);

        // 2. Data organisasi terhapus
        $this->assertDatabaseMissing('organisasi', [
            'id_organisasi' => $idOrg,
        ]);

        // 3. Akun dan profil pengguna terhapus
        $this->assertDatabaseMissing('pengguna', [
            'id_pengguna' => $idPengguna,
        ]);
        $this->assertDatabaseMissing('profil_anggota', [
            'id_pengguna' => $idPengguna,
        ]);

        // 4. Log verifikasi tercatat
        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna'     => $userBem->id_pengguna,
            'jenis_aktivitas' => 'verifikasi',
        ]);
    }

    // -------------------------------------------------------------------------
    // OTORISASI & VALIDASI GAGAL
    // -------------------------------------------------------------------------

    public function test_akses_tanpa_token_ditolak_401(): void
    {
        $this->getJson('/api/org/registrations/hmj-ukm')->assertStatus(401);
    }

    public function test_peran_selain_akun_bem_ditolak_403(): void
    {
        // 1. Super Admin
        $superAdmin = Pengguna::factory()->superAdmin()->create();
        $this->withHeader('Authorization', "Bearer " . $this->tokenPengguna($superAdmin))
            ->getJson('/api/org/registrations/hmj-ukm')
            ->assertStatus(403);

        // 2. Administrator
        $admin = Pengguna::factory()->administrator()->create();
        $this->withHeader('Authorization', "Bearer " . $this->tokenPengguna($admin))
            ->getJson('/api/org/registrations/hmj-ukm')
            ->assertStatus(403);

        // 3. Anggota
        $anggota = Pengguna::factory()->create(['peran' => 'anggota']);
        $this->withHeader('Authorization', "Bearer " . $this->tokenPengguna($anggota))
            ->getJson('/api/org/registrations/hmj-ukm')
            ->assertStatus(403);

        // 4. Akun HMJ/UKM
        $hmj = Pengguna::factory()->akunOrganisasi()->create();
        $roleHmj = \Spatie\Permission\Models\Role::findOrCreate('akun_hmj_ukm', 'web');
        $hmj->syncRoles($roleHmj);
        $this->withHeader('Authorization', "Bearer " . $this->tokenPengguna($hmj))
            ->getJson('/api/org/registrations/hmj-ukm')
            ->assertStatus(403);
    }

    public function test_akun_bem_yang_organisasinya_belum_aktif_ditolak_403(): void
    {
        [$userBemMenunggu, $bemMenunggu, $tokenMenunggu] = $this->buatBem('Univ Inaktif', 'menunggu');

        $response = $this->withHeader('Authorization', "Bearer {$tokenMenunggu}")
            ->getJson('/api/org/registrations/hmj-ukm');

        // Middleware akun.aktif menolak karena status_akun menunggu
        $response->assertStatus(403);
    }

    public function test_bem_lain_mengakses_pendaftaran_organisasi_bem_lain_ditolak_403(): void
    {
        [$userBem1, $bem1, $tokenBem1] = $this->buatBem('Univ 1');
        [$userBem2, $bem2, $tokenBem2] = $this->buatBem('Univ 2');

        [$p1, $org1, $pend1] = $this->buatPendaftaranHmj($bem1);

        // BEM 2 mencoba melihat detail pendaftaran milik BEM 1
        $this->withHeader('Authorization', "Bearer {$tokenBem2}")
            ->getJson("/api/org/registrations/hmj-ukm/{$pend1->id_pendaftaran_organisasi}")
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Registrasi ini bukan dari universitas Anda',
            ]);

        // BEM 2 mencoba menyetujui
        $this->withHeader('Authorization', "Bearer {$tokenBem2}")
            ->postJson("/api/org/registrations/hmj-ukm/{$pend1->id_pendaftaran_organisasi}/approve")
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Registrasi ini bukan dari universitas Anda',
            ]);

        // BEM 2 mencoba menolak
        $this->withHeader('Authorization', "Bearer {$tokenBem2}")
            ->postJson("/api/org/registrations/hmj-ukm/{$pend1->id_pendaftaran_organisasi}/reject", ['alasan' => 'Tolak'])
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Registrasi ini bukan dari universitas Anda',
            ]);
    }

    public function test_id_tidak_ada_atau_jenis_bem_mengembalikan_404(): void
    {
        [$userBem, $bem, $tokenBem] = $this->buatBem();

        // 1. ID tidak ada
        $this->withHeader('Authorization', "Bearer {$tokenBem}")
            ->getJson("/api/org/registrations/hmj-ukm/99999")
            ->assertStatus(404);

        // 2. ID merupakan pendaftaran BEM (bukan hmj atau ukm)
        $pendBem = PendaftaranOrganisasi::create([
            'id_organisasi'       => $bem->id_organisasi,
            'status_pendaftaran'  => 'menunggu',
            'tanggal_pendaftaran' => now(),
        ]);

        $this->withHeader('Authorization', "Bearer {$tokenBem}")
            ->getJson("/api/org/registrations/hmj-ukm/{$pendBem->id_pendaftaran_organisasi}")
            ->assertStatus(404);
    }

    public function test_keputusan_kedua_mengembalikan_409(): void
    {
        [$userBem, $bem, $tokenBem] = $this->buatBem();
        [$pendaftar, $org, $pend] = $this->buatPendaftaranHmj($bem);

        // Setujui pertama kali -> 200
        $this->withHeader('Authorization', "Bearer {$tokenBem}")
            ->postJson("/api/org/registrations/hmj-ukm/{$pend->id_pendaftaran_organisasi}/approve")
            ->assertStatus(200);

        // Setujui kedua kali -> 409
        $this->withHeader('Authorization', "Bearer {$tokenBem}")
            ->postJson("/api/org/registrations/hmj-ukm/{$pend->id_pendaftaran_organisasi}/approve")
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Pendaftaran sudah diputuskan',
            ]);

        // Coba tolak yang sudah disetujui -> 409
        $this->withHeader('Authorization', "Bearer {$tokenBem}")
            ->postJson("/api/org/registrations/hmj-ukm/{$pend->id_pendaftaran_organisasi}/reject", ['alasan' => 'Tolak'])
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Pendaftaran sudah diputuskan',
            ]);
    }

    public function test_alasan_tolak_kosong_atau_lebih_dari_1000_karakter_mengembalikan_422(): void
    {
        [$userBem, $bem, $tokenBem] = $this->buatBem();
        [$pendaftar, $org, $pend] = $this->buatPendaftaranHmj($bem);

        // Kosong
        $this->withHeader('Authorization', "Bearer {$tokenBem}")
            ->postJson("/api/org/registrations/hmj-ukm/{$pend->id_pendaftaran_organisasi}/reject", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['alasan']);

        // Lebih dari 1000 karakter
        $this->withHeader('Authorization', "Bearer {$tokenBem}")
            ->postJson("/api/org/registrations/hmj-ukm/{$pend->id_pendaftaran_organisasi}/reject", [
                'alasan' => str_repeat('a', 1001),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['alasan']);
    }
}
