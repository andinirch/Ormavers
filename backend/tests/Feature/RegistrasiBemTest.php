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

    private function getOrCreateUniversitas(string $nama = 'Universitas Contoh'): Universitas
    {
        return Universitas::firstOrCreate(
            ['nama_universitas' => $nama],
            ['logo' => null]
        );
    }

    private function validPayload(array $overrides = []): array
    {
        $univ = $this->getOrCreateUniversitas();

        return array_merge([
            'id_universitas'           => $univ->id_universitas,
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

        $univ = $this->getOrCreateUniversitas();
        $response = $this->postJson('/api/register/bem', $this->validPayload());

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Registrasi BEM berhasil dikirim',
                'data'    => [
                    'pendaftaran' => [
                        'jenis_organisasi'   => 'bem',
                        'nama_organisasi'    => 'BEM Universitas Contoh',
                        'id_universitas'     => $univ->id_universitas,
                        'nama_universitas'   => 'Universitas Contoh',
                        'nama_instansi'      => 'Universitas Contoh',
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
                        'id_universitas',
                        'nama_universitas',
                        'nama_instansi',
                        'status_pendaftaran',
                        'tanggal_pendaftaran',
                    ],
                ],
            ]);

        // 1. Pengguna terbentuk dengan peran, status, nim, whatsapp, dan foto_ktm
        $this->assertDatabaseHas('pengguna', [
            'username'       => 'budi_bem',
            'peran'          => 'akun_organisasi',
            'status_akun'    => 'menunggu',
            'nim'            => '2024001',
            'nomor_whatsapp' => '081234567890',
        ]);

        $pengguna = Pengguna::where('username', 'budi_bem')->first();
        $this->assertNotNull($pengguna);
        $this->assertTrue(Hash::check('Password123', $pengguna->kata_sandi));
        $this->assertNotNull($pengguna->foto_ktm);

        // 2. Organisasi terbentuk dengan status menunggu
        $this->assertDatabaseHas('organisasi', [
            'id_universitas'     => $univ->id_universitas,
            'id_akun_organisasi' => $pengguna->id_pengguna,
            'nama_organisasi'    => 'BEM Universitas Contoh',
            'jenis_organisasi'   => 'bem',
            'status_organisasi'  => 'menunggu',
        ]);

        $organisasi = Organisasi::where('id_akun_organisasi', $pengguna->id_pengguna)->first();
        $this->assertNotNull($organisasi);

        // 3. Pendaftaran organisasi terbentuk
        $this->assertDatabaseHas('pendaftaran_organisasi', [
            'id_organisasi'      => $organisasi->id_organisasi,
            'status_pendaftaran' => 'menunggu',
        ]);

        $pendaftaran = PendaftaranOrganisasi::where('id_organisasi', $organisasi->id_organisasi)->first();
        $this->assertNotNull($pendaftaran);

        // 4. 2 baris file pendaftaran_organisasi_file terbentuk
        $this->assertDatabaseHas('pendaftaran_organisasi_file', [
            'id_pendaftaran_organisasi' => $pendaftaran->id_pendaftaran_organisasi,
            'jenis_dokumen'             => 'sk_organisasi',
        ]);
        $this->assertDatabaseHas('pendaftaran_organisasi_file', [
            'id_pendaftaran_organisasi' => $pendaftaran->id_pendaftaran_organisasi,
            'jenis_dokumen'             => 'sk_kepengurusan',
        ]);

        // 5. Berkas tersimpan di disk lokal
        $fileSkOrg = PendaftaranOrganisasiFile::where('id_pendaftaran_organisasi', $pendaftaran->id_pendaftaran_organisasi)
            ->where('jenis_dokumen', 'sk_organisasi')->first();
        $fileSkKep = PendaftaranOrganisasiFile::where('id_pendaftaran_organisasi', $pendaftaran->id_pendaftaran_organisasi)
            ->where('jenis_dokumen', 'sk_kepengurusan')->first();

        Storage::disk('local')->assertExists($pengguna->foto_ktm);
        Storage::disk('local')->assertExists($fileSkOrg->path_file);
        Storage::disk('local')->assertExists($fileSkKep->path_file);

        // 6. Log aktivitas pendaftaran tercatat
        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna'     => $pengguna->id_pengguna,
            'jenis_aktivitas' => 'pendaftaran',
        ]);
    }

    public function test_organisasi_berstatus_menunggu_setelah_registrasi(): void
    {
        $this->fakeStorage();

        $this->postJson('/api/register/bem', $this->validPayload())
            ->assertStatus(201);

        $this->assertDatabaseCount('organisasi', 1);
        $this->assertDatabaseHas('organisasi', [
            'status_organisasi' => 'menunggu',
            'jenis_organisasi'  => 'bem',
        ]);
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

    public function test_registrasi_berhasil_jika_bem_sebelumnya_berstatus_ditolak(): void
    {
        $this->fakeStorage();

        $univ = $this->getOrCreateUniversitas();

        // Buat BEM pendaftaran sebelumnya yang ditolak di universitas yang sama
        $penggunaDitolak = Pengguna::factory()->akunOrganisasi()->create(['status_akun' => 'ditolak']);
        $organisasiDitolak = Organisasi::create([
            'id_universitas'     => $univ->id_universitas,
            'id_akun_organisasi' => $penggunaDitolak->id_pengguna,
            'nama_organisasi'    => 'BEM Univ Lama',
            'jenis_organisasi'   => 'bem',
            'nomor_telepon'      => '02100000000',
            'status_organisasi'  => 'menunggu',
        ]);
        PendaftaranOrganisasi::create([
            'id_organisasi'       => $organisasiDitolak->id_organisasi,
            'status_pendaftaran'  => 'ditolak',
            'alasan_penolakan'    => 'SK tidak sesuai',
            'tanggal_pendaftaran' => now()->subDays(5),
            'tanggal_verifikasi'  => now()->subDays(4),
        ]);

        // Pendaftaran BEM baru untuk universitas yang sama berhasil 201
        $response = $this->postJson('/api/register/bem', $this->validPayload([
            'nama_organisasi' => 'BEM Baru Universitas Contoh',
            'username'        => 'budi_bem_baru',
        ]));

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
                'id_universitas',
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

    public function test_id_universitas_tidak_ada_mengembalikan_422(): void
    {
        $this->fakeStorage();

        $response = $this->postJson('/api/register/bem', $this->validPayload([
            'id_universitas' => 999999,
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_universitas']);
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
    // ATURAN BEM PER UNIVERSITAS & NAMA KEMBAR
    // -------------------------------------------------------

    public function test_duplikasi_bem_status_menunggu_mengembalikan_422(): void
    {
        $this->fakeStorage();

        // Pendaftaran pertama
        $this->postJson('/api/register/bem', $this->validPayload())
            ->assertStatus(201);

        // Pendaftaran BEM kedua untuk universitas yang sama
        $response = $this->postJson('/api/register/bem', $this->validPayload([
            'nama_organisasi' => 'BEM Berbeda',
            'username'        => 'budi_bem2',
        ]));

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors'  => [
                    'id_universitas' => ['BEM untuk universitas ini sudah terdaftar'],
                ],
            ]);
    }

    public function test_duplikasi_bem_status_disetujui_mengembalikan_422(): void
    {
        $this->fakeStorage();

        $univ = $this->getOrCreateUniversitas();

        $pengguna = Pengguna::factory()->akunOrganisasi()->create();
        $organisasi = Organisasi::create([
            'id_universitas'     => $univ->id_universitas,
            'id_akun_organisasi' => $pengguna->id_pengguna,
            'nama_organisasi'    => 'BEM Universitas Contoh',
            'jenis_organisasi'   => 'bem',
            'nomor_telepon'      => '02100000000',
            'status_organisasi'  => 'aktif',
        ]);
        PendaftaranOrganisasi::create([
            'id_organisasi'       => $organisasi->id_organisasi,
            'status_pendaftaran'  => 'disetujui',
            'tanggal_pendaftaran' => now(),
            'tanggal_verifikasi'  => now(),
        ]);

        $response = $this->postJson('/api/register/bem', $this->validPayload([
            'nama_organisasi' => 'BEM Baru Lagi',
            'username'        => 'budi_bem2',
        ]));

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors'  => [
                    'id_universitas' => ['BEM untuk universitas ini sudah terdaftar'],
                ],
            ]);
    }

    public function test_nama_organisasi_kembar_dalam_universitas_sama_mengembalikan_422(): void
    {
        $this->fakeStorage();

        $univ = $this->getOrCreateUniversitas();

        $pengguna = Pengguna::factory()->akunOrganisasi()->create();
        Organisasi::create([
            'id_universitas'     => $univ->id_universitas,
            'id_akun_organisasi' => $pengguna->id_pengguna,
            'nama_organisasi'    => 'BEM Universitas Contoh',
            'jenis_organisasi'   => 'hmj', // bukan BEM agar lolos aturan 1 BEM
            'nomor_telepon'      => '02100000000',
            'status_organisasi'  => 'aktif',
        ]);

        $response = $this->postJson('/api/register/bem', $this->validPayload([
            'nama_organisasi' => 'BEM Universitas Contoh',
            'username'        => 'budi_bem2',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nama_organisasi']);
    }

    // -------------------------------------------------------
    // KEAMANAN
    // -------------------------------------------------------

    public function test_field_peran_dan_status_akun_selundupan_diabaikan(): void
    {
        $this->fakeStorage();

        $response = $this->postJson('/api/register/bem', $this->validPayload([
            'peran'              => 'super_admin',
            'status_akun'        => 'aktif',
            'status_pendaftaran' => 'disetujui',
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

        // 1. Simpan satu pendaftaran sukses
        $this->postJson('/api/register/bem', $this->validPayload())
            ->assertStatus(201);

        $jumlahPengguna = Pengguna::count();
        $jumlahOrganisasi = Organisasi::count();
        $jumlahPendaftaran = PendaftaranOrganisasi::count();
        $jumlahFiles = PendaftaranOrganisasiFile::count();

        // 2. Coba daftarkan duplikat (gagal 422)
        $this->postJson('/api/register/bem', $this->validPayload([
            'username' => 'budi_bem2',
        ]))->assertStatus(422);

        // 3. Pastikan tidak ada data yang bertambah
        $this->assertDatabaseCount('pengguna', $jumlahPengguna);
        $this->assertDatabaseCount('organisasi', $jumlahOrganisasi);
        $this->assertDatabaseCount('pendaftaran_organisasi', $jumlahPendaftaran);
        $this->assertDatabaseCount('pendaftaran_organisasi_file', $jumlahFiles);
    }

    public function test_rollback_transaksi_menghapus_file_yang_sempat_disimpan(): void
    {
        $this->fakeStorage();

        // Pasang listener pada Organisasi yang melempar exception untuk mensimulasikan kegagalan transaksi
        Organisasi::creating(function () {
            throw new \RuntimeException('Simulasi error DB di tengah transaksi registrasi');
        });

        try {
            $this->postJson('/api/register/bem', $this->validPayload([
                'username' => 'user_rollback_test',
            ]));
        } catch (\RuntimeException) {
        } finally {
            Organisasi::flushEventListeners();
        }

        // Pengguna dan organisasi tidak tersimpan
        $this->assertDatabaseMissing('pengguna', ['username' => 'user_rollback_test']);
        $this->assertDatabaseCount('organisasi', 0);
        $this->assertDatabaseCount('pendaftaran_organisasi', 0);
        $this->assertDatabaseCount('pendaftaran_organisasi_file', 0);

        // Berkas di storage local bersih (tidak ada file yatim)
        $files = Storage::disk('local')->allFiles('pendaftaran');
        $this->assertEmpty($files);
    }

    // -------------------------------------------------------
    // THROTTLE (429)
    // -------------------------------------------------------

    public function test_percobaan_berlebih_mengembalikan_429(): void
    {
        $this->fakeStorage();

        $univ = $this->getOrCreateUniversitas();

        for ($i = 0; $i < 10; $i++) {
            $otherUniv = Universitas::create(['nama_universitas' => 'Univ Ke ' . $i]);
            $this->postJson('/api/register/bem', $this->validPayload([
                'id_universitas'  => $otherUniv->id_universitas,
                'nama_organisasi' => 'BEM Univ ' . $i,
                'username'        => 'user_' . $i,
            ]));
        }

        $otherUniv11 = Universitas::create(['nama_universitas' => 'Univ Ke 11']);
        $response = $this->postJson('/api/register/bem', $this->validPayload([
            'id_universitas'  => $otherUniv11->id_universitas,
            'nama_organisasi' => 'BEM Univ 11',
            'username'        => 'user_overflow',
        ]));

        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
                'message' => 'Terlalu banyak percobaan, coba lagi nanti',
            ]);
    }
}
