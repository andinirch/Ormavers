<?php

namespace Tests\Feature;

use App\Models\BidangOrganisasi;
use App\Models\Organisasi;
use App\Models\Pengguna;
use App\Models\Universitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IdentitasOrganisasiTest extends TestCase
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

        Storage::fake('public');
    }

    private function buatAkunOrganisasi(
        string $jenis = 'bem',
        string $statusAkun = 'aktif',
        string $statusOrg = 'aktif'
    ): array {
        $univ = Universitas::firstOrCreate(['nama_universitas' => 'Universitas ' . uniqid()]);
        $bidang = BidangOrganisasi::firstOrCreate(['nama_bidang' => 'Bidang ' . uniqid()]);

        $user = Pengguna::factory()->akunOrganisasi()->create([
            'status_akun' => $statusAkun,
        ]);

        $org = Organisasi::create([
            'id_universitas'      => $univ->id_universitas,
            'id_organisasi_induk' => null,
            'id_akun_organisasi'  => $user->id_pengguna,
            'id_bidang'           => $bidang->id_bidang,
            'nama_organisasi'     => strtoupper($jenis) . ' ' . uniqid(),
            'jenis_organisasi'    => $jenis,
            'nomor_telepon'       => '081234567890',
            'deskripsi'           => 'Deskripsi ' . $jenis,
            'alamat'              => 'Jl. Kampus No. 1',
            'tanggal_mulai_sk'    => '2025-01-01',
            'tanggal_berakhir_sk' => '2025-12-31',
            'status_organisasi'   => $statusOrg,
        ]);

        $roleName = ($jenis === 'bem') ? 'akun_bem' : 'akun_hmj_ukm';
        $user->syncRoles($roleName);

        $abilities = ($statusAkun === 'aktif') ? ['*'] : ['status-registrasi'];
        $token = $user->createToken('auth_token', $abilities)->plainTextToken;

        return [$user, $org, $token, $univ, $bidang];
    }

    public function test_get_identitas_organisasi_berhasil(): void
    {
        [$user, $org, $token, $univ, $bidang] = $this->buatAkunOrganisasi('bem');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/organisasi/identitas');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Identitas organisasi berhasil diambil',
                'data'    => [
                    'id_organisasi'       => $org->id_organisasi,
                    'nama_organisasi'     => $org->nama_organisasi,
                    'jenis_organisasi'    => 'bem',
                    'id_bidang'           => $bidang->id_bidang,
                    'alamat'              => 'Jl. Kampus No. 1',
                    'tanggal_mulai_sk'    => '2025-01-01',
                    'tanggal_berakhir_sk' => '2025-12-31',
                    'status_organisasi'   => 'aktif',
                ],
            ]);
    }

    public function test_update_identitas_organisasi_tanpa_file(): void
    {
        [$user, $org, $token, $univ, $bidangLama] = $this->buatAkunOrganisasi('hmj');
        $bidangBaru = BidangOrganisasi::firstOrCreate(['nama_bidang' => 'Bidang Baru ' . uniqid()]);

        $payload = [
            'id_bidang'           => $bidangBaru->id_bidang,
            'alamat'              => 'Jl. Baru No. 99',
            'tanggal_mulai_sk'    => '2026-01-01',
            'tanggal_berakhir_sk' => '2026-12-31',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/identitas', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Identitas organisasi berhasil diperbarui',
                'data'    => [
                    'id_organisasi'       => $org->id_organisasi,
                    'id_bidang'           => $bidangBaru->id_bidang,
                    'alamat'              => 'Jl. Baru No. 99',
                    'tanggal_mulai_sk'    => '2026-01-01',
                    'tanggal_berakhir_sk' => '2026-12-31',
                ],
            ]);

        $this->assertDatabaseHas('organisasi', [
            'id_organisasi'       => $org->id_organisasi,
            'id_bidang'           => $bidangBaru->id_bidang,
            'alamat'              => 'Jl. Baru No. 99',
            'tanggal_mulai_sk'    => '2026-01-01 00:00:00',
            'tanggal_berakhir_sk' => '2026-12-31 00:00:00',
        ]);
    }

    public function test_update_identitas_dengan_logo_organisasi(): void
    {
        [$user, $org, $token, $univ, $bidang] = $this->buatAkunOrganisasi('ukm');

        $fileLogo = UploadedFile::fake()->image('logo_ukm.png', 200, 200);

        $payload = [
            'id_bidang'           => $bidang->id_bidang,
            'alamat'              => 'Jl. UKM No. 10',
            'tanggal_mulai_sk'    => '2025-06-01',
            'tanggal_berakhir_sk' => '2026-06-01',
            'logo'                => $fileLogo,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->post('/api/organisasi/identitas', $payload, ['Accept' => 'application/json']);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Identitas organisasi berhasil diperbarui',
            ]);

        $org->refresh();
        $this->assertNotNull($org->logo);
        Storage::disk('public')->assertExists($org->logo);
    }

    public function test_update_identitas_oleh_bem_dapat_mengubah_logo_universitas(): void
    {
        [$user, $org, $token, $univ, $bidang] = $this->buatAkunOrganisasi('bem');

        $fileLogoUniv = UploadedFile::fake()->image('logo_kampus.jpg', 300, 300);

        $payload = [
            'id_bidang'           => $bidang->id_bidang,
            'alamat'              => 'Gedung BEM Lt. 2',
            'tanggal_mulai_sk'    => '2025-01-01',
            'tanggal_berakhir_sk' => '2025-12-31',
            'logo_universitas'    => $fileLogoUniv,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->post('/api/organisasi/identitas', $payload, ['Accept' => 'application/json']);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Identitas organisasi berhasil diperbarui',
            ]);

        $univ->refresh();
        $this->assertNotNull($univ->logo);
        Storage::disk('public')->assertExists($univ->logo);
    }

    public function test_update_identitas_oleh_non_bem_mengabaikan_logo_universitas(): void
    {
        [$user, $org, $token, $univ, $bidang] = $this->buatAkunOrganisasi('hmj');
        $univ->update(['logo' => null]);

        $fileLogoUniv = UploadedFile::fake()->image('logo_fake_univ.jpg', 300, 300);

        $payload = [
            'id_bidang'           => $bidang->id_bidang,
            'alamat'              => 'Sekretariat HMJ',
            'tanggal_mulai_sk'    => '2025-01-01',
            'tanggal_berakhir_sk' => '2025-12-31',
            'logo_universitas'    => $fileLogoUniv,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->post('/api/organisasi/identitas', $payload, ['Accept' => 'application/json']);

        $response->assertStatus(200);

        $univ->refresh();
        $this->assertNull($univ->logo);
    }

    public function test_validasi_tanggal_sk_berakhir_sebelum_mulai(): void
    {
        [$user, $org, $token, $univ, $bidang] = $this->buatAkunOrganisasi('bem');

        $payload = [
            'id_bidang'           => $bidang->id_bidang,
            'alamat'              => 'Gedung BEM',
            'tanggal_mulai_sk'    => '2025-12-31',
            'tanggal_berakhir_sk' => '2025-01-01', // Lebih awal dari tanggal_mulai_sk
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/identitas', $payload);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['tanggal_berakhir_sk'],
            ]);
    }

    public function test_validasi_bidang_tidak_ditemukan(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem');

        $payload = [
            'id_bidang'           => 999999, // tidak ada di tabel bidang_organisasi
            'alamat'              => 'Alamat tes',
            'tanggal_mulai_sk'    => '2025-01-01',
            'tanggal_berakhir_sk' => '2025-12-31',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/identitas', $payload);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['id_bidang'],
            ]);
    }
}
