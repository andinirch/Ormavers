<?php

namespace Tests\Feature;

use App\Models\KontakOrganisasi;
use App\Models\Organisasi;
use App\Models\Pengguna;
use App\Models\Universitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KontakTest extends TestCase
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
            'id_universitas'      => $univ->id_universitas,
            'id_organisasi_induk' => null,
            'id_akun_organisasi'  => $user->id_pengguna,
            'nama_organisasi'     => strtoupper($jenis) . ' ' . uniqid(),
            'jenis_organisasi'    => $jenis,
            'nomor_telepon'       => '081234567890',
            'deskripsi'           => 'Deskripsi awal ' . $jenis,
            'status_organisasi'   => $statusOrg,
        ]);

        $roleName = ($jenis === 'bem') ? 'akun_bem' : 'akun_hmj_ukm';
        $user->syncRoles($roleName);

        $abilities = ($statusAkun === 'aktif') ? ['*'] : ['status-registrasi'];
        $token = $user->createToken('auth_token', $abilities)->plainTextToken;

        return [$user, $org, $token];
    }

    public function test_ambil_daftar_kontak_berhasil(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');

        KontakOrganisasi::create([
            'id_organisasi' => $org->id_organisasi,
            'jenis'         => 'wa',
            'tautan'        => '081299998888',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/organisasi/kontak');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daftar kontak berhasil diambil',
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.jenis', 'wa')
            ->assertJsonPath('data.0.tautan', '081299998888');
    }

    public function test_tambah_kontak_semua_jenis_berhasil(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');

        $kontakList = [
            ['jenis' => 'wa', 'tautan' => '08123456789'],
            ['jenis' => 'instagram', 'tautan' => 'https://instagram.com/bem_univ'],
            ['jenis' => 'email', 'tautan' => 'bem@univ.ac.id'],
            ['jenis' => 'tiktok', 'tautan' => 'https://tiktok.com/@bem_univ'],
        ];

        foreach ($kontakList as $item) {
            $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->postJson('/api/organisasi/kontak', $item);

            $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Kontak berhasil ditambahkan',
                ]);

            $this->assertDatabaseHas('kontak_organisasi', [
                'id_organisasi' => $org->id_organisasi,
                'jenis'         => $item['jenis'],
                'tautan'        => $item['tautan'],
            ]);
        }

        // Pastikan total ada 4 kontak
        $this->assertDatabaseCount('kontak_organisasi', 4);
    }

    public function test_tambah_kontak_gagal_validasi_422(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');

        // Field kosong
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/kontak', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid',
            ])
            ->assertJsonValidationErrors(['jenis', 'tautan']);

        // Jenis tidak valid
        $responseInvalidJenis = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/kontak', [
                'jenis'  => 'facebook',
                'tautan' => 'https://facebook.com/bem',
            ]);

        $responseInvalidJenis->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid',
            ])
            ->assertJsonValidationErrors(['jenis']);

        // Tautan terlalu panjang
        $responseLongTautan = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/kontak', [
                'jenis'  => 'wa',
                'tautan' => str_repeat('a', 256),
            ]);

        $responseLongTautan->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid',
            ])
            ->assertJsonValidationErrors(['tautan']);
    }

    public function test_ubah_kontak_berhasil_via_put_dan_patch(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');

        $kontak = KontakOrganisasi::create([
            'id_organisasi' => $org->id_organisasi,
            'jenis'         => 'wa',
            'tautan'        => '08123456789',
        ]);

        // PUT update
        $responsePut = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/organisasi/kontak/' . $kontak->id_kontak, [
                'jenis'  => 'email',
                'tautan' => 'humas@univ.ac.id',
            ]);

        $responsePut->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Kontak berhasil diperbarui',
            ])
            ->assertJsonPath('data.0.jenis', 'email')
            ->assertJsonPath('data.0.tautan', 'humas@univ.ac.id');

        $this->assertDatabaseHas('kontak_organisasi', [
            'id_kontak' => $kontak->id_kontak,
            'jenis'     => 'email',
            'tautan'    => 'humas@univ.ac.id',
        ]);

        // PATCH update
        $responsePatch = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/organisasi/kontak/' . $kontak->id_kontak, [
                'jenis'  => 'instagram',
                'tautan' => 'https://instagram.com/humas_univ',
            ]);

        $responsePatch->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Kontak berhasil diperbarui',
            ])
            ->assertJsonPath('data.0.jenis', 'instagram')
            ->assertJsonPath('data.0.tautan', 'https://instagram.com/humas_univ');
    }

    public function test_ubah_kontak_gagal_validasi_422(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');

        $kontak = KontakOrganisasi::create([
            'id_organisasi' => $org->id_organisasi,
            'jenis'         => 'wa',
            'tautan'        => '08123456789',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/organisasi/kontak/' . $kontak->id_kontak, [
                'jenis'  => 'invalid_platform',
                'tautan' => '',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid',
            ])
            ->assertJsonValidationErrors(['jenis', 'tautan']);
    }

    public function test_ubah_kontak_gagal_jika_bukan_milik_organisasi_idor_404(): void
    {
        [$userA, $orgA, $tokenA] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');
        [$userB, $orgB, $tokenB] = $this->buatAkunOrganisasi('hmj', 'aktif', 'aktif');

        $kontakA = KontakOrganisasi::create([
            'id_organisasi' => $orgA->id_organisasi,
            'jenis'         => 'wa',
            'tautan'        => '08111111111',
        ]);

        // Akun B mencoba mengubah kontak milik Organisasi A
        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenB)
            ->putJson('/api/organisasi/kontak/' . $kontakA->id_kontak, [
                'jenis'  => 'instagram',
                'tautan' => 'https://instagram.com/hacked',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Kontak tidak ditemukan',
            ]);

        $this->assertDatabaseHas('kontak_organisasi', [
            'id_kontak' => $kontakA->id_kontak,
            'jenis'     => 'wa',
            'tautan'    => '08111111111',
        ]);
    }

    public function test_ubah_kontak_gagal_jika_id_tidak_ditemukan_404(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/organisasi/kontak/999999', [
                'jenis'  => 'wa',
                'tautan' => '08123456789',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Kontak tidak ditemukan',
            ]);
    }

    public function test_hapus_kontak_berhasil(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');

        $kontak = KontakOrganisasi::create([
            'id_organisasi' => $org->id_organisasi,
            'jenis'         => 'wa',
            'tautan'        => '08123456789',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/organisasi/kontak/' . $kontak->id_kontak);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Kontak berhasil dihapus',
            ])
            ->assertJsonCount(0, 'data');

        $this->assertDatabaseMissing('kontak_organisasi', [
            'id_kontak' => $kontak->id_kontak,
        ]);
    }

    public function test_hapus_kontak_gagal_jika_bukan_milik_organisasi_idor_404(): void
    {
        [$userA, $orgA, $tokenA] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');
        [$userB, $orgB, $tokenB] = $this->buatAkunOrganisasi('hmj', 'aktif', 'aktif');

        $kontakA = KontakOrganisasi::create([
            'id_organisasi' => $orgA->id_organisasi,
            'jenis'         => 'wa',
            'tautan'        => '08111111111',
        ]);

        // Akun B mencoba menghapus kontak milik Organisasi A
        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenB)
            ->deleteJson('/api/organisasi/kontak/' . $kontakA->id_kontak);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Kontak tidak ditemukan',
            ]);

        $this->assertDatabaseHas('kontak_organisasi', [
            'id_kontak' => $kontakA->id_kontak,
        ]);
    }

    public function test_hapus_kontak_gagal_jika_id_tidak_ditemukan_404(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/organisasi/kontak/999999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Kontak tidak ditemukan',
            ]);
    }

    public function test_akses_kontak_ditolak_tanpa_token(): void
    {
        $this->getJson('/api/organisasi/kontak')
            ->assertStatus(401);

        $this->postJson('/api/organisasi/kontak', ['jenis' => 'wa', 'tautan' => '08123456789'])
            ->assertStatus(401);

        $this->putJson('/api/organisasi/kontak/1', ['jenis' => 'wa', 'tautan' => '08123456789'])
            ->assertStatus(401);

        $this->deleteJson('/api/organisasi/kontak/1')
            ->assertStatus(401);
    }

    public function test_akses_kontak_ditolak_jika_bukan_peran_akun_organisasi(): void
    {
        $admin = Pengguna::factory()->administrator()->create(['status_akun' => 'aktif']);
        $tokenAdmin = $admin->createToken('admin_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenAdmin)
            ->getJson('/api/organisasi/kontak');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akses ditolak',
            ]);
    }

    public function test_akses_kontak_ditolak_jika_organisasi_belum_aktif(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'menunggu');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/organisasi/kontak');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akun organisasi belum aktif atau tidak ditemukan',
            ]);

        $responsePost = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/kontak', [
                'jenis'  => 'wa',
                'tautan' => '08123456789',
            ]);

        $responsePost->assertStatus(403);
    }

    public function test_akses_kontak_dengan_id_bukan_angka_mengembalikan_404(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/organisasi/kontak/abc', ['jenis' => 'wa', 'tautan' => '08123456789'])
            ->assertStatus(404);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/organisasi/kontak/xyz')
            ->assertStatus(404);
    }
}
