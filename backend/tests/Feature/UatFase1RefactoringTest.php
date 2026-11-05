<?php

namespace Tests\Feature;

use App\Models\BidangOrganisasi;
use App\Models\Keanggotaan;
use App\Models\Organisasi;
use App\Models\PendaftaranOrganisasi;
use App\Models\PendaftaranOrganisasiFile;
use App\Models\Pengguna;
use App\Models\ProfilAnggota;
use App\Models\Universitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UatFase1RefactoringTest extends TestCase
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

        Storage::fake('local');
        Storage::fake('public');
    }

    private function buatUserDenganToken(string $peran = 'administrator', string $statusAkun = 'aktif'): array
    {
        $user = Pengguna::factory()->create([
            'peran'       => $peran,
            'status_akun' => $statusAkun,
        ]);
        $user->syncRoles($peran);

        $token = $user->createToken('auth_token', ['*'])->plainTextToken;

        return [$user, ['Authorization' => "Bearer {$token}"]];
    }

    /**
     * TEST 1: Penolakan BEM dengan checklist dokumen_ditolak dan pengajuan ulang (resubmit)
     */
    public function test_penolakan_bem_dengan_checklist_dan_resubmit_dokumen(): void
    {
        [, $adminHeaders] = $this->buatUserDenganToken('administrator');

        $univ = Universitas::create(['nama_universitas' => 'Universitas UAT']);
        $pendaftarUser = Pengguna::factory()->akunOrganisasi()->create(['status_akun' => 'menunggu']);
        $pendaftarUser->syncRoles('akun_bem');

        $pathKtm = UploadedFile::fake()->image('ktm_lama.jpg')->store('pendaftaran/ktm', 'local');
        ProfilAnggota::create([
            'id_pengguna'    => $pendaftarUser->id_pengguna,
            'id_universitas' => $univ->id_universitas,
            'nim'            => '12345678',
            'nomor_whatsapp' => '081234567890',
            'foto_ktm'       => $pathKtm,
        ]);

        $organisasi = Organisasi::create([
            'id_universitas'     => $univ->id_universitas,
            'id_akun_organisasi' => $pendaftarUser->id_pengguna,
            'nama_organisasi'    => 'BEM UAT',
            'jenis_organisasi'   => 'bem',
            'status_organisasi'  => 'menunggu',
        ]);

        $pendaftaran = PendaftaranOrganisasi::create([
            'id_organisasi'       => $organisasi->id_organisasi,
            'status_pendaftaran'  => 'menunggu',
            'tanggal_pendaftaran' => now(),
        ]);

        $pathSkOrg = UploadedFile::fake()->create('sk_org_lama.pdf', 100, 'application/pdf')->store('pendaftaran/sk_organisasi', 'local');
        PendaftaranOrganisasiFile::create([
            'id_pendaftaran_organisasi' => $pendaftaran->id_pendaftaran_organisasi,
            'jenis_dokumen'             => 'sk_organisasi',
            'nama_file_asli'            => 'sk_org_lama.pdf',
            'path_file'                 => $pathSkOrg,
            'ukuran_kb'                 => 100,
            'diunggah_pada'             => now(),
        ]);

        // 1. Verifikator Administrator menolak dengan alasan dan checklist dokumen_ditolak
        $rejectResponse = $this->postJson("/api/admin/registrations/bem/{$pendaftaran->id_pendaftaran_organisasi}/reject", [
            'alasan'          => 'Foto KTM buram dan SK Organisasi salah periode',
            'dokumen_ditolak' => ['foto_ktm', 'sk_organisasi'],
        ], $adminHeaders);

        $rejectResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Pendaftaran BEM berhasil ditolak',
            ]);

        // Pastikan kolom dokumen_ditolak tersimpan
        $pendaftaran->refresh();
        $this->assertEquals('ditolak', $pendaftaran->status_pendaftaran);
        $this->assertContains('foto_ktm', $pendaftaran->dokumen_ditolak);
        $this->assertContains('sk_organisasi', $pendaftaran->dokumen_ditolak);

        // 2. Pendaftar login terbatas dan mengecek status
        auth()->forgetGuards();
        $tokenPendaftar = $pendaftarUser->createToken('auth_token', ['*'])->plainTextToken;
        $pendaftarHeaders = ['Authorization' => "Bearer {$tokenPendaftar}"];

        $statusResponse = $this->getJson('/api/registration/status', $pendaftarHeaders);
        $statusResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'status_pendaftaran' => 'ditolak',
                    'alasan_penolakan'   => 'Foto KTM buram dan SK Organisasi salah periode',
                    'dokumen_ditolak'    => ['foto_ktm', 'sk_organisasi'],
                ],
            ]);

        // Periksa flag ditolak pada array dokumen
        $dokumenList = $statusResponse->json('data.dokumen');
        $ktmDoc = collect($dokumenList)->firstWhere('jenis_dokumen', 'foto_ktm');
        $this->assertNotNull($ktmDoc);
        $this->assertTrue($ktmDoc['ditolak']);

        $skDoc = collect($dokumenList)->firstWhere('jenis_dokumen', 'sk_organisasi');
        $this->assertNotNull($skDoc);
        $this->assertTrue($skDoc['ditolak']);

        // 3. Pendaftar melakukan resubmit foto KTM baru dan SK Organisasi baru
        $resubmitResponse = $this->postJson('/api/registration/resubmit-sk', [
            'foto_ktm'      => UploadedFile::fake()->image('ktm_revisi.jpg'),
            'sk_organisasi' => UploadedFile::fake()->create('sk_org_revisi.pdf', 150, 'application/pdf'),
        ], $pendaftarHeaders);

        $resubmitResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Dokumen SK Berhasil Diperbarui',
            ]);

        // Verifikasi pendaftaran kembali 'menunggu', alasan & dokumen_ditolak bersih
        $pendaftaran->refresh();
        $this->assertEquals('menunggu', $pendaftaran->status_pendaftaran);
        $this->assertNull($pendaftaran->alasan_penolakan);
        $this->assertNull($pendaftaran->dokumen_ditolak);

        // Verifikasi file fisik lama telah dihapus
        $this->assertFalse(Storage::disk('local')->exists($pathKtm));
        $this->assertFalse(Storage::disk('local')->exists($pathSkOrg));

        // Verifikasi file baru tersimpan
        $profilAnggota = $pendaftarUser->profilAnggota()->first();
        $this->assertTrue(Storage::disk('local')->exists($profilAnggota->foto_ktm));
    }

    /**
     * TEST 2: Penolakan Permintaan Anggota dengan Checklist Dokumen
     */
    public function test_penolakan_anggota_dengan_checklist_dan_ajukan_ulang(): void
    {
        $univ = Universitas::create(['nama_universitas' => 'Universitas Anggota']);
        $bemUser = Pengguna::factory()->akunOrganisasi()->create(['status_akun' => 'aktif']);
        $bemUser->syncRoles('akun_bem');
        $bemToken = $bemUser->createToken('auth_token', ['*'])->plainTextToken;
        $bemHeaders = ['Authorization' => "Bearer {$bemToken}"];

        $organisasi = Organisasi::create([
            'id_universitas'     => $univ->id_universitas,
            'id_akun_organisasi' => $bemUser->id_pengguna,
            'nama_organisasi'    => 'BEM Anggota',
            'jenis_organisasi'   => 'bem',
            'status_organisasi'  => 'aktif',
        ]);

        $anggotaUser = Pengguna::factory()->create([
            'peran'       => 'anggota',
            'status_akun' => 'menunggu',
        ]);
        $anggotaUser->syncRoles('anggota');

        $pathKtm = UploadedFile::fake()->image('ktm_mhs.jpg')->store('pendaftaran/ktm', 'local');
        ProfilAnggota::create([
            'id_pengguna'    => $anggotaUser->id_pengguna,
            'id_universitas' => $univ->id_universitas,
            'nim'            => '87654321',
            'nomor_whatsapp' => '081298765432',
            'foto_ktm'       => $pathKtm,
        ]);

        $keanggotaan = Keanggotaan::create([
            'id_pengguna'         => $anggotaUser->id_pengguna,
            'id_organisasi'       => $organisasi->id_organisasi,
            'status_keanggotaan'  => 'menunggu',
            'tanggal_pendaftaran' => now(),
        ]);

        // 1. Verifikator BEM menolak keanggotaan dengan checklist ['foto_ktm']
        $rejectResponse = $this->postJson("/api/org/member-requests/{$keanggotaan->id_keanggotaan}/reject", [
            'alasan'          => 'Foto KTM terpotong dan tidak terbaca',
            'dokumen_ditolak' => ['foto_ktm'],
        ], $bemHeaders);

        $rejectResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Keanggotaan berhasil ditolak',
            ]);

        $keanggotaan->refresh();
        $this->assertEquals('ditolak', $keanggotaan->status_keanggotaan);
        $this->assertEquals(['foto_ktm'], $keanggotaan->dokumen_ditolak);

        // 2. Anggota mengecek status registrasi
        auth()->forgetGuards();
        $tokenAnggota = $anggotaUser->createToken('auth_token', ['*'])->plainTextToken;
        $anggotaHeaders = ['Authorization' => "Bearer {$tokenAnggota}"];

        $statusResponse = $this->getJson('/api/registration/status', $anggotaHeaders);
        $statusResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'status_pendaftaran' => 'ditolak',
                    'alasan_penolakan'   => 'Foto KTM terpotong dan tidak terbaca',
                    'dokumen_ditolak'    => ['foto_ktm'],
                ],
            ]);

        // 3. Anggota mengajukan ulang foto KTM
        $ajukanResponse = $this->postJson('/api/registration/anggota/ajukan-ulang', [
            'foto_ktm' => UploadedFile::fake()->image('ktm_mhs_baru.jpg'),
        ], $anggotaHeaders);

        $ajukanResponse->assertStatus(200);

        // Keanggotaan kembali 'menunggu', dokumen_ditolak di-reset
        $keanggotaan->refresh();
        $this->assertEquals('menunggu', $keanggotaan->status_keanggotaan);
        $this->assertNull($keanggotaan->alasan_penolakan);
        $this->assertNull($keanggotaan->dokumen_ditolak);
        $this->assertFalse(Storage::disk('local')->exists($pathKtm));
    }

    /**
     * TEST 3: Input Kustom Bidang Organisasi ("Lainnya") Tanpa Mengotori Tabel Master
     */
    public function test_input_kustom_bidang_organisasi_lainnya_tidak_mengotori_master(): void
    {
        $univ = Universitas::create(['nama_universitas' => 'Universitas Kustom']);
        $masterCountAwal = BidangOrganisasi::count();

        $orgUser = Pengguna::factory()->akunOrganisasi()->create(['status_akun' => 'aktif']);
        $orgUser->syncRoles('akun_bem');
        $orgToken = $orgUser->createToken('auth_token', ['*'])->plainTextToken;
        $orgHeaders = ['Authorization' => "Bearer {$orgToken}"];

        $organisasi = Organisasi::create([
            'id_universitas'     => $univ->id_universitas,
            'id_akun_organisasi' => $orgUser->id_pengguna,
            'nama_organisasi'    => 'UKM Robotika & Kecerdasan Buatan',
            'jenis_organisasi'   => 'ukm',
            'status_organisasi'  => 'aktif',
        ]);

        // 1. Organisasi memilih opsi "Lainnya" dan mengetikkan teks kustom
        $updateResponse = $this->postJson('/api/organisasi/identitas', [
            'id_bidang'           => 'lainnya',
            'bidang_organisasi'   => 'Robotika dan Kecerdasan Buatan',
            'is_custom_bidang'    => true,
            'alamat'              => 'Gedung Lab Robotika Lt. 2',
            'tanggal_mulai_sk'    => '2026-01-01',
            'tanggal_berakhir_sk' => '2026-12-31',
        ], $orgHeaders);

        $updateResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'nama_bidang'      => 'Robotika dan Kecerdasan Buatan',
                    'bidang_kustom'    => 'Robotika dan Kecerdasan Buatan',
                    'is_custom_bidang' => true,
                    'id_bidang'        => null,
                ],
            ]);

        // 2. PASTIKAN tabel master bidang_organisasi TETAP BERSIH (jumlah tidak bertambah)
        $this->assertEquals($masterCountAwal, BidangOrganisasi::count());
        $this->assertDatabaseMissing('bidang_organisasi', [
            'nama_bidang' => 'Robotika dan Kecerdasan Buatan',
        ]);

        // 3. Kolom bidang_kustom tersimpan di tabel organisasi
        $this->assertDatabaseHas('organisasi', [
            'id_organisasi' => $organisasi->id_organisasi,
            'id_bidang'     => null,
            'bidang_kustom' => 'Robotika dan Kecerdasan Buatan',
        ]);

        // 4. GET /api/organisasi/identitas mengembalikan data bidang kustom
        $getResponse = $this->getJson('/api/organisasi/identitas', $orgHeaders);
        $getResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'nama_bidang'      => 'Robotika dan Kecerdasan Buatan',
                    'is_custom_bidang' => true,
                ],
            ]);

        // 5. Profil publik / pencarian juga menampilkan nama bidang kustom
        $showResponse = $this->getJson("/api/organisasi/{$organisasi->id_organisasi}", $orgHeaders);
        $showResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'bidang' => [
                        'id_bidang'   => null,
                        'nama_bidang' => 'Robotika dan Kecerdasan Buatan',
                    ],
                ],
            ]);
    }

    /**
     * TEST 4: Paginasi Direktori Organisasi dengan Format Standar Laravel (data, links, meta)
     */
    public function test_direktori_universitas_mengembalikan_struktur_paginasi_lengkap(): void
    {
        [, $headers] = $this->buatUserDenganToken('anggota');

        for ($i = 1; $i <= 10; $i++) {
            Universitas::create(['nama_universitas' => "Universitas Ke-{$i}"]);
        }

        $response = $this->getJson('/api/direktori/universitas?per_page=4&page=1', $headers);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daftar universitas berhasil diambil',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id_universitas', 'nama_universitas', 'logo', 'is_default_logo'],
                ],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta'  => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total'],
            ]);

        $this->assertEquals(4, $response->json('meta.per_page'));
        $this->assertEquals(10, $response->json('meta.total'));
        $this->assertEquals(3, $response->json('meta.last_page'));
        $this->assertCount(4, $response->json('data'));
    }
}
