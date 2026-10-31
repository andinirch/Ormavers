<?php

namespace Tests\Feature;

use App\Models\Keanggotaan;
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

class StatusRegistrasiTest extends TestCase
{
    use RefreshDatabase;

    private function pdfHeader(): string
    {
        return '%PDF-1.4' . str_repeat("\x00", 100);
    }

    private function buatPendaftaranOrganisasi(array $attributes = []): array
    {
        Storage::fake('local');

        $statusAkun = $attributes['status_akun'] ?? 'menunggu';
        $statusPendaftaran = $attributes['status_pendaftaran'] ?? 'menunggu';

        $univ = Universitas::factory()->create([
            'nama_universitas' => 'Universitas ' . uniqid(),
        ]);

        $pengguna = Pengguna::factory()->akunOrganisasi()->create([
            'username'    => 'bem_user_' . uniqid(),
            'status_akun' => $statusAkun,
        ]);

        \App\Models\ProfilAnggota::create([
            'id_pengguna'    => $pengguna->id_pengguna,
            'foto_ktm'       => 'pendaftaran/ktm/ktm.jpg',
            'id_universitas' => $univ->id_universitas,
        ]);

        $organisasi = Organisasi::create([
            'id_universitas'     => $univ->id_universitas,
            'id_akun_organisasi' => $pengguna->id_pengguna,
            'nama_organisasi'    => 'BEM ' . $univ->nama_universitas,
            'jenis_organisasi'   => 'bem',
            'nomor_telepon'      => '081234567890',
            'status_organisasi'  => $attributes['status_organisasi'] ?? 'menunggu',
        ]);

        $pendaftaran = PendaftaranOrganisasi::create([
            'id_organisasi'           => $organisasi->id_organisasi,
            'status_pendaftaran'      => $statusPendaftaran,
            'alasan_penolakan'        => $attributes['alasan_penolakan'] ?? null,
            'id_pengguna_verifikator' => $attributes['id_pengguna_verifikator'] ?? null,
            'tanggal_pendaftaran'     => now()->subDays(2),
            'tanggal_verifikasi'      => $attributes['tanggal_verifikasi'] ?? null,
        ]);

        // Simpan file dummy di storage
        $pathSkOrg = 'pendaftaran/sk_organisasi/sk_lama_' . uniqid() . '.pdf';
        $pathSkKep = 'pendaftaran/sk_kepengurusan/sk_lama_' . uniqid() . '.pdf';
        Storage::disk('local')->put($pathSkOrg, 'isi-sk-org-lama');
        Storage::disk('local')->put($pathSkKep, 'isi-sk-kep-lama');

        $fileOrg = PendaftaranOrganisasiFile::create([
            'id_pendaftaran_organisasi' => $pendaftaran->id_pendaftaran_organisasi,
            'jenis_dokumen'             => 'sk_organisasi',
            'nama_file_asli'            => 'sk_org_asli.pdf',
            'path_file'                 => $pathSkOrg,
            'ukuran_kb'                 => 120,
            'diunggah_pada'             => now()->subDays(2),
        ]);

        $fileKep = PendaftaranOrganisasiFile::create([
            'id_pendaftaran_organisasi' => $pendaftaran->id_pendaftaran_organisasi,
            'jenis_dokumen'             => 'sk_kepengurusan',
            'nama_file_asli'            => 'sk_kep_asli.pdf',
            'path_file'                 => $pathSkKep,
            'ukuran_kb'                 => 150,
            'diunggah_pada'             => now()->subDays(2),
        ]);

        return compact('univ', 'pengguna', 'organisasi', 'pendaftaran', 'fileOrg', 'fileKep');
    }

    // -------------------------------------------------------
    // 1. LOGIN TERBATAS (UC-08)
    // -------------------------------------------------------

