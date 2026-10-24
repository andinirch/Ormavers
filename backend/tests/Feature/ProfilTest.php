<?php

namespace Tests\Feature;

use App\Models\Keanggotaan;
use App\Models\Organisasi;
use App\Models\Pengguna;
use App\Models\Universitas;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProfilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function buatPengguna(string $peran = 'anggota', array $attributes = []): array
    {
        $state = match ($peran) {
            'super_admin'     => 'superAdmin',
            'administrator'   => 'administrator',
            'akun_organisasi' => 'akunOrganisasi',
            default           => null,
        };

        $factory = Pengguna::factory();
        if ($state) {
            $factory = $factory->{$state}();
        }

        $user = $factory->create(array_merge([
            'status_akun' => 'aktif',
            'kata_sandi'  => 'PasswordLama1',
        ], $attributes));

        $token = $user->createToken('auth_token')->plainTextToken;

        return [$user, $token, ['Authorization' => "Bearer {$token}"]];
    }

    // ──── C1: GET /api/profil ────────────────────────────────────────

    public function test_pengguna_melihat_data_profil_sendiri(): void
    {
        [$user, , $headers] = $this->buatPengguna('anggota', [
            'nama'           => 'Ahmad Mahasiswa',
            'username'       => 'ahmad_mhs',
            'nomor_whatsapp' => '081234567890',
        ]);

        auth()->forgetGuards();

        $response = $this->getJson('/api/profil', $headers);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Data profil berhasil diambil',
                'data'    => [
                    'id_pengguna'     => $user->id_pengguna,
                    'nama'            => 'Ahmad Mahasiswa',
                    'username'        => 'ahmad_mhs',
                    'role'            => 'anggota',
                    'peran'           => 'anggota',
                    'status_akun'     => 'aktif',
                    'nomor_whatsapp'  => '081234567890',
                    'foto_profil_url' => null,
                ],
            ]);

        $this->assertArrayNotHasKey('kata_sandi', $response->json('data'));
        $this->assertArrayHasKey('dibuat_pada', $response->json('data'));
    }

    public function test_berbagai_peran_dapat_mengakses_profil(): void
    {
        $perans = ['super_admin', 'administrator', 'akun_organisasi', 'anggota'];

        foreach ($perans as $peran) {
            [, , $headers] = $this->buatPengguna($peran);
            auth()->forgetGuards();

            $response = $this->getJson('/api/profil', $headers);
            $response->assertOk();
            $this->assertSame('aktif', $response->json('data.status_akun'));
        }
    }

    // ──── C2: PUT /api/profil ────────────────────────────────────────

    public function test_perbarui_nama_dan_nomor_whatsapp_berhasil(): void
    {
        [$user, , $headers] = $this->buatPengguna('anggota', [
            'nama'           => 'Nama Awal',
            'nomor_whatsapp' => '081111111111',
        ]);

        auth()->forgetGuards();

        $response = $this->putJson('/api/profil', [
            'nama'           => 'Nama Baru',
            'nomor_whatsapp' => '082222222222',
        ], $headers);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Data Berhasil Diperbarui',
                'data'    => [
                    'nama'           => 'Nama Baru',
                    'nomor_whatsapp' => '082222222222',
                ],
            ]);

        $this->assertDatabaseHas('pengguna', [
            'id_pengguna'    => $user->id_pengguna,
            'nama'           => 'Nama Baru',
            'nomor_whatsapp' => '082222222222',
        ]);
    }

    public function test_perbarui_profil_dengan_nomor_whatsapp_null(): void
    {
        [$user, , $headers] = $this->buatPengguna('anggota');

        auth()->forgetGuards();

        $response = $this->putJson('/api/profil', [
            'nama'           => 'Nama Baru Saja',
            'nomor_whatsapp' => null,
        ], $headers);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Data Berhasil Diperbarui',
                'data'    => [
                    'nama'           => 'Nama Baru Saja',
                    'nomor_whatsapp' => null,
                ],
            ]);
    }

    public function test_validasi_perbarui_profil_gagal(): void
    {
        [, , $headers] = $this->buatPengguna('anggota');

        auth()->forgetGuards();

        // Nama kosong
        $res1 = $this->putJson('/api/profil', ['nama' => ''], $headers);
        $res1->assertStatus(422)->assertJsonValidationErrors('nama');

        auth()->forgetGuards();

        // Nomor WA tidak diawali 08
        $res2 = $this->putJson('/api/profil', [
            'nama'           => 'Valid Name',
            'nomor_whatsapp' => '071234567890',
        ], $headers);
        $res2->assertStatus(422)->assertJsonValidationErrors('nomor_whatsapp');

        auth()->forgetGuards();

        // Nomor WA terlalu pendek (< 10 digit)
        $res3 = $this->putJson('/api/profil', [
            'nama'           => 'Valid Name',
            'nomor_whatsapp' => '0812345',
        ], $headers);
        $res3->assertStatus(422)->assertJsonValidationErrors('nomor_whatsapp');

        auth()->forgetGuards();

        // Nomor WA terlalu panjang (> 15 digit)
        $res4 = $this->putJson('/api/profil', [
            'nama'           => 'Valid Name',
            'nomor_whatsapp' => '081234567890123456',
        ], $headers);
        $res4->assertStatus(422)->assertJsonValidationErrors('nomor_whatsapp');
    }

    // ──── C3: PUT /api/profil/kata-sandi ─────────────────────────────

    public function test_ubah_kata_sandi_berhasil_dan_mencabut_token_lain(): void
    {
        [$user, $tokenAktif, $headers] = $this->buatPengguna('anggota', [
            'kata_sandi' => 'PasswordLama1',
        ]);

        // Buat token kedua untuk user ini (mensimulasikan login di device lain)
        $tokenLain = $user->createToken('token_device_lain')->plainTextToken;

        auth()->forgetGuards();

        // Pastikan token lain awalnya valid
        $this->getJson('/api/profil', ['Authorization' => "Bearer {$tokenLain}"])
            ->assertOk();

        auth()->forgetGuards();

        $payload = [
            'password_lama'       => 'PasswordLama1',
            'password_baru'       => 'PasswordBaru2',
            'konfirmasi_password' => 'PasswordBaru2',
        ];

        $response = $this->putJson('/api/profil/kata-sandi', $payload, $headers);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Password Berhasil Diubah',
            ]);

        // Cek database kata_sandi terupdate
        $user->refresh();
        $this->assertTrue(Hash::check('PasswordBaru2', $user->kata_sandi));

        // Token saat ini masih valid
        auth()->forgetGuards();
        $this->getJson('/api/profil', $headers)->assertOk();

        // Token lain sudah dicabut (401)
        auth()->forgetGuards();
        $this->getJson('/api/profil', ['Authorization' => "Bearer {$tokenLain}"])
            ->assertStatus(401);

        // Login dengan password baru berhasil
        auth()->forgetGuards();
        $loginRes = $this->postJson('/api/auth/login', [
            'username' => $user->username,
            'password' => 'PasswordBaru2',
        ]);
        $loginRes->assertOk();

        // Login dengan password lama gagal
        auth()->forgetGuards();
        $loginLamaRes = $this->postJson('/api/auth/login', [
            'username' => $user->username,
            'password' => 'PasswordLama1',
        ]);
        $loginLamaRes->assertStatus(401);
    }

    public function test_ubah_kata_sandi_password_lama_salah_422(): void
    {
        [, , $headers] = $this->buatPengguna('anggota', [
            'kata_sandi' => 'PasswordLama1',
        ]);

        auth()->forgetGuards();

        $response = $this->putJson('/api/profil/kata-sandi', [
            'password_lama'       => 'PasswordSalahBukanLama',
            'password_baru'       => 'PasswordBaru2',
            'konfirmasi_password' => 'PasswordBaru2',
        ], $headers);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password_lama');
    }

    public function test_ubah_kata_sandi_validasi_lemah_atau_tidak_cocok_422(): void
    {
        [, , $headers] = $this->buatPengguna('anggota', [
            'kata_sandi' => 'PasswordLama1',
        ]);

        auth()->forgetGuards();

        // Password baru kurang dari 8
        $res1 = $this->putJson('/api/profil/kata-sandi', [
            'password_lama'       => 'PasswordLama1',
            'password_baru'       => 'pass1',
            'konfirmasi_password' => 'pass1',
        ], $headers);
        $res1->assertStatus(422)->assertJsonValidationErrors('password_baru');

        auth()->forgetGuards();

        // Password baru hanya huruf tanpa angka
        $res2 = $this->putJson('/api/profil/kata-sandi', [
            'password_lama'       => 'PasswordLama1',
            'password_baru'       => 'passwordhanya',
            'konfirmasi_password' => 'passwordhanya',
        ], $headers);
        $res2->assertStatus(422)->assertJsonValidationErrors('password_baru');

        auth()->forgetGuards();

        // Konfirmasi password tidak sama
        $res3 = $this->putJson('/api/profil/kata-sandi', [
            'password_lama'       => 'PasswordLama1',
            'password_baru'       => 'PasswordBaru1',
            'konfirmasi_password' => 'PasswordBedaLagi2',
        ], $headers);
        $res3->assertStatus(422)->assertJsonValidationErrors('konfirmasi_password');
    }

    // ──── C4: POST /api/profil/foto ──────────────────────────────────

    public function test_unggah_foto_profil_berhasil_dan_mengganti_foto_lama(): void
    {
        Storage::fake('public');

        [$user, , $headers] = $this->buatPengguna('anggota');

        auth()->forgetGuards();

        // 1. Unggah foto pertama (JPEG magic bytes)
        $file1 = UploadedFile::fake()->createWithContent(
            'avatar1.jpg',
            "\xFF\xD8\xFF\xE0" . str_repeat("\x00", 200)
        );

        $response1 = $this->postJson('/api/profil/foto', [
            'foto' => $file1,
        ], $headers);

        $response1->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Foto Profil Berhasil Diubah',
            ])
            ->assertJsonStructure([
                'data' => ['foto_profil_url'],
            ]);

        $user->refresh();
        $this->assertNotNull($user->foto_profil);
        $fotoLamaPath = $user->foto_profil;
        Storage::disk('public')->assertExists($fotoLamaPath);

        auth()->forgetGuards();

        // 2. Unggah foto kedua (PNG magic bytes, mengganti foto lama)
        $file2 = UploadedFile::fake()->createWithContent(
            'avatar2.png',
            "\x89PNG\r\n\x1a\n" . str_repeat("\x00", 200)
        );

        $response2 = $this->postJson('/api/profil/foto', [
            'foto' => $file2,
        ], $headers);

        $response2->assertOk();

        $user->refresh();
        $fotoBaruPath = $user->foto_profil;

        $this->assertNotEquals($fotoLamaPath, $fotoBaruPath);
        Storage::disk('public')->assertMissing($fotoLamaPath);
        Storage::disk('public')->assertExists($fotoBaruPath);

        // GET /api/profil memuat foto_profil_url
        auth()->forgetGuards();
        $profilRes = $this->getJson('/api/profil', $headers);
        $this->assertStringContainsString($fotoBaruPath, $profilRes->json('data.foto_profil_url'));
    }

    public function test_unggah_foto_profil_format_salah_422(): void
    {
        Storage::fake('public');

        [, , $headers] = $this->buatPengguna('anggota');

        auth()->forgetGuards();

        $filePdf = UploadedFile::fake()->createWithContent(
            'dokumen.pdf',
            '%PDF-1.4' . str_repeat("\x00", 100)
        );

        $response = $this->postJson('/api/profil/foto', [
            'foto' => $filePdf,
        ], $headers);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('foto');
    }

    public function test_unggah_foto_profil_lebih_dari_2mb_422(): void
    {
        Storage::fake('public');

        [, , $headers] = $this->buatPengguna('anggota');

        auth()->forgetGuards();

        // 3 MB file (> 2048 KB)
        $fileBesar = UploadedFile::fake()->createWithContent(
            'besar.jpg',
            "\xFF\xD8\xFF\xE0" . str_repeat("\x00", 3 * 1024 * 1024)
        );

        $response = $this->postJson('/api/profil/foto', [
            'foto' => $fileBesar,
        ], $headers);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('foto');
    }

    // ──── Endpoint 401 Tanpa Token ──────────────────────────────────

    public function test_endpoint_profil_401_tanpa_token(): void
    {
        $endpoints = [
            ['GET', '/api/profil'],
            ['PUT', '/api/profil'],
            ['PUT', '/api/profil/kata-sandi'],
            ['POST', '/api/profil/foto'],
        ];

        foreach ($endpoints as [$method, $url]) {
            auth()->forgetGuards();
            $this->json($method, $url)->assertStatus(401);
        }
    }

    public function test_profil_memuat_nim_dan_organisasi_diikuti_kosong(): void
    {
        [$user, , $headers] = $this->buatPengguna('anggota', [
            'nim' => '20261001',
        ]);

        auth()->forgetGuards();

        $response = $this->getJson('/api/profil', $headers);

        $response->assertOk()
            ->assertJsonPath('data.nim', '20261001')
            ->assertJsonPath('data.organisasi_diikuti', []);
    }

    public function test_profil_memuat_daftar_organisasi_diikuti(): void
    {
        [$user, , $headers] = $this->buatPengguna('anggota', [
            'nim' => '20261002',
        ]);

        $univ = Universitas::factory()->create();
        $akunOrg = Pengguna::factory()->akunOrganisasi()->create();

        $org1 = Organisasi::create([
            'id_universitas'     => $univ->id_universitas,
            'id_akun_organisasi' => $akunOrg->id_pengguna,
            'nama_organisasi'    => 'BEM Fakultas Teknik',
            'jenis_organisasi'   => 'bem',
            'nomor_telepon'      => '081234567890',
            'status_organisasi'  => 'aktif',
        ]);

        $k1 = Keanggotaan::create([
            'id_pengguna'         => $user->id_pengguna,
            'id_organisasi'       => $org1->id_organisasi,
            'status_keanggotaan'  => 'disetujui',
            'tanggal_pendaftaran' => now()->subDays(5),
            'tanggal_verifikasi'  => now()->subDays(1),
        ]);

        auth()->forgetGuards();

        $response = $this->getJson('/api/profil', $headers);

        $response->assertOk()
            ->assertJsonPath('data.nim', '20261002')
            ->assertJsonCount(1, 'data.organisasi_diikuti')
            ->assertJsonPath('data.organisasi_diikuti.0.id_keanggotaan', $k1->id_keanggotaan)
            ->assertJsonPath('data.organisasi_diikuti.0.id_organisasi', $org1->id_organisasi)
            ->assertJsonPath('data.organisasi_diikuti.0.nama_organisasi', 'BEM Fakultas Teknik')
            ->assertJsonPath('data.organisasi_diikuti.0.jenis_organisasi', 'bem')
            ->assertJsonPath('data.organisasi_diikuti.0.status_keanggotaan', 'disetujui');

        $this->assertNotNull($response->json('data.organisasi_diikuti.0.tanggal_pendaftaran'));
        $this->assertNotNull($response->json('data.organisasi_diikuti.0.tanggal_verifikasi'));
    }

    public function test_profil_tidak_memasukkan_keanggotaan_yang_belum_disetujui(): void
    {
        [$user, , $headers] = $this->buatPengguna('anggota', [
            'nim' => '20261003',
        ]);

        $univ = Universitas::factory()->create();
        $akunOrg = Pengguna::factory()->akunOrganisasi()->create();

        $org = Organisasi::create([
            'id_universitas'     => $univ->id_universitas,
            'id_akun_organisasi' => $akunOrg->id_pengguna,
            'nama_organisasi'    => 'HMJ Informatika',
            'jenis_organisasi'   => 'hmj',
            'nomor_telepon'      => '081234567890',
            'status_organisasi'  => 'aktif',
        ]);

        Keanggotaan::create([
            'id_pengguna'         => $user->id_pengguna,
            'id_organisasi'       => $org->id_organisasi,
            'status_keanggotaan'  => 'menunggu',
            'tanggal_pendaftaran' => now(),
        ]);

        auth()->forgetGuards();

        $response = $this->getJson('/api/profil', $headers);

        $response->assertOk()
            ->assertJsonPath('data.nim', '20261003')
            ->assertJsonPath('data.organisasi_diikuti', []);
    }
}
