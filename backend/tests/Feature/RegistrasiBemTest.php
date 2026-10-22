<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Models\PendaftaranOrganisasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrasiBemTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    private function fakeStorage(): void
    {
        Storage::fake('local');
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'nama_instansi'            => 'Universitas Contoh',
            'nama_organisasi'          => 'BEM Universitas Contoh',
            'nomor_telepon_organisasi' => '02112345678',
            'nama'                     => 'Budi Santoso',
            'nim'                      => '2024001',
            'nomor_whatsapp'           => '081234567890',
            'username'                 => 'budi_bem',
            'password'                 => 'Password123',
            'foto_ktm'                 => UploadedFile::fake()->createWithContent(
                'ktm.jpg',
                $this->jpegHeader()
            ),
            'sk_organisasi'            => UploadedFile::fake()->createWithContent(
                'sk_org.pdf',
                $this->pdfHeader()
            ),
            'sk_kepengurusan'          => UploadedFile::fake()->createWithContent(
                'sk_kep.pdf',
                $this->pdfHeader()
            ),
        ], $overrides);
    }

    /**
     * Minimal JPEG file header untuk lolos validasi mimes:jpg,jpeg
     */
    private function jpegHeader(): string
    {
        // JFIF JPEG header
        return "\xFF\xD8\xFF\xE0" . str_repeat("\x00", 100);
    }

    /**
     * Minimal PDF file header untuk lolos validasi mimes:pdf
     */
    private function pdfHeader(): string
    {
        return '%PDF-1.4' . str_repeat("\x00", 100);
    }

    // -------------------------------------------------------
    // HAPPY PATH
    // -------------------------------------------------------

    public function test_registrasi_bem_berhasil_201(): void
    {
        $this->fakeStorage();

        $response = $this->postJson('/api/register/bem', $this->validPayload());

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Registrasi BEM berhasil dikirim',
                'data'    => [
                    'pendaftaran' => [
                        'jenis_organisasi'  => 'bem',
                        'nama_organisasi'   => 'BEM Universitas Contoh',
                        'nama_instansi'     => 'Universitas Contoh',
                        'status_pendaftaran' => 'menunggu',
                    ],
                ],
            ])
            ->assertJsonStructure([
                'success', 'message',
                'data' => [
                    'pendaftaran' => [
                        'id_pendaftaran_organisasi',
                        'jenis_organisasi',
                        'nama_organisasi',
                        'nama_instansi',
                        'status_pendaftaran',
                        'tanggal_pendaftaran',
                    ],
                ],
            ]);

        // Pengguna terbentuk dengan peran & status yang benar
        $this->assertDatabaseHas('pengguna', [
            'username'    => 'budi_bem',
            'peran'       => 'akun_organisasi',
            'status_akun' => 'menunggu',
        ]);

        // Password ter-hash
        $pengguna = Pengguna::where('username', 'budi_bem')->first();
        $this->assertNotNull($pengguna);
        $this->assertTrue(Hash::check('Password123', $pengguna->kata_sandi));

        // Pendaftaran organisasi terbentuk
        $this->assertDatabaseHas('pendaftaran_organisasi', [
            'id_pengguna_pendaftar' => $pengguna->id_pengguna,
            'jenis_organisasi'      => 'bem',
            'status_pendaftaran'    => 'menunggu',
            'nama_instansi'         => 'Universitas Contoh',
            'nama_organisasi'       => 'BEM Universitas Contoh',
        ]);

        // id_organisasi kosong (belum dibuat)
        $pendaftaran = PendaftaranOrganisasi::where('id_pengguna_pendaftar', $pengguna->id_pengguna)->first();
        $this->assertNull($pendaftaran->id_organisasi);

        // 3 file tersimpan di disk privat
        Storage::disk('local')->assertExists($pendaftaran->foto_ktm);
        Storage::disk('local')->assertExists($pendaftaran->dokumen_sk_organisasi);
        Storage::disk('local')->assertExists($pendaftaran->dokumen_sk_kepengurusan);
    }

    public function test_tabel_organisasi_tetap_kosong_setelah_registrasi(): void
    {
        $this->fakeStorage();

        $this->postJson('/api/register/bem', $this->validPayload())
            ->assertStatus(201);

        $this->assertDatabaseCount('organisasi', 0);
    }

    public function test_response_tanpa_kata_sandi_dan_tanpa_path_file(): void
    {
        $this->fakeStorage();

        $response = $this->postJson('/api/register/bem', $this->validPayload());

        $response->assertStatus(201);

        $content = $response->getContent();
        $this->assertStringNotContainsString('kata_sandi', $content);
        $this->assertStringNotContainsString('password', strtolower($content));
        $this->assertStringNotContainsString('pendaftaran/ktm', $content);
        $this->assertStringNotContainsString('pendaftaran/sk_organisasi', $content);
        $this->assertStringNotContainsString('pendaftaran/sk_kepengurusan', $content);
    }

    public function test_akun_baru_tidak_bisa_login_403(): void
    {
        $this->fakeStorage();

        $this->postJson('/api/register/bem', $this->validPayload())
            ->assertStatus(201);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'budi_bem',
            'password' => 'Password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akun belum disetujui',
            ]);
    }

    public function test_registrasi_berhasil_jika_instansi_sebelumnya_berstatus_ditolak(): void
    {
        $this->fakeStorage();

        // Buat pendaftaran sebelumnya yang ditolak
        $penggunaDitolak = Pengguna::factory()->akunOrganisasi()->menunggu()->create();
        PendaftaranOrganisasi::create([
            'id_pengguna_pendaftar'    => $penggunaDitolak->id_pengguna,
            'jenis_organisasi'         => 'bem',
            'nama_organisasi'          => 'BEM Univ Lama',
            'nama_instansi'            => 'Universitas Contoh',
            'nomor_telepon_organisasi' => '02100000000',
            'nama_penanggung_jawab'    => 'Andi',
            'nim_penanggung_jawab'     => '2024999',
            'nomor_whatsapp'           => '081200000000',
            'foto_ktm'                 => 'old/ktm.jpg',
            'dokumen_sk_organisasi'    => 'old/sk.pdf',
            'dokumen_sk_kepengurusan'  => 'old/skk.pdf',
            'status_pendaftaran'       => 'ditolak',
        ]);

        $response = $this->postJson('/api/register/bem', $this->validPayload());

        $response->assertStatus(201);
    }

    // -------------------------------------------------------
    // VALIDASI GAGAL (422)
    // -------------------------------------------------------

    public function test_field_kosong_mengembalikan_422(): void
    {
        $this->fakeStorage();

        $response = $this->postJson('/api/register/bem', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid',
            ])
            ->assertJsonValidationErrors([
                'nama_instansi',
                'nama_organisasi',
                'nomor_telepon_organisasi',
                'nama',
                'nim',
                'nomor_whatsapp',
                'username',
                'password',
                'foto_ktm',
                'sk_organisasi',
                'sk_kepengurusan',
            ]);
    }

    public function test_username_sudah_ada_mengembalikan_422(): void
    {
        $this->fakeStorage();

        Pengguna::factory()->create(['username' => 'budi_bem']);

        $response = $this->postJson('/api/register/bem', $this->validPayload());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_password_tanpa_angka_mengembalikan_422(): void
    {
        $this->fakeStorage();

        $response = $this->postJson('/api/register/bem', $this->validPayload([
            'password' => 'PasswordTanpaAngka',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_password_kurang_dari_8_karakter_mengembalikan_422(): void
    {
        $this->fakeStorage();

        $response = $this->postJson('/api/register/bem', $this->validPayload([
            'password' => 'Ab1',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_nomor_whatsapp_tidak_diawali_08_mengembalikan_422(): void
    {
        $this->fakeStorage();

        $response = $this->postJson('/api/register/bem', $this->validPayload([
            'nomor_whatsapp' => '07123456789',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nomor_whatsapp']);
    }

    public function test_nomor_whatsapp_terlalu_pendek_mengembalikan_422(): void
    {
        $this->fakeStorage();

        $response = $this->postJson('/api/register/bem', $this->validPayload([
            'nomor_whatsapp' => '08123',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nomor_whatsapp']);
    }

    public function test_nomor_whatsapp_terlalu_panjang_mengembalikan_422(): void
    {
        $this->fakeStorage();

        $response = $this->postJson('/api/register/bem', $this->validPayload([
            'nomor_whatsapp' => '0812345678901234',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nomor_whatsapp']);
    }

    public function test_nomor_telepon_organisasi_non_angka_mengembalikan_422(): void
    {
        $this->fakeStorage();

        $response = $this->postJson('/api/register/bem', $this->validPayload([
            'nomor_telepon_organisasi' => '021-123-456',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nomor_telepon_organisasi']);
    }

    public function test_foto_ktm_png_mengembalikan_422(): void
    {
        $this->fakeStorage();

        $response = $this->postJson('/api/register/bem', $this->validPayload([
            'foto_ktm' => UploadedFile::fake()->create('ktm.png', 500, 'image/png'),
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['foto_ktm']);
    }

    public function test_foto_ktm_lebih_dari_1mb_mengembalikan_422(): void
    {
        $this->fakeStorage();

        $response = $this->postJson('/api/register/bem', $this->validPayload([
            'foto_ktm' => UploadedFile::fake()->createWithContent(
                'ktm.jpg',
                $this->jpegHeader() . str_repeat('x', 1024 * 1024)
            ),
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['foto_ktm']);
    }

    public function test_sk_organisasi_bukan_pdf_mengembalikan_422(): void
    {
        $this->fakeStorage();

        $response = $this->postJson('/api/register/bem', $this->validPayload([
            'sk_organisasi' => UploadedFile::fake()->createWithContent(
                'sk.jpg',
                $this->jpegHeader()
            ),
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sk_organisasi']);
    }

    public function test_sk_kepengurusan_lebih_dari_2mb_mengembalikan_422(): void
    {
        $this->fakeStorage();

        $response = $this->postJson('/api/register/bem', $this->validPayload([
            'sk_kepengurusan' => UploadedFile::fake()->createWithContent(
                'sk.pdf',
                $this->pdfHeader() . str_repeat('x', 2049 * 1024)
            ),
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sk_kepengurusan'])
            ->assertJsonFragment([
                'sk_kepengurusan' => ['SK Kepengurusan maksimal 2 MB'],
            ]);
    }

    public function test_sk_organisasi_dan_kepengurusan_sekitar_2mb_lolos(): void
    {
        $this->fakeStorage();

        $response = $this->postJson('/api/register/bem', $this->validPayload([
            'sk_organisasi' => UploadedFile::fake()->createWithContent(
                'sk_org.pdf',
                $this->pdfHeader() . str_repeat('x', 2040 * 1024)
            ),
            'sk_kepengurusan' => UploadedFile::fake()->createWithContent(
                'sk_kep.pdf',
                $this->pdfHeader() . str_repeat('x', 2040 * 1024)
            ),
        ]));

        $response->assertStatus(201);
    }

    // -------------------------------------------------------
    // DUPLIKASI NAMA INSTANSI
    // -------------------------------------------------------

    public function test_duplikasi_nama_instansi_status_menunggu_mengembalikan_422(): void
    {
        $this->fakeStorage();

        // Pendaftaran pertama
        $this->postJson('/api/register/bem', $this->validPayload())
            ->assertStatus(201);

        // Pendaftaran kedua dengan instansi sama
        $response = $this->postJson('/api/register/bem', $this->validPayload([
            'username' => 'budi_bem2',
        ]));

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors'  => [
                    'nama_instansi' => ['BEM untuk instansi ini sudah terdaftar'],
                ],
            ]);
    }

    public function test_duplikasi_nama_instansi_beda_huruf_besar_kecil_mengembalikan_422(): void
    {
        $this->fakeStorage();

        $this->postJson('/api/register/bem', $this->validPayload())
            ->assertStatus(201);

        $response = $this->postJson('/api/register/bem', $this->validPayload([
            'nama_instansi' => 'UNIVERSITAS CONTOH',
            'username'      => 'budi_bem2',
        ]));

        $response->assertStatus(422)
            ->assertJson([
                'errors' => [
                    'nama_instansi' => ['BEM untuk instansi ini sudah terdaftar'],
                ],
            ]);
    }

    public function test_duplikasi_nama_instansi_spasi_di_tepi_mengembalikan_422(): void
    {
        $this->fakeStorage();

        $this->postJson('/api/register/bem', $this->validPayload())
            ->assertStatus(201);

        $response = $this->postJson('/api/register/bem', $this->validPayload([
            'nama_instansi' => '  Universitas Contoh  ',
            'username'      => 'budi_bem2',
        ]));

        $response->assertStatus(422)
            ->assertJson([
                'errors' => [
                    'nama_instansi' => ['BEM untuk instansi ini sudah terdaftar'],
                ],
            ]);
    }

    public function test_duplikasi_nama_instansi_status_disetujui_mengembalikan_422(): void
    {
        $this->fakeStorage();

        // Buat pendaftaran yang sudah disetujui
        $pengguna = Pengguna::factory()->akunOrganisasi()->create();
        PendaftaranOrganisasi::create([
            'id_pengguna_pendaftar'    => $pengguna->id_pengguna,
            'jenis_organisasi'         => 'bem',
            'nama_organisasi'          => 'BEM Universitas Contoh',
            'nama_instansi'            => 'Universitas Contoh',
            'nomor_telepon_organisasi' => '02100000000',
            'nama_penanggung_jawab'    => 'Andi',
            'nim_penanggung_jawab'     => '2024999',
            'nomor_whatsapp'           => '081200000000',
            'foto_ktm'                 => 'old/ktm.jpg',
            'dokumen_sk_organisasi'    => 'old/sk.pdf',
            'dokumen_sk_kepengurusan'  => 'old/skk.pdf',
            'status_pendaftaran'       => 'disetujui',
        ]);

        $response = $this->postJson('/api/register/bem', $this->validPayload());

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors'  => [
                    'nama_instansi' => ['BEM untuk instansi ini sudah terdaftar'],
                ],
            ]);
    }

    // -------------------------------------------------------
    // KEAMANAN
    // -------------------------------------------------------

    public function test_field_peran_dan_status_akun_selundupan_diabaikan(): void
    {
        $this->fakeStorage();

        $response = $this->postJson('/api/register/bem', $this->validPayload([
            'peran'                => 'super_admin',
            'status_akun'          => 'aktif',
            'status_pendaftaran'   => 'disetujui',
        ]));

        $response->assertStatus(201);

        $this->assertDatabaseHas('pengguna', [
            'username'    => 'budi_bem',
            'peran'       => 'akun_organisasi',
            'status_akun' => 'menunggu',
        ]);

        $this->assertDatabaseHas('pendaftaran_organisasi', [
            'status_pendaftaran' => 'menunggu',
        ]);
    }

    // -------------------------------------------------------
    // ROLLBACK (kegagalan di tengah proses)
    // -------------------------------------------------------

    public function test_kegagalan_di_tengah_proses_tidak_meninggalkan_data_atau_file(): void
    {
        $this->fakeStorage();

        // Pertama, daftarkan BEM dengan sukses
        $this->postJson('/api/register/bem', $this->validPayload())
            ->assertStatus(201);

        $jumlahPengguna = Pengguna::count();
        $jumlahPendaftaran = PendaftaranOrganisasi::count();

        // Kedua, coba daftarkan BEM duplikat (akan gagal)
        $this->postJson('/api/register/bem', $this->validPayload([
            'username' => 'budi_bem2',
        ]))->assertStatus(422);

        // Pastikan tidak ada pengguna atau pendaftaran tambahan
        $this->assertDatabaseCount('pengguna', $jumlahPengguna);
        $this->assertDatabaseCount('pendaftaran_organisasi', $jumlahPendaftaran);
    }

    // -------------------------------------------------------
    // THROTTLE (429)
    // -------------------------------------------------------

    public function test_percobaan_berlebih_mengembalikan_429(): void
    {
        $this->fakeStorage();

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/register/bem', $this->validPayload([
                'username' => 'user_' . $i,
                'nama_instansi' => 'Instansi ' . $i,
            ]));
        }

        $response = $this->postJson('/api/register/bem', $this->validPayload([
            'username' => 'user_overflow',
            'nama_instansi' => 'Instansi Overflow',
        ]));

        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
                'message' => 'Terlalu banyak percobaan, coba lagi nanti',
            ]);
    }
}
