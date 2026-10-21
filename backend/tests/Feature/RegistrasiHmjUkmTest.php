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

class RegistrasiHmjUkmTest extends TestCase
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

    private function pdfHeader(): string
    {
        return '%PDF-1.4' . str_repeat("\x00", 100);
    }

    private function buatBemAktif(string $namaUniv = 'Universitas Indonesia', string $namaBem = 'BEM UI'): Organisasi
    {
        $univ = Universitas::firstOrCreate(
            ['nama_universitas' => $namaUniv],
            ['logo' => null]
        );

        $pengguna = Pengguna::factory()->akunOrganisasi()->create([
            'status_akun' => 'aktif',
        ]);

        return Organisasi::create([
            'id_universitas'     => $univ->id_universitas,
            'id_organisasi_induk'=> null,
            'id_akun_organisasi' => $pengguna->id_pengguna,
            'nama_organisasi'    => $namaBem,
            'jenis_organisasi'   => 'bem',
            'nomor_telepon'      => '02112345678',
            'status_organisasi'  => 'aktif',
        ]);
    }

    private function validPayload(Organisasi $bemInduk, array $overrides = []): array
    {
        return array_merge([
            'id_organisasi_induk'      => $bemInduk->id_organisasi,
            'jenis_organisasi'         => 'hmj',
            'nama_organisasi'          => 'HMJ Teknik Informatika',
            'nomor_telepon_organisasi' => '02198765432',
            'nama'                     => 'Ahmad Dani',
            'nim'                      => '12345678',
            'nomor_whatsapp'           => '081234567890',
            'username'                 => 'ahmad_hmj',
            'password'                 => 'Password123',
            'foto_ktm'                 => UploadedFile::fake()->createWithContent('ktm.jpg', $this->jpegHeader()),
            'sk_organisasi'            => UploadedFile::fake()->createWithContent('sk_org.pdf', $this->pdfHeader()),
            'sk_kepengurusan'          => UploadedFile::fake()->createWithContent('sk_kep.pdf', $this->pdfHeader()),
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // 1. GET /api/public/bem
    // -------------------------------------------------------------------------

    public function test_get_public_bem_hanya_menampilkan_bem_aktif_dan_urut_nama_universitas(): void
    {
        $bemB = $this->buatBemAktif('Universitas B', 'BEM Univ B');
        $bemA = $this->buatBemAktif('Universitas A', 'BEM Univ A');

        // BEM nonaktif tidak boleh muncul
        $univC = Universitas::firstOrCreate(['nama_universitas' => 'Universitas C']);
        $pC = Pengguna::factory()->akunOrganisasi()->create(['status_akun' => 'menunggu']);
        Organisasi::create([
            'id_universitas'     => $univC->id_universitas,
            'id_akun_organisasi' => $pC->id_pengguna,
            'nama_organisasi'    => 'BEM Univ C Menunggu',
            'jenis_organisasi'   => 'bem',
            'nomor_telepon'      => '0812345',
            'status_organisasi'  => 'menunggu',
        ]);

        $response = $this->getJson('/api/public/bem');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daftar BEM aktif berhasil diambil',
            ]);

        $bems = $response->json('data.bem');
        $this->assertCount(2, $bems);
        $this->assertEquals('Universitas A', $bems[0]['nama_universitas']);
        $this->assertEquals('Universitas B', $bems[1]['nama_universitas']);
    }

    // -------------------------------------------------------------------------
    // 2. HAPPY PATH Registrasi HMJ/UKM
    // -------------------------------------------------------------------------

    public function test_registrasi_hmj_berhasil_201_dan_role_spatie_akun_hmj_ukm(): void
    {
        $this->fakeStorage();
        $bem = $this->buatBemAktif();

        $response = $this->postJson('/api/register/hmj-ukm', $this->validPayload($bem));

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Registrasi HMJ berhasil dikirim',
                'data'    => [
                    'pendaftaran' => [
                        'jenis_organisasi'    => 'hmj',
                        'nama_organisasi'     => 'HMJ Teknik Informatika',
                        'id_organisasi_induk' => $bem->id_organisasi,
                        'nama_bem_induk'      => $bem->nama_organisasi,
                        'status_pendaftaran'  => 'menunggu',
                    ],
                ],
            ]);

        // Verifikasi database Pengguna
        $pengguna = Pengguna::where('username', 'ahmad_hmj')->first();
        $this->assertNotNull($pengguna);
        $this->assertEquals('akun_organisasi', $pengguna->peran);
        $this->assertEquals('menunggu', $pengguna->status_akun);
        $this->assertTrue($pengguna->hasRole('akun_hmj_ukm'));
        $this->assertFalse($pengguna->hasRole('akun_bem'));

        // Verifikasi Organisasi
        $organisasi = Organisasi::where('nama_organisasi', 'HMJ Teknik Informatika')->first();
        $this->assertNotNull($organisasi);
        $this->assertEquals('hmj', $organisasi->jenis_organisasi);
        $this->assertEquals($bem->id_universitas, $organisasi->id_universitas);
        $this->assertEquals($bem->id_organisasi, $organisasi->id_organisasi_induk);
        $this->assertEquals('menunggu', $organisasi->status_organisasi);

        // Verifikasi Pendaftaran & Berkas
        $pendaftaran = PendaftaranOrganisasi::where('id_organisasi', $organisasi->id_organisasi)->first();
        $this->assertNotNull($pendaftaran);
        $this->assertEquals('menunggu', $pendaftaran->status_pendaftaran);

        $files = PendaftaranOrganisasiFile::where('id_pendaftaran_organisasi', $pendaftaran->id_pendaftaran_organisasi)->get();
        $this->assertCount(2, $files);

        // Verifikasi Log Aktivitas
        $log = LogAktivitas::where('id_pengguna', $pengguna->id_pengguna)->first();
        $this->assertNotNull($log);
        $this->assertEquals('pendaftaran', $log->jenis_aktivitas);
        $this->assertStringContainsString("Pendaftaran HMJ HMJ Teknik Informatika ke {$bem->nama_organisasi}", $log->keterangan);
    }

    public function test_registrasi_ukm_berhasil_201(): void
    {
        $this->fakeStorage();
        $bem = $this->buatBemAktif();

        $payload = $this->validPayload($bem, [
            'jenis_organisasi' => 'ukm',
            'nama_organisasi'  => 'UKM Robotika',
            'username'         => 'ukm_robotika',
        ]);

        $response = $this->postJson('/api/register/hmj-ukm', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Registrasi UKM berhasil dikirim',
                'data'    => [
                    'pendaftaran' => [
                        'jenis_organisasi' => 'ukm',
                        'nama_organisasi'  => 'UKM Robotika',
                    ],
                ],
            ]);

        $pengguna = Pengguna::where('username', 'ukm_robotika')->first();
        $this->assertTrue($pengguna->hasRole('akun_hmj_ukm'));
        $this->assertFalse($pengguna->hasRole('akun_bem'));
    }

    public function test_akun_hmj_baru_dapat_login_terbatas_dan_melihat_status_registrasi(): void
    {
        $this->fakeStorage();
        $bem = $this->buatBemAktif();

        $this->postJson('/api/register/hmj-ukm', $this->validPayload($bem));

        // Login dengan kredensial baru
        $loginRes = $this->postJson('/api/auth/login', [
            'username' => 'ahmad_hmj',
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

        // Akses endpoint status registrasi
        $statusRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/registration/status');

        $statusRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'pendaftar' => [
                        'username'        => 'ahmad_hmj',
                        'jenis_pendaftar' => 'organisasi',
                    ],
                    'status_pendaftaran' => 'menunggu',
                    'organisasi' => [
                        'nama_organisasi'  => 'HMJ Teknik Informatika',
                        'jenis_organisasi' => 'hmj',
                    ],
                ],
            ]);
    }

    // -------------------------------------------------------------------------
    // 3. VALIDASI GAGAL
    // -------------------------------------------------------------------------

    public function test_induk_tidak_ada_atau_bukan_bem_atau_belum_aktif_mengembalikan_422(): void
    {
        $this->fakeStorage();
        $bem = $this->buatBemAktif();

        // 1. ID tidak ada
        $payload1 = $this->validPayload($bem, ['id_organisasi_induk' => 99999]);
        $this->postJson('/api/register/hmj-ukm', $payload1)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['id_organisasi_induk']);

        // 2. Organisasi induk bukan bem (misal jenis hmj)
        $orgHmj = Organisasi::create([
            'id_universitas'     => $bem->id_universitas,
            'id_organisasi_induk'=> $bem->id_organisasi,
            'nama_organisasi'    => 'HMJ Lain',
            'jenis_organisasi'   => 'hmj',
            'nomor_telepon'      => '081234',
            'status_organisasi'  => 'aktif',
        ]);
        $payload2 = $this->validPayload($bem, ['id_organisasi_induk' => $orgHmj->id_organisasi]);
        $this->postJson('/api/register/hmj-ukm', $payload2)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['id_organisasi_induk']);

        // 3. BEM belum aktif (menunggu)
        $bemMenunggu = Organisasi::create([
            'id_universitas'     => $bem->id_universitas,
            'nama_organisasi'    => 'BEM Menunggu',
            'jenis_organisasi'   => 'bem',
            'nomor_telepon'      => '081234',
            'status_organisasi'  => 'menunggu',
        ]);
        $payload3 = $this->validPayload($bem, ['id_organisasi_induk' => $bemMenunggu->id_organisasi]);
        $this->postJson('/api/register/hmj-ukm', $payload3)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['id_organisasi_induk']);
    }

    public function test_nama_organisasi_kembar_dalam_universitas_sama_mengembalikan_422(): void
    {
        $this->fakeStorage();
        $bem = $this->buatBemAktif();

        // Daftarkan yang pertama
        $this->postJson('/api/register/hmj-ukm', $this->validPayload($bem))->assertStatus(201);

        // Daftarkan yang kedua dengan nama kembar (variasi spasi/kapital)
        $payload = $this->validPayload($bem, [
            'username'        => 'user_beda',
            'nama_organisasi' => '  hmj teknik informatika  ',
        ]);

        $this->postJson('/api/register/hmj-ukm', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nama_organisasi']);
    }

    public function test_jenis_organisasi_selain_hmj_atau_ukm_mengembalikan_422(): void
    {
        $this->fakeStorage();
        $bem = $this->buatBemAktif();

        $payload = $this->validPayload($bem, ['jenis_organisasi' => 'bem']);
        $this->postJson('/api/register/hmj-ukm', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['jenis_organisasi']);
    }

    public function test_sk_lebih_dari_2mb_atau_bukan_pdf_mengembalikan_422(): void
    {
        $this->fakeStorage();
        $bem = $this->buatBemAktif();

        // Bukan PDF
        $payload1 = $this->validPayload($bem, [
            'sk_organisasi' => UploadedFile::fake()->create('sk.txt', 100),
        ]);
        $this->postJson('/api/register/hmj-ukm', $payload1)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sk_organisasi']);

        // Lebih dari 2 MB
        $payload2 = $this->validPayload($bem, [
            'sk_kepengurusan' => UploadedFile::fake()->createWithContent(
                'sk_besar.pdf',
                $this->pdfHeader() . str_repeat('A', 2049 * 1024)
            ),
        ]);
        $this->postJson('/api/register/hmj-ukm', $payload2)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sk_kepengurusan']);
    }

    public function test_foto_ktm_png_atau_lebih_dari_1mb_mengembalikan_422(): void
    {
        $this->fakeStorage();
        $bem = $this->buatBemAktif();

        // PNG
        $payload1 = $this->validPayload($bem, [
            'foto_ktm' => UploadedFile::fake()->create('ktm.png', 200, 'image/png'),
        ]);
        $this->postJson('/api/register/hmj-ukm', $payload1)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['foto_ktm']);

        // Lebih dari 1 MB
        $payload2 = $this->validPayload($bem, [
            'foto_ktm' => UploadedFile::fake()->createWithContent(
                'ktm_besar.jpg',
                $this->jpegHeader() . str_repeat('A', 1025 * 1024)
            ),
        ]);
        $this->postJson('/api/register/hmj-ukm', $payload2)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['foto_ktm']);
    }

    public function test_nomor_whatsapp_salah_mengembalikan_422(): void
    {
        $this->fakeStorage();
        $bem = $this->buatBemAktif();

        // Tidak diawali 08
        $payload1 = $this->validPayload($bem, ['nomor_whatsapp' => '071234567890']);
        $this->postJson('/api/register/hmj-ukm', $payload1)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nomor_whatsapp']);

        // Kurang dari 10 digit
        $payload2 = $this->validPayload($bem, ['nomor_whatsapp' => '081234567']);
        $this->postJson('/api/register/hmj-ukm', $payload2)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nomor_whatsapp']);
    }

    public function test_password_lemah_mengembalikan_422(): void
    {
        $this->fakeStorage();
        $bem = $this->buatBemAktif();

        // Hanya huruf tanpa angka
        $payload1 = $this->validPayload($bem, ['password' => 'hanyahurufsaja']);
        $this->postJson('/api/register/hmj-ukm', $payload1)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        // Kurang dari 8 karakter
        $payload2 = $this->validPayload($bem, ['password' => 'Pass1']);
        $this->postJson('/api/register/hmj-ukm', $payload2)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_rollback_transaksi_menghapus_file_yang_sempat_disimpan(): void
    {
        $this->fakeStorage();
        $bem = $this->buatBemAktif();

        // Simulasi error DB di tengah transaksi dengan menduplikasi username
        Pengguna::factory()->create(['username' => 'user_tabrakan']);

        $payload = $this->validPayload($bem, ['username' => 'user_tabrakan']);

        $this->postJson('/api/register/hmj-ukm', $payload)->assertStatus(422);

        // Pastikan tidak ada file yang tertinggal di disk local
        $filesKtm = Storage::disk('local')->allFiles('pendaftaran/ktm');
        $filesSkOrg = Storage::disk('local')->allFiles('pendaftaran/sk_organisasi');
        $filesSkKep = Storage::disk('local')->allFiles('pendaftaran/sk_kepengurusan');

        $this->assertEmpty($filesKtm);
        $this->assertEmpty($filesSkOrg);
        $this->assertEmpty($filesSkKep);
    }
}
