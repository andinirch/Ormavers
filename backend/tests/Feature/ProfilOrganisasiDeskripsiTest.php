<?php

namespace Tests\Feature;

use App\Models\Organisasi;
use App\Models\Pengguna;
use App\Models\Universitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfilOrganisasiDeskripsiTest extends TestCase
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

    private function buatAkunOrganisasi(string $jenis = 'bem', string $statusAkun = 'aktif', string $statusOrg = 'aktif'): array
    {
        $univ = Universitas::firstOrCreate(['nama_universitas' => 'Universitas ' . uniqid()]);

        $user = Pengguna::factory()->akunOrganisasi()->create([
            'status_akun' => $statusAkun,
        ]);

        $org = Organisasi::create([
            'id_universitas'     => $univ->id_universitas,
            'id_organisasi_induk'=> null,
            'id_akun_organisasi' => $user->id_pengguna,
            'nama_organisasi'    => strtoupper($jenis) . ' ' . uniqid(),
            'jenis_organisasi'   => $jenis,
            'nomor_telepon'      => '081234567890',
            'deskripsi'          => 'Deskripsi awal ' . $jenis,
            'status_organisasi'  => $statusOrg,
        ]);

        $roleName = ($jenis === 'bem') ? 'akun_bem' : 'akun_hmj_ukm';
        $user->syncRoles($roleName);

        $abilities = ($statusAkun === 'aktif') ? ['*'] : ['status-registrasi'];
        $token = $user->createToken('auth_token', $abilities)->plainTextToken;

        return [$user, $org, $token];
    }

    public function test_get_deskripsi_organisasi_berhasil(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/organisasi/deskripsi');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Deskripsi organisasi berhasil diambil',
                'data'    => [
                    'id_organisasi'     => $org->id_organisasi,
                    'nama_organisasi'   => $org->nama_organisasi,
                    'jenis_organisasi'  => 'bem',
                    'deskripsi'         => 'Deskripsi awal bem',
                    'status_organisasi' => 'aktif',
                ],
            ]);
    }

    public function test_put_ubah_deskripsi_organisasi_berhasil(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');

        $newDeskripsi = 'Ini adalah deskripsi organisasi BEM yang baru diperbarui.';

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/organisasi/deskripsi', [
                'deskripsi' => $newDeskripsi,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Deskripsi organisasi berhasil diperbarui',
                'data'    => [
                    'id_organisasi'     => $org->id_organisasi,
                    'nama_organisasi'   => $org->nama_organisasi,
                    'jenis_organisasi'  => 'bem',
                    'deskripsi'         => $newDeskripsi,
                    'status_organisasi' => 'aktif',
                ],
            ]);

        $this->assertDatabaseHas('organisasi', [
            'id_organisasi' => $org->id_organisasi,
            'deskripsi'     => $newDeskripsi,
        ]);
    }

    public function test_patch_ubah_deskripsi_organisasi_berhasil(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('hmj', 'aktif', 'aktif');

        $newDeskripsi = 'Deskripsi HMJ yang baru diubah via PATCH.';

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/organisasi/deskripsi', [
                'deskripsi' => $newDeskripsi,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Deskripsi organisasi berhasil diperbarui',
                'data'    => [
                    'id_organisasi'     => $org->id_organisasi,
                    'deskripsi'         => $newDeskripsi,
                    'status_organisasi' => 'aktif',
                ],
            ]);

        $this->assertDatabaseHas('organisasi', [
            'id_organisasi' => $org->id_organisasi,
            'deskripsi'     => $newDeskripsi,
        ]);
    }

    public function test_ubah_deskripsi_gagal_jika_deskripsi_kosong(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/organisasi/deskripsi', [
                'deskripsi' => '',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid',
            ])
            ->assertJsonValidationErrors(['deskripsi']);

        // Data tidak berubah
        $this->assertDatabaseHas('organisasi', [
            'id_organisasi' => $org->id_organisasi,
            'deskripsi'     => 'Deskripsi awal bem',
        ]);
    }

    public function test_ubah_deskripsi_gagal_jika_field_deskripsi_hilang(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/organisasi/deskripsi', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid',
            ])
            ->assertJsonValidationErrors(['deskripsi']);
    }

    public function test_ubah_deskripsi_gagal_jika_deskripsi_bukan_string(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/organisasi/deskripsi', [
                'deskripsi' => ['bukan', 'string'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['deskripsi']);
    }

    public function test_akses_ditolak_jika_tanpa_token(): void
    {
        $response = $this->getJson('/api/organisasi/deskripsi');
        $response->assertStatus(401);

        $responsePut = $this->putJson('/api/organisasi/deskripsi', [
            'deskripsi' => 'Testing unauthorized',
        ]);
        $responsePut->assertStatus(401);
    }

    public function test_akses_ditolak_jika_peran_bukan_akun_organisasi(): void
    {
        // Anggota
        $anggota = Pengguna::factory()->create([
            'peran'       => 'anggota',
            'status_akun' => 'aktif',
        ]);
        $tokenAnggota = $anggota->createToken('auth_token', ['*'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenAnggota)
            ->getJson('/api/organisasi/deskripsi');
        $response->assertStatus(403);

        $responsePut = $this->withHeader('Authorization', 'Bearer ' . $tokenAnggota)
            ->putJson('/api/organisasi/deskripsi', ['deskripsi' => 'Test']);
        $responsePut->assertStatus(403);

        // Administrator
        $admin = Pengguna::factory()->administrator()->create([
            'status_akun' => 'aktif',
        ]);
        $tokenAdmin = $admin->createToken('auth_token', ['*'])->plainTextToken;

        $resAdmin = $this->withHeader('Authorization', 'Bearer ' . $tokenAdmin)
            ->getJson('/api/organisasi/deskripsi');
        $resAdmin->assertStatus(403);
    }

    public function test_akses_ditolak_jika_akun_belum_verifikasi(): void
    {
        // Akun organisasi yang status akunnya 'menunggu'
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'menunggu', 'menunggu');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/organisasi/deskripsi');

        $response->assertStatus(403);

        $responsePut = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/organisasi/deskripsi', [
                'deskripsi' => 'Coba ubah saat belum verifikasi',
            ]);

        $responsePut->assertStatus(403);
    }

    public function test_akses_ditolak_jika_organisasi_belum_aktif(): void
    {
        // Akun aktif tapi status organisasinya masih 'menunggu'
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'menunggu');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/organisasi/deskripsi');

        $response->assertStatus(403);

        $responsePut = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/organisasi/deskripsi', [
                'deskripsi' => 'Coba ubah saat organisasi belum aktif',
            ]);

        $responsePut->assertStatus(403);
    }

    public function test_perubahan_deskripsi_tidak_mempengaruhi_organisasi_lain(): void
    {
        [$user1, $org1, $token1] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');
        [$user2, $org2, $token2] = $this->buatAkunOrganisasi('hmj', 'aktif', 'aktif');

        $deskripsiOrg2Sebelumnya = $org2->deskripsi;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->putJson('/api/organisasi/deskripsi', [
                'deskripsi' => 'Deskripsi BEM telah diperbarui',
            ]);

        $response->assertStatus(200);

        // Pastikan organisasi 1 berubah
        $this->assertDatabaseHas('organisasi', [
            'id_organisasi' => $org1->id_organisasi,
            'deskripsi'     => 'Deskripsi BEM telah diperbarui',
        ]);

        // Pastikan organisasi 2 tetap sama tidak berubah
        $this->assertDatabaseHas('organisasi', [
            'id_organisasi' => $org2->id_organisasi,
            'deskripsi'     => $deskripsiOrg2Sebelumnya,
        ]);
    }
}
