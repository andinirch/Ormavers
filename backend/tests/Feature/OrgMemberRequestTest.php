<?php

namespace Tests\Feature;

use App\Models\Keanggotaan;
use App\Models\LogAktivitas;
use App\Models\Organisasi;
use App\Models\Pengguna;
use App\Models\Universitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrgMemberRequestTest extends TestCase
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
    }

    private function buatOrganisasi(string $jenis = 'bem', string $status = 'aktif'): array
    {
        $univ = Universitas::firstOrCreate(['nama_universitas' => 'Univ ' . uniqid()]);

        $user = Pengguna::factory()->akunOrganisasi()->create([
            'status_akun' => ($status === 'aktif' ? 'aktif' : 'menunggu'),
        ]);

        $org = Organisasi::create([
            'id_universitas'     => $univ->id_universitas,
            'id_organisasi_induk'=> null,
            'id_akun_organisasi' => $user->id_pengguna,
            'nama_organisasi'    => strtoupper($jenis) . ' ' . uniqid(),
            'jenis_organisasi'   => $jenis,
            'nomor_telepon'      => '08123456789',
            'status_organisasi'  => $status,
        ]);

        $roleName = ($jenis === 'bem') ? 'akun_bem' : 'akun_hmj_ukm';
        $user->syncRoles($roleName);

        $token = $user->createToken('auth_token', ['*'])->plainTextToken;

        return [$user, $org, $token];
    }

    private function buatAnggota(Organisasi $org, array $attrs = []): array
    {
        Storage::fake('local');
        $pathKtm = 'pendaftaran/ktm/' . uniqid() . '.jpg';
        Storage::disk('local')->put($pathKtm, 'fake-ktm-binary');

        $user = Pengguna::factory()->menunggu()->create([
            'peran'    => 'anggota',
            'foto_ktm' => $pathKtm,
            'nama'     => $attrs['nama'] ?? 'Calon Anggota',
            'nim'      => $attrs['nim'] ?? 'NIM' . rand(1000, 9999),
        ]);
        $user->syncRoles('anggota');

        $keanggotaan = Keanggotaan::create([
            'id_pengguna'         => $user->id_pengguna,
            'id_organisasi'       => $org->id_organisasi,
            'status_keanggotaan'  => $attrs['status_keanggotaan'] ?? 'menunggu',
            'alasan_penolakan'    => $attrs['alasan_penolakan'] ?? null,
            'tanggal_pendaftaran' => $attrs['tanggal_pendaftaran'] ?? now(),
        ]);

        return [$user, $keanggotaan, $pathKtm];
    }

    public function test_daftar_member_requests_berhasil_untuk_bem_dan_hmj_ukm()
    {
        [$bemUser, $bemOrg, $bemToken] = $this->buatOrganisasi('bem', 'aktif');
        [$hmjUser, $hmjOrg, $hmjToken] = $this->buatOrganisasi('hmj', 'aktif');

        // 2 anggota di BEM, 1 di HMJ
        $this->buatAnggota($bemOrg, ['nama' => 'Anggota BEM 1', 'tanggal_pendaftaran' => now()->subDay()]);
        $this->buatAnggota($bemOrg, ['nama' => 'Anggota BEM 2', 'tanggal_pendaftaran' => now()]);
        $this->buatAnggota($hmjOrg, ['nama' => 'Anggota HMJ 1']);

        // BEM cek
        $responseBem = $this->withToken($bemToken)
            ->getJson('/api/org/member-requests');

        $responseBem->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.meta.total', 2)
            ->assertJsonPath('data.requests.0.nama', 'Anggota BEM 1')
            ->assertJsonPath('data.requests.1.nama', 'Anggota BEM 2');

        $this->app['auth']->forgetGuards();

        // HMJ cek
        $responseHmj = $this->withToken($hmjToken)
            ->getJson('/api/org/member-requests');

        $responseHmj->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.requests.0.nama', 'Anggota HMJ 1');

        $responseHmj->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.requests.0.nama', 'Anggota HMJ 1');
    }

    public function test_paginasi_daftar_member_requests()
    {
        [$bemUser, $bemOrg, $bemToken] = $this->buatOrganisasi('bem', 'aktif');

        for ($i = 1; $i <= 15; $i++) {
            $this->buatAnggota($bemOrg, [
                'nama'                => "Anggota {$i}",
                'tanggal_pendaftaran' => now()->subMinutes(20 - $i),
            ]);
        }

        $res = $this->withHeader('Authorization', "Bearer {$bemToken}")
            ->getJson('/api/org/member-requests?per_page=5&page=2');

        $res->assertStatus(200)
            ->assertJsonPath('data.meta.current_page', 2)
            ->assertJsonPath('data.meta.per_page', 5)
            ->assertJsonPath('data.meta.total', 15)
            ->assertJsonPath('data.meta.last_page', 3);
        $this->assertCount(5, $res->json('data.requests'));
    }

    public function test_detail_member_request_berhasil_dan_tanpa_bocor_sandi_atau_path()
    {
        [$bemUser, $bemOrg, $bemToken] = $this->buatOrganisasi('bem', 'aktif');
        [$anggota, $keanggotaan] = $this->buatAnggota($bemOrg, ['nama' => 'John Doe']);

        $res = $this->withHeader('Authorization', "Bearer {$bemToken}")
            ->getJson("/api/org/member-requests/{$keanggotaan->id_keanggotaan}");

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id_keanggotaan', $keanggotaan->id_keanggotaan)
            ->assertJsonPath('data.nama', 'John Doe')
            ->assertJsonPath('data.dokumen.ktm', "/api/org/member-requests/{$keanggotaan->id_keanggotaan}/dokumen/ktm");

        // Pastikan tidak ada kata_sandi dan path file fisik di respons
        $this->assertArrayNotHasKey('kata_sandi', $res->json('data'));
        $this->assertStringNotContainsString('pendaftaran/ktm', json_encode($res->json('data')));
    }

    public function test_unduh_dokumen_ktm_berhasil()
    {
        [$bemUser, $bemOrg, $bemToken] = $this->buatOrganisasi('bem', 'aktif');
        [$anggota, $keanggotaan] = $this->buatAnggota($bemOrg);

        $res = $this->withHeader('Authorization', "Bearer {$bemToken}")
            ->get("/api/org/member-requests/{$keanggotaan->id_keanggotaan}/dokumen/ktm");

        $res->assertStatus(200);
    }

    public function test_unduh_dokumen_ktm_berkas_tidak_ada_404()
    {
        [$bemUser, $bemOrg, $bemToken] = $this->buatOrganisasi('bem', 'aktif');
        [$anggota, $keanggotaan, $pathKtm] = $this->buatAnggota($bemOrg);

        Storage::disk('local')->delete($pathKtm);

        $res = $this->withHeader('Authorization', "Bearer {$bemToken}")
            ->getJson("/api/org/member-requests/{$keanggotaan->id_keanggotaan}/dokumen/ktm");

        $res->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'File dokumen tidak ditemukan');
    }

    public function test_setujui_keanggotaan_berhasil()
    {
        [$bemUser, $bemOrg, $bemToken] = $this->buatOrganisasi('bem', 'aktif');
        [$anggota, $keanggotaan] = $this->buatAnggota($bemOrg, ['nama' => 'Budi Santoso']);

        // Beri token terbatas pada anggota
        $anggotaToken = $anggota->createToken('auth_token', ['status-registrasi'])->plainTextToken;
        $this->assertCount(1, $anggota->tokens);

        $res = $this->withHeader('Authorization', "Bearer {$bemToken}")
            ->postJson("/api/org/member-requests/{$keanggotaan->id_keanggotaan}/approve");

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Keanggotaan berhasil disetujui');

        // Cek DB
        $keanggotaan->refresh();
        $anggota->refresh();

        $this->assertEquals('disetujui', $keanggotaan->status_keanggotaan);
        $this->assertEquals($bemUser->id_pengguna, $keanggotaan->id_pengguna_verifikator);
        $this->assertNotNull($keanggotaan->tanggal_verifikasi);
        $this->assertEquals('aktif', $anggota->status_akun);

        // Token anggota harus dicabut
        $this->assertCount(0, $anggota->tokens()->get());

        // Login anggota sekarang harus penuh
        $loginRes = $this->postJson('/api/auth/login', [
            'username' => $anggota->username,
            'password' => 'password',
        ]);
        $loginRes->assertStatus(200)
            ->assertJsonMissing(['akses_terbatas' => true]);

        // Cek log aktivitas
        $log = LogAktivitas::where('jenis_aktivitas', 'verifikasi')
            ->where('id_pengguna', $bemUser->id_pengguna)
            ->latest('id_log')
            ->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString("Menyetujui keanggotaan Budi Santoso di {$bemOrg->nama_organisasi}", $log->keterangan);
    }

    public function test_tolak_keanggotaan_berhasil()
    {
        [$bemUser, $bemOrg, $bemToken] = $this->buatOrganisasi('bem', 'aktif');
        [$anggota, $keanggotaan] = $this->buatAnggota($bemOrg, ['nama' => 'Citra']);

        $res = $this->withHeader('Authorization', "Bearer {$bemToken}")
            ->postJson("/api/org/member-requests/{$keanggotaan->id_keanggotaan}/reject", [
                'alasan' => 'Foto KTM buram dan tidak terbaca',
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Keanggotaan berhasil ditolak');

        $keanggotaan->refresh();
        $anggota->refresh();

        $this->assertEquals('ditolak', $keanggotaan->status_keanggotaan);
        $this->assertEquals('Foto KTM buram dan tidak terbaca', $keanggotaan->alasan_penolakan);
        $this->assertEquals('ditolak', $anggota->status_akun);

        // Anggota cek status registrasi
        $this->app['auth']->forgetGuards();
        $tokenTerbatas = $anggota->createToken('auth_token', ['status-registrasi'])->plainTextToken;
        $statusRes = $this->withHeader('Authorization', "Bearer {$tokenTerbatas}")
            ->getJson('/api/registration/status');

        $statusRes->assertStatus(200)
            ->assertJsonPath('data.status_pendaftaran', 'ditolak')
            ->assertJsonPath('data.alasan_penolakan', 'Foto KTM buram dan tidak terbaca');

        // Cek log aktivitas
        $log = LogAktivitas::where('jenis_aktivitas', 'verifikasi')
            ->where('id_pengguna', $bemUser->id_pengguna)
            ->latest('id_log')
            ->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Foto KTM buram dan tidak terbaca', $log->keterangan);
    }

    public function test_tanpa_token_401()
    {
        $res = $this->getJson('/api/org/member-requests');
        $res->assertStatus(401);
    }

    public function test_peran_lain_ditolak_403()
    {
        // Super admin
        $sa = Pengguna::factory()->superAdmin()->create(['status_akun' => 'aktif']);
        $sa->syncRoles('super_admin');
        $tokenSa = $sa->createToken('auth_token', ['*'])->plainTextToken;

        $res = $this->withHeader('Authorization', "Bearer {$tokenSa}")
            ->getJson('/api/org/member-requests');
        $res->assertStatus(403);

        // Anggota
        $anggota = Pengguna::factory()->create(['peran' => 'anggota', 'status_akun' => 'aktif']);
        $anggota->syncRoles('anggota');
        $tokenAnggota = $anggota->createToken('auth_token', ['*'])->plainTextToken;

        $res = $this->withHeader('Authorization', "Bearer {$tokenAnggota}")
            ->getJson('/api/org/member-requests');
        $res->assertStatus(403);
    }

    public function test_keanggotaan_milik_organisasi_lain_403()
    {
        [$bem1, $org1, $token1] = $this->buatOrganisasi('bem', 'aktif');
        [$bem2, $org2, $token2] = $this->buatOrganisasi('bem', 'aktif');

        [$anggota, $keanggotaan] = $this->buatAnggota($org1);

        // BEM 2 mencoba melihat detail
        $res = $this->withHeader('Authorization', "Bearer {$token2}")
            ->getJson("/api/org/member-requests/{$keanggotaan->id_keanggotaan}");

        $res->assertStatus(403)
            ->assertJsonPath('message', 'Permintaan ini bukan milik organisasi Anda');

        // BEM 2 mencoba approve
        $resApprove = $this->withHeader('Authorization', "Bearer {$token2}")
            ->postJson("/api/org/member-requests/{$keanggotaan->id_keanggotaan}/approve");

        $resApprove->assertStatus(403)
            ->assertJsonPath('message', 'Permintaan ini bukan milik organisasi Anda');
    }

    public function test_akun_organisasi_belum_aktif_403()
    {
        [$user, $org, $token] = $this->buatOrganisasi('bem', 'menunggu');
        // Force token *
        $user->update(['status_akun' => 'aktif']); // tokenCan(*) lolos akun.aktif, tapi status_organisasi masih menunggu

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/org/member-requests');

        $res->assertStatus(403)
            ->assertJsonPath('message', 'Akun organisasi belum aktif');
    }

    public function test_id_tidak_ditemukan_atau_bukan_angka_404()
    {
        [$user, $org, $token] = $this->buatOrganisasi('bem', 'aktif');

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/org/member-requests/99999');
        $res->assertStatus(404);

        $resString = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/org/member-requests/abc');
        $resString->assertStatus(404);
    }

    public function test_keputusan_kedua_mengembalikan_409()
    {
        [$user, $org, $token] = $this->buatOrganisasi('bem', 'aktif');
        [$anggota, $keanggotaan] = $this->buatAnggota($org, ['status_keanggotaan' => 'disetujui']);

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/org/member-requests/{$keanggotaan->id_keanggotaan}/approve");

        $res->assertStatus(409)
            ->assertJsonPath('message', 'Permintaan sudah diputuskan');
    }

    public function test_alasan_penolakan_kosong_atau_terlalu_panjang_422()
    {
        [$user, $org, $token] = $this->buatOrganisasi('bem', 'aktif');
        [$anggota, $keanggotaan] = $this->buatAnggota($org);

        // Kosong
        $res1 = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/org/member-requests/{$keanggotaan->id_keanggotaan}/reject", [
                'alasan' => '',
            ]);
        $res1->assertStatus(422)
            ->assertJsonPath('success', false);

        // Lebih dari 1000 karakter
        $res2 = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/org/member-requests/{$keanggotaan->id_keanggotaan}/reject", [
                'alasan' => str_repeat('a', 1001),
            ]);
        $res2->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