    public function test_login_menunggu_dan_ditolak_mendapat_akses_terbatas(): void
    {
        // Status Menunggu
        $dataMenunggu = $this->buatPendaftaranOrganisasi(['status_akun' => 'menunggu']);
        $resMenunggu = $this->postJson('/api/auth/login', [
            'username' => $dataMenunggu['pengguna']->username,
            'password' => 'password',
        ]);

        $resMenunggu->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login terbatas: hanya dapat melihat status registrasi',
                'data'    => [
                    'akses_terbatas' => true,
                    'permissions'    => [],
                ],
            ]);

        $tokenMenunggu = $resMenunggu->json('data.token');
        $this->assertNotEmpty($tokenMenunggu);

        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna'     => $dataMenunggu['pengguna']->id_pengguna,
            'jenis_aktivitas' => 'login',
            'keterangan'      => 'Berhasil masuk dengan akses terbatas dari IP 127.0.0.1',
        ]);

        // Status Ditolak
        $dataDitolak = $this->buatPendaftaranOrganisasi(['status_akun' => 'ditolak', 'status_pendaftaran' => 'ditolak']);
        $resDitolak = $this->postJson('/api/auth/login', [
            'username' => $dataDitolak['pengguna']->username,
            'password' => 'password',
        ]);

        $resDitolak->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login terbatas: hanya dapat melihat status registrasi',
                'data'    => [
                    'akses_terbatas' => true,
                    'permissions'    => [],
                ],
            ]);

        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna'     => $dataDitolak['pengguna']->id_pengguna,
            'jenis_aktivitas' => 'login',
            'keterangan'      => 'Berhasil masuk dengan akses terbatas dari IP 127.0.0.1',
        ]);
    }

    public function test_login_nonaktif_atau_ditangguhkan_tetap_403(): void
    {
        $nonaktif = Pengguna::factory()->create(['status_akun' => 'nonaktif']);
        $this->postJson('/api/auth/login', [
            'username' => $nonaktif->username,
            'password' => 'password',
        ])->assertStatus(403)
          ->assertJson([
              'success' => false,
              'message' => 'Akun dinonaktifkan',
          ]);

        $ditangguhkan = Pengguna::factory()->create(['status_akun' => 'ditangguhkan']);
        $this->postJson('/api/auth/login', [
            'username' => $ditangguhkan->username,
            'password' => 'password',
        ])->assertStatus(403)
          ->assertJson([
              'success' => false,
              'message' => 'Akun ditangguhkan',
          ]);
    }

    public function test_token_terbatas_ditolak_di_rute_biasa_403(): void
    {
        $data = $this->buatPendaftaranOrganisasi(['status_akun' => 'menunggu']);
        $res = $this->postJson('/api/auth/login', [
            'username' => $data['pengguna']->username,
            'password' => 'password',
        ]);

        $token = $res->json('data.token');
        $headers = ['Authorization' => 'Bearer ' . $token];

        auth()->forgetGuards();
        $this->getJson('/api/auth/me', $headers)
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Silakan login ulang',
            ]);

        auth()->forgetGuards();
        $this->getJson('/api/profil', $headers)
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Silakan login ulang',
            ]);

        auth()->forgetGuards();
        $this->getJson('/api/admin/registrations/bem', $headers)
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Silakan login ulang',
            ]);
    }


    // -------------------------------------------------------
    // 2. GET /api/registration/status (UC-04)
    // -------------------------------------------------------

    public function test_get_status_organisasi_menunggu(): void
    {
        $data = $this->buatPendaftaranOrganisasi([
            'status_akun'        => 'menunggu',
            'status_pendaftaran' => 'menunggu',
        ]);

        $token = $data['pengguna']->createToken('auth_token', ['status-registrasi'])->plainTextToken;

        $response = $this->getJson('/api/registration/status', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Status registrasi berhasil diambil',
                'data'    => [
                    'pendaftar' => [
                        'id_pengguna'     => $data['pengguna']->id_pengguna,
                        'nama'            => $data['pengguna']->nama,
                        'username'        => $data['pengguna']->username,
                        'jenis_pendaftar' => 'organisasi',
                    ],
                    'status_pendaftaran'  => 'menunggu',
                    'alasan_penolakan'    => null,
                    'organisasi'          => [
                        'id_organisasi'     => $data['organisasi']->id_organisasi,
                        'nama_organisasi'   => $data['organisasi']->nama_organisasi,
                        'jenis_organisasi'  => 'bem',
                        'nama_universitas'  => $data['univ']->nama_universitas,
                        'status_organisasi' => 'menunggu',
                    ],
                    'bisa_unggah_ulang'   => false,
                    'batas_ukuran_sk_mb'  => 2,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'dokumen' => [
                        '*' => ['jenis_dokumen', 'nama_file_asli', 'ukuran_kb', 'diunggah_pada'],
                    ],
                    'tanggal_pendaftaran',
                ],
            ]);

        $content = $response->getContent();
        $this->assertStringNotContainsString('kata_sandi', $content);
        $this->assertStringNotContainsString('password', strtolower($content));
        $this->assertStringNotContainsString('pendaftaran/sk_organisasi', $content);
        $this->assertStringNotContainsString('path_file', $content);
    }

    public function test_get_status_organisasi_ditolak_bisa_unggah_ulang_true(): void
    {
        $data = $this->buatPendaftaranOrganisasi([
            'status_akun'        => 'ditolak',
            'status_pendaftaran' => 'ditolak',
            'alasan_penolakan'   => 'SK Kepengurusan tidak stempel basah',
            'tanggal_verifikasi' => now()->subDay(),
        ]);

        $token = $data['pengguna']->createToken('auth_token', ['status-registrasi'])->plainTextToken;

        $response = $this->getJson('/api/registration/status', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'status_pendaftaran' => 'ditolak',
                    'alasan_penolakan'   => 'SK Kepengurusan tidak stempel basah',
                    'bisa_unggah_ulang'  => true,
                ],
            ]);
    }

    public function test_get_status_organisasi_disetujui(): void
    {
        $data = $this->buatPendaftaranOrganisasi([
            'status_akun'        => 'aktif',
            'status_pendaftaran' => 'disetujui',
            'status_organisasi'  => 'aktif',
            'tanggal_verifikasi' => now()->subDay(),
        ]);

        $token = $data['pengguna']->createToken('auth_token', ['*'])->plainTextToken;

        $response = $this->getJson('/api/registration/status', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'status_pendaftaran' => 'disetujui',
                    'bisa_unggah_ulang'  => false,
                    'organisasi'         => [
                        'status_organisasi' => 'aktif',
                    ],
                ],
            ]);
    }

    public function test_get_status_anggota_dari_keanggotaan(): void
    {
        $keanggotaan = Keanggotaan::factory()->create([
            'status_keanggotaan' => 'menunggu',
        ]);
        $pengguna = $keanggotaan->pengguna;
        $pengguna->update(['status_akun' => 'menunggu']);

        $token = $pengguna->createToken('auth_token', ['status-registrasi'])->plainTextToken;

        $response = $this->getJson('/api/registration/status', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Status registrasi berhasil diambil',
                'data'    => [
                    'pendaftar' => [
                        'id_pengguna'     => $pengguna->id_pengguna,
                        'nama'            => $pengguna->nama,
                        'username'        => $pengguna->username,
                        'jenis_pendaftar' => 'anggota',
                    ],
                    'status_pendaftaran' => 'menunggu',
                    'alasan_penolakan'   => null,
                    'organisasi'         => [
                        'id_organisasi'   => $keanggotaan->organisasi->id_organisasi,
                        'nama_organisasi' => $keanggotaan->organisasi->nama_organisasi,
                    ],
                    'bisa_unggah_ulang'  => false,
                    'batas_ukuran_sk_mb' => 2,
                ],
            ]);

        $this->assertArrayNotHasKey('dokumen', $response->json('data'));
    }

    public function test_get_status_super_admin_tanpa_registrasi_mengembalikan_404(): void
    {
        $superAdmin = Pengguna::factory()->superAdmin()->create();
        $token = $superAdmin->createToken('auth_token', ['*'])->plainTextToken;

        $response = $this->getJson('/api/registration/status', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Data registrasi tidak ditemukan',
            ]);
    }

    public function test_get_status_tanpa_token_401(): void
    {
        $this->getJson('/api/registration/status')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated',
            ]);
    }

    public function test_get_status_akun_nonaktif_atau_ditangguhkan_403(): void
    {
        $userNonaktif = Pengguna::factory()->create(['status_akun' => 'nonaktif']);
        $tokenNonaktif = $userNonaktif->createToken('auth_token', ['status-registrasi'])->plainTextToken;

        $this->getJson('/api/registration/status', [
            'Authorization' => 'Bearer ' . $tokenNonaktif,
        ])->assertStatus(403)
          ->assertJson([
              'success' => false,
              'message' => 'Akun dinonaktifkan',
          ]);

        auth()->forgetGuards();

        $userDitangguhkan = Pengguna::factory()->create(['status_akun' => 'ditangguhkan']);
        $tokenDitangguhkan = $userDitangguhkan->createToken('auth_token', ['status-registrasi'])->plainTextToken;

        $this->getJson('/api/registration/status', [
            'Authorization' => 'Bearer ' . $tokenDitangguhkan,
        ])->assertStatus(403)
          ->assertJson([
              'success' => false,
              'message' => 'Akun ditangguhkan',
          ]);
    }

    // -------------------------------------------------------
    // 3. POST /api/registration/resubmit-sk
    // -------------------------------------------------------

    public function test_resubmit_sk_berhasil_memperbarui_dokumen_dan_mengubah_status_menjadi_menunggu(): void
    {
        $data = $this->buatPendaftaranOrganisasi([
            'status_akun'             => 'ditolak',
            'status_pendaftaran'      => 'ditolak',
            'alasan_penolakan'        => 'Dokumen SK buram dan tidak terbaca',
            'id_pengguna_verifikator' => 1,
            'tanggal_verifikasi'      => now()->subDay(),
        ]);

        $pathSkOrgLama = $data['fileOrg']->path_file;
        $pathSkKepLama = $data['fileKep']->path_file;

        $this->assertTrue(Storage::disk('local')->exists($pathSkOrgLama));
        $this->assertTrue(Storage::disk('local')->exists($pathSkKepLama));

        $token = $data['pengguna']->createToken('auth_token', ['status-registrasi'])->plainTextToken;

        $skOrgBaru = UploadedFile::fake()->createWithContent('sk_org_revisi.pdf', $this->pdfHeader() . str_repeat('A', 500));
        $skKepBaru = UploadedFile::fake()->createWithContent('sk_kep_revisi.pdf', $this->pdfHeader() . str_repeat('B', 600));

        $response = $this->postJson('/api/registration/resubmit-sk', [
            'sk_organisasi'   => $skOrgBaru,
            'sk_kepengurusan' => $skKepBaru,
        ], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Dokumen SK Berhasil Diperbarui',
                'data'    => [
                    'status_pendaftaran' => 'menunggu',
                    'alasan_penolakan'   => null,
                    'bisa_unggah_ulang'  => false,
                ],
            ]);

        // 1. Pendaftaran organisasi diperbarui: status_pendaftaran = menunggu, verifikator & tanggal null
        $pendaftaranFresh = $data['pendaftaran']->fresh();
        $this->assertEquals('menunggu', $pendaftaranFresh->status_pendaftaran);
        $this->assertNull($pendaftaranFresh->alasan_penolakan);
        $this->assertNull($pendaftaranFresh->id_pengguna_verifikator);
        $this->assertNull($pendaftaranFresh->tanggal_verifikasi);

        // 2. Status akun pengguna kembali menjadi menunggu
        $this->assertEquals('menunggu', $data['pengguna']->fresh()->status_akun);

        // 3. File lama dihapus dari storage
        $this->assertFalse(Storage::disk('local')->exists($pathSkOrgLama));
        $this->assertFalse(Storage::disk('local')->exists($pathSkKepLama));

        // 4. File baru tersimpan di database dan di disk
        $fileOrgFresh = PendaftaranOrganisasiFile::where('id_pendaftaran_organisasi', $pendaftaranFresh->id_pendaftaran_organisasi)
            ->where('jenis_dokumen', 'sk_organisasi')->first();
        $fileKepFresh = PendaftaranOrganisasiFile::where('id_pendaftaran_organisasi', $pendaftaranFresh->id_pendaftaran_organisasi)
            ->where('jenis_dokumen', 'sk_kepengurusan')->first();

        $this->assertNotNull($fileOrgFresh);
        $this->assertEquals('sk_org_revisi.pdf', $fileOrgFresh->nama_file_asli);
        $this->assertTrue(Storage::disk('local')->exists($fileOrgFresh->path_file));

        $this->assertNotNull($fileKepFresh);
        $this->assertEquals('sk_kep_revisi.pdf', $fileKepFresh->nama_file_asli);
        $this->assertTrue(Storage::disk('local')->exists($fileKepFresh->path_file));

        // 5. Log aktivitas tercatat dengan pelaku id_pengguna
        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna'     => $data['pengguna']->id_pengguna,
            'jenis_aktivitas' => 'pendaftaran',
            'keterangan'      => "Memperbarui dokumen SK pendaftaran bem {$data['organisasi']->nama_organisasi}",
        ]);

        // 6. Muncul kembali di daftar verifikasi Administrator
        auth()->forgetGuards();
        $admin = Pengguna::factory()->administrator()->create();
        $tokenAdmin = $admin->createToken('auth_token', ['*'])->plainTextToken;

        $resAdmin = $this->getJson('/api/admin/registrations/bem', [
            'Authorization' => 'Bearer ' . $tokenAdmin,
        ]);

        $resAdmin->assertStatus(200);
        $idsMenunggu = collect($resAdmin->json('data.pendaftaran'))->pluck('id_pendaftaran_organisasi')->all();
        $this->assertContains($pendaftaranFresh->id_pendaftaran_organisasi, $idsMenunggu);
    }

    public function test_resubmit_sk_hanya_satu_file_tetap_berhasil(): void
    {
        $data = $this->buatPendaftaranOrganisasi([
            'status_akun'        => 'ditolak',
            'status_pendaftaran' => 'ditolak',
        ]);

        $token = $data['pengguna']->createToken('auth_token', ['status-registrasi'])->plainTextToken;

        $skOrgBaru = UploadedFile::fake()->createWithContent('sk_org_saja.pdf', $this->pdfHeader());

        $response = $this->postJson('/api/registration/resubmit-sk', [
            'sk_organisasi' => $skOrgBaru,
        ], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Dokumen SK Berhasil Diperbarui',
            ]);
    }

    public function test_resubmit_sk_oleh_anggota_ditolak_403(): void
    {
        $keanggotaan = Keanggotaan::factory()->create(['status_keanggotaan' => 'ditolak']);
        $pengguna = $keanggotaan->pengguna;
        $pengguna->update(['status_akun' => 'ditolak']);

        $token = $pengguna->createToken('auth_token', ['status-registrasi'])->plainTextToken;

        $response = $this->postJson('/api/registration/resubmit-sk', [
            'sk_organisasi' => UploadedFile::fake()->createWithContent('sk.pdf', $this->pdfHeader()),
        ], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Anggota tidak dapat mengunggah dokumen',
            ]);
    }

    public function test_resubmit_sk_status_bukan_ditolak_mengembalikan_409(): void
    {
        $data = $this->buatPendaftaranOrganisasi([
            'status_akun'        => 'menunggu',
            'status_pendaftaran' => 'menunggu',
        ]);

        $token = $data['pengguna']->createToken('auth_token', ['status-registrasi'])->plainTextToken;

        $response = $this->postJson('/api/registration/resubmit-sk', [
            'sk_organisasi' => UploadedFile::fake()->createWithContent('sk.pdf', $this->pdfHeader()),
        ], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Hanya pendaftaran yang ditolak yang dapat memperbarui dokumen',
            ]);
    }

    public function test_resubmit_sk_validasi_gagal_422(): void
    {
        $data = $this->buatPendaftaranOrganisasi([
            'status_akun'        => 'ditolak',
            'status_pendaftaran' => 'ditolak',
        ]);

        $token = $data['pengguna']->createToken('auth_token', ['status-registrasi'])->plainTextToken;
        $headers = ['Authorization' => 'Bearer ' . $token];

        // 1. Tanpa file sama sekali
        $r1 = $this->postJson('/api/registration/resubmit-sk', [], $headers);
        $r1->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid',
            ]);

        // 2. Format bukan PDF
        $r2 = $this->postJson('/api/registration/resubmit-sk', [
            'sk_organisasi' => UploadedFile::fake()->create('sk.jpg', 500, 'image/jpeg'),
        ], $headers);
        $r2->assertStatus(422)
            ->assertJsonValidationErrors(['sk_organisasi']);

        // 3. Ukuran lebih dari 2 MB (2049 KB)
        $r3 = $this->postJson('/api/registration/resubmit-sk', [
            'sk_organisasi' => UploadedFile::fake()->createWithContent('sk_besar.pdf', $this->pdfHeader() . str_repeat('X', 2049 * 1024)),
        ], $headers);
        $r3->assertStatus(422)
            ->assertJsonValidationErrors(['sk_organisasi']);
    }

    public function test_kegagalan_di_tengah_proses_resubmit_mempertahankan_file_lama(): void
    {
        $data = $this->buatPendaftaranOrganisasi([
            'status_akun'        => 'ditolak',
            'status_pendaftaran' => 'ditolak',
            'alasan_penolakan'   => 'SK ditolak',
        ]);

        $pathSkOrgLama = $data['fileOrg']->path_file;
        $this->assertTrue(Storage::disk('local')->exists($pathSkOrgLama));

        $token = $data['pengguna']->createToken('auth_token', ['status-registrasi'])->plainTextToken;

        // Pasang event listener throwing exception pada LogAktivitas
        LogAktivitas::creating(function () {
            throw new \RuntimeException('Simulasi gagal simpan log saat resubmit');
        });

        try {
            $this->postJson('/api/registration/resubmit-sk', [
                'sk_organisasi' => UploadedFile::fake()->createWithContent('sk_baru.pdf', $this->pdfHeader()),
            ], [
                'Authorization' => 'Bearer ' . $token,
            ]);
        } catch (\RuntimeException) {
        } finally {
            LogAktivitas::flushEventListeners();
        }

        // File lama tetap ada di disk
        $this->assertTrue(Storage::disk('local')->exists($pathSkOrgLama));

        // Status pendaftaran tetap ditolak
        $this->assertEquals('ditolak', $data['pendaftaran']->fresh()->status_pendaftaran);

        // Tidak ada file baru yatim di disk
        $semuaFile = Storage::disk('local')->allFiles('pendaftaran/sk_organisasi');
        $this->assertEquals([$pathSkOrgLama], $semuaFile);
    }
}
