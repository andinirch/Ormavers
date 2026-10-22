<?php

namespace Tests\Feature;

use App\Models\LogAktivitas;
use App\Models\Organisasi;
use App\Models\PendaftaranOrganisasi;
use App\Models\PendaftaranOrganisasiFile;
use App\Models\Pengguna;
use App\Models\Universitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerifikasiBemTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    private function tokenPengguna(Pengguna $pengguna): string
    {
        return $pengguna->createToken('auth_token')->plainTextToken;
    }

    private function buatAdmin(): array
    {
        $admin = Pengguna::factory()->administrator()->create();

        return [$admin, $this->tokenPengguna($admin)];
    }

    private function buatPendaftaranBem(array $overrides = []): PendaftaranOrganisasi
    {
        Storage::fake('local');

        $pendaftar = Pengguna::factory()->akunOrganisasi()->menunggu()->create([
            'nama'           => $overrides['nama_penanggung_jawab'] ?? 'Budi Santoso',
            'username'       => 'bem_user_' . uniqid(),
            'nim'            => $overrides['nim_penanggung_jawab'] ?? '2024001',
            'nomor_whatsapp' => $overrides['nomor_whatsapp'] ?? '081234567890',
            'foto_ktm'       => 'pendaftaran/ktm/ktm.jpg',
        ]);

        Storage::disk('local')->put('pendaftaran/ktm/ktm.jpg', 'fake-ktm-content');
        Storage::disk('local')->put('pendaftaran/sk_organisasi/sk_org.pdf', 'fake-sk-org-content');
        Storage::disk('local')->put('pendaftaran/sk_kepengurusan/sk_kep.pdf', 'fake-sk-kep-content');

        $univNama = $overrides['nama_instansi'] ?? 'Universitas Contoh ' . uniqid();
        $univ = Universitas::firstOrCreate(
            ['nama_universitas' => $univNama],
            ['logo' => null]
        );

        $organisasi = Organisasi::create([
            'id_universitas'     => $univ->id_universitas,
            'id_organisasi_induk'=> null,
            'id_akun_organisasi' => $pendaftar->id_pengguna,
            'nama_organisasi'    => $overrides['nama_organisasi'] ?? ('BEM ' . $univNama),
            'jenis_organisasi'   => $overrides['jenis_organisasi'] ?? 'bem',
            'nomor_telepon'      => $overrides['nomor_telepon_organisasi'] ?? '02112345678',
            'status_organisasi'  => 'menunggu',
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

        return $pendaftaran;
    }

    // -------------------------------------------------------
    // Happy Path Tests
    // -------------------------------------------------------

    public function test_daftar_hanya_memuat_status_menunggu(): void
    {
        [$admin, $token] = $this->buatAdmin();

        // 1. Menunggu (terlama)
        $p1 = $this->buatPendaftaranBem([
            'nama_organisasi'     => 'BEM Satu',
            'tanggal_pendaftaran' => now()->subHours(2),
        ]);

        // 2. Menunggu (lebih baru)
        $p2 = $this->buatPendaftaranBem([
            'nama_organisasi'     => 'BEM Dua',
            'tanggal_pendaftaran' => now()->subHour(),
        ]);

        // 3. Disetujui (tidak boleh masuk daftar)
        $this->buatPendaftaranBem([
            'nama_organisasi'    => 'BEM Tiga',
            'status_pendaftaran' => 'disetujui',
        ]);

        // 4. Ditolak (tidak boleh masuk daftar)
        $this->buatPendaftaranBem([
            'nama_organisasi'    => 'BEM Empat',
            'status_pendaftaran' => 'ditolak',
        ]);

        $response = $this->getJson('/api/admin/registrations/bem', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daftar pendaftaran BEM berhasil diambil',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'pendaftaran' => [
                        '*' => [
                            'id_pendaftaran_organisasi',
                            'nama_organisasi',
                            'id_universitas',
                            'nama_universitas',
                            'nama_instansi',
                            'nama_penanggung_jawab',
                            'tanggal_pendaftaran',
                        ],
                    ],
                ],
            ]);

        $items = $response->json('data.pendaftaran');
        $this->assertCount(2, $items);
        $this->assertEquals($p1->id_pendaftaran_organisasi, $items[0]['id_pendaftaran_organisasi']);
        $this->assertEquals($p2->id_pendaftaran_organisasi, $items[1]['id_pendaftaran_organisasi']);
    }

    public function test_detail_memuat_data_dan_tautan_dokumen(): void
    {
        [$admin, $token] = $this->buatAdmin();
        $pendaftaran = $this->buatPendaftaranBem([
            'nama_organisasi' => 'BEM Universitas Contoh',
            'nama_instansi'   => 'Universitas Contoh',
        ]);

        $response = $this->getJson("/api/admin/registrations/bem/{$pendaftaran->id_pendaftaran_organisasi}", [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Detail pendaftaran BEM berhasil diambil',
                'data'    => [
                    'pendaftaran' => [
                        'id_pendaftaran_organisasi' => $pendaftaran->id_pendaftaran_organisasi,
                        'jenis_organisasi'          => 'bem',
                        'nama_organisasi'           => 'BEM Universitas Contoh',
                        'nama_instansi'             => 'Universitas Contoh',
                        'nomor_telepon_organisasi'  => '02112345678',
                        'status_pendaftaran'        => 'menunggu',
                        'penanggung_jawab'          => [
                            'nama'           => 'Budi Santoso',
                            'nim'            => '2024001',
                            'nomor_whatsapp' => '081234567890',
                            'username'       => $pendaftaran->organisasi->akun->username,
                        ],
                        'dokumen' => [
                            'ktm'              => "/api/admin/registrations/bem/{$pendaftaran->id_pendaftaran_organisasi}/dokumen/ktm",
                            'sk_organisasi'    => "/api/admin/registrations/bem/{$pendaftaran->id_pendaftaran_organisasi}/dokumen/sk-organisasi",
                            'sk_kepengurusan'  => "/api/admin/registrations/bem/{$pendaftaran->id_pendaftaran_organisasi}/dokumen/sk-kepengurusan",
                        ],
                    ],
                ],
            ]);

        $content = $response->getContent();
        $this->assertStringNotContainsString('kata_sandi', $content);
        $this->assertStringNotContainsString('password', strtolower($content));
        $this->assertStringNotContainsString('pendaftaran/ktm', $content);
        $this->assertStringNotContainsString('pendaftaran/sk_organisasi', $content);
        $this->assertStringNotContainsString('pendaftaran/sk_kepengurusan', $content);
    }

    public function test_setujui_membuat_organisasi_aktif_dan_pendaftaran_disetujui(): void
    {
        [$admin, $token] = $this->buatAdmin();
        $pendaftaran = $this->buatPendaftaranBem();
        $pendaftar = $pendaftaran->organisasi->akun;

        // Berikan token awal untuk mengetes pencabutan token saat disetujui
        $tokenPendaftar = $pendaftar->createToken('auth_token')->plainTextToken;
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $pendaftar->id_pengguna]);

        $response = $this->postJson("/api/admin/registrations/bem/{$pendaftaran->id_pendaftaran_organisasi}/approve", [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Registrasi BEM berhasil disetujui',
            ]);

        // 1. Organisasi aktif
        $this->assertDatabaseHas('organisasi', [
            'id_organisasi'     => $pendaftaran->id_organisasi,
            'status_organisasi' => 'aktif',
        ]);

        // 2. Pendaftaran disetujui dengan id_pengguna_verifikator dan tanggal_verifikasi
        $pendaftaranFresh = $pendaftaran->fresh();
        $this->assertEquals('disetujui', $pendaftaranFresh->status_pendaftaran);
        $this->assertEquals($admin->id_pengguna, $pendaftaranFresh->id_pengguna_verifikator);
        $this->assertNotNull($pendaftaranFresh->tanggal_verifikasi);

        // 3. Pengguna aktif
        $this->assertDatabaseHas('pengguna', [
            'id_pengguna' => $pendaftar->id_pengguna,
            'status_akun' => 'aktif',
        ]);

        // 4. Semua token pendaftar dicabut
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $pendaftar->id_pengguna]);

        // 5. Log verifikasi tercatat dengan pelaku verifikator
        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna'     => $admin->id_pengguna,
            'jenis_aktivitas' => 'verifikasi',
        ]);
    }

    public function test_setelah_disetujui_penanggung_jawab_bisa_login(): void
    {
        [$admin, $token] = $this->buatAdmin();
        $pendaftaran = $this->buatPendaftaranBem();
        $pendaftar = $pendaftaran->organisasi->akun;

        // Verifikasi pendaftaran
        $this->postJson("/api/admin/registrations/bem/{$pendaftaran->id_pendaftaran_organisasi}/approve", [], [
            'Authorization' => 'Bearer ' . $token,
        ])->assertStatus(200);

        // Pendaftar sekarang bisa login
        $loginResponse = $this->postJson('/api/auth/login', [
            'username' => $pendaftar->username,
            'password' => 'password',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login berhasil',
                'data'    => [
                    'pengguna' => [
                        'username'    => $pendaftar->username,
                        'peran'       => 'akun_organisasi',
                        'status_akun' => 'aktif',
                    ],
                ],
            ]);
    }

    public function test_tolak_menyimpan_alasan_dan_status_ditolak(): void
    {
        [$admin, $token] = $this->buatAdmin();
        $pendaftaran = $this->buatPendaftaranBem();
        $pendaftar = $pendaftaran->organisasi->akun;

        $response = $this->postJson("/api/admin/registrations/bem/{$pendaftaran->id_pendaftaran_organisasi}/reject", [
            'alasan' => 'Dokumen SK Kepengurusan tidak sah dan sudah kedaluwarsa.',
        ], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Registrasi BEM berhasil ditolak',
            ]);

        // 1. Pendaftaran terupdate dengan alasan, verifikator, & tanggal
        $pendaftaranFresh = $pendaftaran->fresh();
        $this->assertEquals('ditolak', $pendaftaranFresh->status_pendaftaran);
        $this->assertEquals('Dokumen SK Kepengurusan tidak sah dan sudah kedaluwarsa.', $pendaftaranFresh->alasan_penolakan);
        $this->assertEquals($admin->id_pengguna, $pendaftaranFresh->id_pengguna_verifikator);
        $this->assertNotNull($pendaftaranFresh->tanggal_verifikasi);

        // 2. Organisasi TETAP menunggu
        $this->assertDatabaseHas('organisasi', [
            'id_organisasi'     => $pendaftaran->id_organisasi,
            'status_organisasi' => 'menunggu',
        ]);

        // 3. Pengguna berstatus ditolak
        $this->assertDatabaseHas('pengguna', [
            'id_pengguna' => $pendaftar->id_pengguna,
            'status_akun' => 'ditolak',
        ]);

        // 4. Log verifikasi tercatat dengan pelaku verifikator
        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna'     => $admin->id_pengguna,
            'jenis_aktivitas' => 'verifikasi',
            'keterangan'      => "Menolak pendaftaran BEM {$pendaftaran->organisasi->nama_organisasi} dengan alasan: Dokumen SK Kepengurusan tidak sah dan sudah kedaluwarsa.",
        ]);
    }

    public function test_penanggung_jawab_ditolak_tidak_bisa_login(): void
    {
        [$admin, $token] = $this->buatAdmin();
        $pendaftaran = $this->buatPendaftaranBem();
        $pendaftar = $pendaftaran->organisasi->akun;

        $this->postJson("/api/admin/registrations/bem/{$pendaftaran->id_pendaftaran_organisasi}/reject", [
            'alasan' => 'Dokumen tidak valid',
        ], [
            'Authorization' => 'Bearer ' . $token,
        ])->assertStatus(200);

        $loginResponse = $this->postJson('/api/auth/login', [
            'username' => $pendaftar->username,
            'password' => 'password',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login terbatas: hanya dapat melihat status registrasi',
                'data'    => [
                    'akses_terbatas' => true,
                    'permissions'    => [],
                ],
            ]);

        $tokenTerbatas = $loginResponse->json('data.token');
        auth()->forgetGuards();

        // Token terbatas tidak dapat mengakses rute biasa
        $this->getJson('/api/auth/me', [
            'Authorization' => 'Bearer ' . $tokenTerbatas,
        ])->assertStatus(403)
          ->assertJson([
              'success' => false,
              'message' => 'Silakan login ulang',
          ]);
    }

    public function test_dokumen_dapat_diunduh_oleh_administrator(): void
    {
        [$admin, $token] = $this->buatAdmin();
        $pendaftaran = $this->buatPendaftaranBem();

        $headers = ['Authorization' => 'Bearer ' . $token];

        $resKtm = $this->get("/api/admin/registrations/bem/{$pendaftaran->id_pendaftaran_organisasi}/dokumen/ktm", $headers);
        $resKtm->assertStatus(200);

        $resSkOrg = $this->get("/api/admin/registrations/bem/{$pendaftaran->id_pendaftaran_organisasi}/dokumen/sk-organisasi", $headers);
        $resSkOrg->assertStatus(200);

        $resSkKep = $this->get("/api/admin/registrations/bem/{$pendaftaran->id_pendaftaran_organisasi}/dokumen/sk-kepengurusan", $headers);
        $resSkKep->assertStatus(200);
    }

    // -------------------------------------------------------
    // Negative Path & Security Tests
    // -------------------------------------------------------

    public function test_tanpa_token_ditolak_401(): void
    {
        $this->getJson('/api/admin/registrations/bem')
            ->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'Unauthenticated']);

        $this->getJson('/api/admin/registrations/bem/1')
            ->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'Unauthenticated']);

        $this->getJson('/api/admin/registrations/bem/1/dokumen/ktm')
            ->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'Unauthenticated']);

        $this->postJson('/api/admin/registrations/bem/1/approve')
            ->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'Unauthenticated']);

        $this->postJson('/api/admin/registrations/bem/1/reject', ['alasan' => 'x'])
            ->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'Unauthenticated']);
    }

    public function test_peran_selain_administrator_ditolak_403(): void
    {
        $peranList = ['super_admin', 'anggota', 'akun_organisasi'];

        foreach ($peranList as $peran) {
            $user = Pengguna::factory()->create(['peran' => $peran, 'status_akun' => 'aktif']);
            $token = $this->tokenPengguna($user);
            $headers = ['Authorization' => 'Bearer ' . $token];

            auth()->forgetGuards();
            $this->getJson('/api/admin/registrations/bem', $headers)
                ->assertStatus(403)
                ->assertJson(['success' => false, 'message' => 'Akses ditolak']);

            auth()->forgetGuards();
            $this->getJson('/api/admin/registrations/bem/1', $headers)
                ->assertStatus(403)
                ->assertJson(['success' => false, 'message' => 'Akses ditolak']);

            auth()->forgetGuards();
            $this->getJson('/api/admin/registrations/bem/1/dokumen/ktm', $headers)
                ->assertStatus(403)
                ->assertJson(['success' => false, 'message' => 'Akses ditolak']);

            auth()->forgetGuards();
            $this->postJson('/api/admin/registrations/bem/1/approve', [], $headers)
                ->assertStatus(403)
                ->assertJson(['success' => false, 'message' => 'Akses ditolak']);

            auth()->forgetGuards();
            $this->postJson('/api/admin/registrations/bem/1/reject', ['alasan' => 'x'], $headers)
                ->assertStatus(403)
                ->assertJson(['success' => false, 'message' => 'Akses ditolak']);
        }
    }

    public function test_id_tidak_ada_mengembalikan_404_dengan_success_false(): void
    {
        [$admin, $token] = $this->buatAdmin();
        $headers = ['Authorization' => 'Bearer ' . $token];

        $r1 = $this->getJson('/api/admin/registrations/bem/99999', $headers);
        $r1->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Pendaftaran BEM tidak ditemukan',
            ]);

        $r2 = $this->getJson('/api/admin/registrations/bem/99999/dokumen/ktm', $headers);
        $r2->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Pendaftaran BEM tidak ditemukan',
            ]);

        $r3 = $this->postJson('/api/admin/registrations/bem/99999/approve', [], $headers);
        $r3->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Pendaftaran BEM tidak ditemukan',
            ]);

        $r4 = $this->postJson('/api/admin/registrations/bem/99999/reject', ['alasan' => 'Alasan'], $headers);
        $r4->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Pendaftaran BEM tidak ditemukan',
            ]);
    }

    public function test_bukan_jenis_bem_mengembalikan_404_dengan_success_false(): void
    {
        [$admin, $token] = $this->buatAdmin();
        $headers = ['Authorization' => 'Bearer ' . $token];

        $pendaftaranHmj = $this->buatPendaftaranBem([
            'jenis_organisasi' => 'hmj',
        ]);

        $id = $pendaftaranHmj->id_pendaftaran_organisasi;

        $this->getJson("/api/admin/registrations/bem/{$id}", $headers)
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Pendaftaran BEM tidak ditemukan',
            ]);

        $this->getJson("/api/admin/registrations/bem/{$id}/dokumen/ktm", $headers)
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Pendaftaran BEM tidak ditemukan',
            ]);

        $this->postJson("/api/admin/registrations/bem/{$id}/approve", [], $headers)
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Pendaftaran BEM tidak ditemukan',
            ]);

        $this->postJson("/api/admin/registrations/bem/{$id}/reject", ['alasan' => 'x'], $headers)
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Pendaftaran BEM tidak ditemukan',
            ]);
    }

    public function test_jenis_dokumen_tidak_valid_mengembalikan_404_dengan_success_false(): void
    {
        [$admin, $token] = $this->buatAdmin();
        $pendaftaran = $this->buatPendaftaranBem();

        $response = $this->getJson("/api/admin/registrations/bem/{$pendaftaran->id_pendaftaran_organisasi}/dokumen/ijazah", [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Jenis dokumen tidak valid',
            ]);
    }

    public function test_keputusan_kedua_pada_pendaftaran_yang_sama_mengembalikan_409_dengan_success_false(): void
    {
        [$admin, $token] = $this->buatAdmin();
        $headers = ['Authorization' => 'Bearer ' . $token];

        // 1. Keputusan kedua approve setelah approve → 409
        $p1 = $this->buatPendaftaranBem();
        $this->postJson("/api/admin/registrations/bem/{$p1->id_pendaftaran_organisasi}/approve", [], $headers)
            ->assertStatus(200);

        $resApproveUlang = $this->postJson("/api/admin/registrations/bem/{$p1->id_pendaftaran_organisasi}/approve", [], $headers);
        $resApproveUlang->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Pendaftaran sudah diputuskan',
            ]);

        // 2. Reject setelah approve → 409
        $resRejectSetelahApprove = $this->postJson("/api/admin/registrations/bem/{$p1->id_pendaftaran_organisasi}/reject", [
            'alasan' => 'Batal',
        ], $headers);
        $resRejectSetelahApprove->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Pendaftaran sudah diputuskan',
            ]);

        // 3. Approve setelah reject → 409
        $p2 = $this->buatPendaftaranBem();
        $this->postJson("/api/admin/registrations/bem/{$p2->id_pendaftaran_organisasi}/reject", [
            'alasan' => 'Ditolak',
        ], $headers)->assertStatus(200);

        $resApproveSetelahReject = $this->postJson("/api/admin/registrations/bem/{$p2->id_pendaftaran_organisasi}/approve", [], $headers);
        $resApproveSetelahReject->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Pendaftaran sudah diputuskan',
            ]);
    }

    public function test_validasi_alasan_penolakan_gagal_422(): void
    {
        [$admin, $token] = $this->buatAdmin();
        $pendaftaran = $this->buatPendaftaranBem();
        $headers = ['Authorization' => 'Bearer ' . $token];

        // Alasan kosong
        $r1 = $this->postJson("/api/admin/registrations/bem/{$pendaftaran->id_pendaftaran_organisasi}/reject", [], $headers);
        $r1->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid',
            ])
            ->assertJsonValidationErrors(['alasan']);

        // Alasan lebih dari 1000 karakter
        $r2 = $this->postJson("/api/admin/registrations/bem/{$pendaftaran->id_pendaftaran_organisasi}/reject", [
            'alasan' => str_repeat('a', 1001),
        ], $headers);
        $r2->assertStatus(422)
            ->assertJsonValidationErrors(['alasan']);
    }

    public function test_kegagalan_di_tengah_proses_approve_rollback(): void
    {
        [$admin, $token] = $this->buatAdmin();
        $pendaftaran = $this->buatPendaftaranBem();
        $pendaftar = $pendaftaran->organisasi->akun;
        $organisasi = $pendaftaran->organisasi;

        // Pasang event listener creating pada LogAktivitas yang melempar exception
        LogAktivitas::creating(function () {
            throw new \RuntimeException('Simulasi kegagalan di tengah proses approve');
        });

        $service = app(\App\Services\VerifikasiBemService::class);

        try {
            $service->setujui($pendaftaran->id_pendaftaran_organisasi, $admin->id_pengguna);
            $this->fail('Harusnya melempar exception');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Simulasi kegagalan di tengah proses approve', $e->getMessage());
        } finally {
            LogAktivitas::flushEventListeners();
        }

        // Organisasi tetap menunggu
        $this->assertEquals('menunggu', $organisasi->fresh()->status_organisasi);

        // Status pendaftaran tetap menunggu
        $this->assertEquals('menunggu', $pendaftaran->fresh()->status_pendaftaran);
        $this->assertNull($pendaftaran->fresh()->tanggal_verifikasi);

        // Status akun pengguna tetap menunggu
        $this->assertEquals('menunggu', $pendaftar->fresh()->status_akun);

        // Tidak ada log aktivitas verifikasi tersimpan
        $this->assertDatabaseMissing('log_aktivitas', [
            'jenis_aktivitas' => 'verifikasi',
        ]);
    }

    public function test_kegagalan_di_tengah_proses_tolak_rollback(): void
    {
        [$admin, $token] = $this->buatAdmin();
        $pendaftaran = $this->buatPendaftaranBem();
        $pendaftar = $pendaftaran->organisasi->akun;
        $organisasi = $pendaftaran->organisasi;

        // Pasang event listener creating pada LogAktivitas yang melempar exception
        LogAktivitas::creating(function () {
            throw new \RuntimeException('Simulasi kegagalan di tengah proses tolak');
        });

        $service = app(\App\Services\VerifikasiBemService::class);

        try {
            $service->tolak($pendaftaran->id_pendaftaran_organisasi, 'Alasan ditolak', $admin->id_pengguna);
            $this->fail('Harusnya melempar exception');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Simulasi kegagalan di tengah proses tolak', $e->getMessage());
        } finally {
            LogAktivitas::flushEventListeners();
        }

        // Status pendaftaran tetap menunggu
        $this->assertEquals('menunggu', $pendaftaran->fresh()->status_pendaftaran);
        $this->assertNull($pendaftaran->fresh()->alasan_penolakan);

        // Status akun pengguna tetap menunggu
        $this->assertEquals('menunggu', $pendaftar->fresh()->status_akun);

        // Organisasi tetap menunggu
        $this->assertEquals('menunggu', $organisasi->fresh()->status_organisasi);

        // Tidak ada log aktivitas verifikasi tersimpan
        $this->assertDatabaseMissing('log_aktivitas', [
            'jenis_aktivitas' => 'verifikasi',
        ]);
    }
}
