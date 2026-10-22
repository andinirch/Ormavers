<?php

namespace Tests\Feature;

use App\Models\Keanggotaan;
use App\Models\LogAktivitas;
use App\Models\Organisasi;
use App\Models\Pengguna;
use App\Models\Universitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrasiAnggotaTest extends TestCase
{
    use RefreshDatabase;

    private function fakeStorage(): void
    {
        Storage::fake('local');
    }

    private function jpegHeader(): string
    {
        return "\xFF\xD8\xFF\xE0" . str_repeat("\x00", 100);
    }

    private function buatBemAktif(): Organisasi
    {
        $univ = Universitas::firstOrCreate(['nama_universitas' => 'Universitas Indonesia']);

        $pengguna = Pengguna::factory()->akunOrganisasi()->create(['status_akun' => 'aktif']);

        return Organisasi::create([
            'id_universitas'     => $univ->id_universitas,
            'id_organisasi_induk'=> null,
            'id_akun_organisasi' => $pengguna->id_pengguna,
            'nama_organisasi'    => 'BEM Universitas Indonesia',
            'jenis_organisasi'   => 'bem',
            'nomor_telepon'      => '02112345678',
            'status_organisasi'  => 'aktif',
        ]);
    }

    private function buatHmjAktif(Organisasi $bem): Organisasi
    {
        $pengguna = Pengguna::factory()->akunOrganisasi()->create(['status_akun' => 'aktif']);
        $roleHmj = \Spatie\Permission\Models\Role::findOrCreate('akun_hmj_ukm', 'web');
        $pengguna->syncRoles($roleHmj);

        return Organisasi::create([
            'id_universitas'     => $bem->id_universitas,
            'id_organisasi_induk'=> $bem->id_organisasi,
            'id_akun_organisasi' => $pengguna->id_pengguna,
            'nama_organisasi'    => 'HMJ Ilmu Komputer',
            'jenis_organisasi'   => 'hmj',
            'nomor_telepon'      => '0219876543',
            'status_organisasi'  => 'aktif',
        ]);
    }

    private function validPayload(Organisasi $organisasi, array $overrides = []): array
    {
        return array_merge([
            'id_organisasi' => $organisasi->id_organisasi,
            'nama'          => 'Dewi Sartika',
            'nim'           => '2024099',
            'username'      => 'dewi_anggota',
            'password'      => 'Password123',
            'foto_ktm'      => UploadedFile::fake()->createWithContent('ktm.jpg', $this->jpegHeader()),
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // 1. GET /api/public/bem/{id}/organisasi
    // -------------------------------------------------------------------------

    public function test_get_organisasi_di_bawah_bem_hanya_menampilkan_organisasi_aktif(): void
    {
        $bem = $this->buatBemAktif();
        $hmjAktif = $this->buatHmjAktif($bem);

        // HMJ belum aktif (menunggu)
        Organisasi::create([
            'id_universitas'     => $bem->id_universitas,
            'id_organisasi_induk'=> $bem->id_organisasi,
            'nama_organisasi'    => 'HMJ Belum Aktif',
            'jenis_organisasi'   => 'hmj',
            'nomor_telepon'      => '081234',
            'status_organisasi'  => 'menunggu',
        ]);

        $response = $this->getJson("/api/public/bem/{$bem->id_organisasi}/organisasi");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daftar organisasi berhasil diambil',
            ]);

        $list = $response->json('data.organisasi');
        // Harus ada 2: BEM itu sendiri + HMJ aktif
        $this->assertCount(2, $list);
        $this->assertEquals($bem->id_organisasi, $list[0]['id_organisasi']);
        $this->assertEquals($hmjAktif->id_organisasi, $list[1]['id_organisasi']);
    }

    public function test_get_organisasi_di_bawah_bem_bukan_bem_atau_tidak_aktif_mengembalikan_404(): void
    {
        // 1. ID tidak ada
        $this->getJson('/api/public/bem/99999/organisasi')->assertStatus(404);

        // 2. ID adalah organisasi jenis HMJ, bukan BEM
        $bem = $this->buatBemAktif();
        $hmj = $this->buatHmjAktif($bem);
        $this->getJson("/api/public/bem/{$hmj->id_organisasi}/organisasi")->assertStatus(404);

        // 3. ID adalah BEM tetapi status organisasi belum aktif
        $univ = Universitas::firstOrCreate(['nama_universitas' => 'Univ Inaktif']);
        $bemInaktif = Organisasi::create([
            'id_universitas'    => $univ->id_universitas,
            'nama_organisasi'   => 'BEM Inaktif',
            'jenis_organisasi'  => 'bem',
            'nomor_telepon'     => '08123',
            'status_organisasi' => 'menunggu',
        ]);
        $this->getJson("/api/public/bem/{$bemInaktif->id_organisasi}/organisasi")->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // 2. HAPPY PATH Registrasi Anggota
    // -------------------------------------------------------------------------

    public function test_registrasi_anggota_berhasil_201_dan_keanggotaan_menunggu(): void
    {
        $this->fakeStorage();
        $bem = $this->buatBemAktif();
        $hmj = $this->buatHmjAktif($bem);

        $response = $this->postJson('/api/register/anggota', $this->validPayload($hmj));

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Pendaftaran anggota berhasil dikirim',
                'data'    => [
                    'pendaftaran' => [
                        'id_organisasi'      => $hmj->id_organisasi,
                        'nama_organisasi'    => $hmj->nama_organisasi,
                        'status_keanggotaan' => 'menunggu',
                    ],
                ],
            ]);

        // Verifikasi pengguna
        $pengguna = Pengguna::where('username', 'dewi_anggota')->first();
        $this->assertNotNull($pengguna);
        $this->assertEquals('anggota', $pengguna->peran);
        $this->assertEquals('menunggu', $pengguna->status_akun);
        $this->assertNull($pengguna->nomor_whatsapp);
        $this->assertTrue($pengguna->hasRole('anggota'));

        // Verifikasi keanggotaan
        $keanggotaan = Keanggotaan::where('id_pengguna', $pengguna->id_pengguna)->first();
        $this->assertNotNull($keanggotaan);
        $this->assertEquals($hmj->id_organisasi, $keanggotaan->id_organisasi);
        $this->assertEquals('menunggu', $keanggotaan->status_keanggotaan);

        // Anggota tidak memiliki organisasi atau pendaftaran organisasi
        $this->assertNull($pengguna->organisasi);

        // Verifikasi log aktivitas
        $log = LogAktivitas::where('id_pengguna', $pengguna->id_pengguna)->first();
        $this->assertNotNull($log);
        $this->assertEquals('pendaftaran', $log->jenis_aktivitas);
        $this->assertStringContainsString("Pendaftaran anggota ke {$hmj->nama_organisasi}", $log->keterangan);
    }

    public function test_anggota_baru_login_terbatas_dan_melihat_status_registrasi_serta_resubmit_403(): void
    {
        $this->fakeStorage();
        $bem = $this->buatBemAktif();
        $this->postJson('/api/register/anggota', $this->validPayload($bem))->assertStatus(201);

        // 1. Login terbatas
        $loginRes = $this->postJson('/api/auth/login', [
            'username' => 'dewi_anggota',
            'password' => 'Password123',
        ]);

        $loginRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'akses_terbatas' => true,
                ],
            ]);

        $token = $loginRes->json('data.token');

        // 2. Akses GET /registration/status
        $statusRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/registration/status');

        $statusRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'pendaftar' => [
                        'username'        => 'dewi_anggota',
                        'jenis_pendaftar' => 'anggota',
                    ],
                    'status_pendaftaran' => 'menunggu',
                    'bisa_unggah_ulang'  => false,
                    'organisasi' => [
                        'id_organisasi'   => $bem->id_organisasi,
                        'nama_organisasi' => $bem->nama_organisasi,
                    ],
                ],
            ]);

        // 3. Resubmit SK oleh anggota ditolak 403
        $resubmitRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/registration/resubmit-sk', [
                'sk_organisasi' => UploadedFile::fake()->createWithContent('sk.pdf', '%PDF-1.4' . str_repeat("\x00", 100)),
            ]);

        $resubmitRes->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Anggota tidak dapat mengunggah dokumen',
            ]);
    }

    // -------------------------------------------------------------------------
    // 3. VALIDASI GAGAL
    // -------------------------------------------------------------------------

    public function test_organisasi_tidak_ada_atau_belum_aktif_mengembalikan_422(): void
    {
        $this->fakeStorage();
        $bem = $this->buatBemAktif();

        // 1. ID tidak ada
        $payload1 = $this->validPayload($bem, ['id_organisasi' => 99999]);
        $this->postJson('/api/register/anggota', $payload1)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['id_organisasi']);

        // 2. Organisasi belum aktif
        $orgMenunggu = Organisasi::create([
            'id_universitas'    => $bem->id_universitas,
            'nama_organisasi'   => 'HMJ Menunggu',
            'jenis_organisasi'  => 'hmj',
            'nomor_telepon'     => '08123',
            'status_organisasi' => 'menunggu',
        ]);
        $payload2 = $this->validPayload($bem, ['id_organisasi' => $orgMenunggu->id_organisasi]);
        $this->postJson('/api/register/anggota', $payload2)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['id_organisasi']);
    }

    public function test_username_kembar_mengembalikan_422(): void
    {
        $this->fakeStorage();
        $bem = $this->buatBemAktif();

        // Pendaftaran 1
        $this->postJson('/api/register/anggota', $this->validPayload($bem))->assertStatus(201);

        // Pendaftaran 2 dengan username yang sama
        $payload = $this->validPayload($bem, [
            'nim'  => '999999',
            'nama' => 'Budi Baru',
        ]);
        $this->postJson('/api/register/anggota', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_password_lemah_mengembalikan_422(): void
    {
        $this->fakeStorage();
        $bem = $this->buatBemAktif();

        // Kurang dari 8 karakter
        $payload1 = $this->validPayload($bem, ['password' => 'Pass1']);
        $this->postJson('/api/register/anggota', $payload1)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        // Tanpa angka
        $payload2 = $this->validPayload($bem, ['password' => 'hanyahuruf']);
        $this->postJson('/api/register/anggota', $payload2)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_foto_ktm_png_atau_lebih_dari_1mb_mengembalikan_422(): void
    {
        $this->fakeStorage();
        $bem = $this->buatBemAktif();

        // Format PNG
        $payload1 = $this->validPayload($bem, [
            'foto_ktm' => UploadedFile::fake()->create('ktm.png', 100, 'image/png'),
        ]);
        $this->postJson('/api/register/anggota', $payload1)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['foto_ktm']);

        // Lebih dari 1 MB
        $payload2 = $this->validPayload($bem, [
            'foto_ktm' => UploadedFile::fake()->createWithContent(
                'ktm_besar.jpg',
                $this->jpegHeader() . str_repeat('A', 1025 * 1024)
            ),
        ]);
        $this->postJson('/api/register/anggota', $payload2)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['foto_ktm']);
    }

    public function test_field_kosong_mengembalikan_422(): void
    {
        $this->fakeStorage();

        $this->postJson('/api/register/anggota', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'id_organisasi', 'nama', 'nim', 'username', 'password', 'foto_ktm',
            ]);
    }

    public function test_rollback_transaksi_menghapus_file_ktm(): void
    {
        $this->fakeStorage();
        $bem = $this->buatBemAktif();

        // Buat user bentrok
        Pengguna::factory()->create(['username' => 'tabrakan_user']);

        $payload = $this->validPayload($bem, ['username' => 'tabrakan_user']);

        $this->postJson('/api/register/anggota', $payload)->assertStatus(422);

        $filesKtm = Storage::disk('local')->allFiles('pendaftaran/ktm');
        $this->assertEmpty($filesKtm);
    }
}
