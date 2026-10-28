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

class DirektoriOrganisasiTest extends TestCase
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

    private function buatPenggunaAktif(string $peran = 'anggota'): array
    {
        $user = Pengguna::factory()->create([
            'peran'       => $peran,
            'status_akun' => 'aktif',
        ]);
        $user->syncRoles($peran);

        $token = $user->createToken('auth_token', ['*'])->plainTextToken;

        return [$user, ['Authorization' => "Bearer {$token}"]];
    }

    public function test_akses_direktori_universitas_tanpa_token_gagal_401(): void
    {
        $response = $this->getJson('/api/direktori/universitas');
        $response->assertStatus(401);
    }

    public function test_akses_direktori_organisasi_tanpa_token_gagal_401(): void
    {
        $response = $this->getJson('/api/direktori/universitas/1/organisasi');
        $response->assertStatus(401);
    }

    public function test_akses_direktori_oleh_pengguna_nonaktif_gagal_403(): void
    {
        $user = Pengguna::factory()->create([
            'peran'       => 'anggota',
            'status_akun' => 'nonaktif',
        ]);
        $token = $user->createToken('auth_token', ['*'])->plainTextToken;

        $response = $this->getJson('/api/direktori/universitas', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akun dinonaktifkan',
            ]);
    }

    public function test_akses_direktori_universitas_oleh_anggota_terverifikasi_berhasil_dan_urut_alfabetis(): void
    {
        [, $headers] = $this->buatPenggunaAktif('anggota');

        Universitas::create(['nama_universitas' => 'Universitas Muhammadiyah Surabaya']);
        Universitas::create(['nama_universitas' => 'Universitas Muhammadiyah Gresik']);
        Universitas::create(['nama_universitas' => 'Universitas Muhammadiyah Malang']);

        $response = $this->getJson('/api/direktori/universitas', $headers);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daftar universitas berhasil diambil',
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id_universitas', 'nama_universitas', 'logo', 'is_default_logo'],
                ],
            ]);

        $daftar = $response->json('data');
        $this->assertCount(3, $daftar);
        $this->assertEquals('Universitas Muhammadiyah Gresik', $daftar[0]['nama_universitas']);
        $this->assertEquals('Universitas Muhammadiyah Malang', $daftar[1]['nama_universitas']);
        $this->assertEquals('Universitas Muhammadiyah Surabaya', $daftar[2]['nama_universitas']);
    }

    public function test_direktori_universitas_menampilkan_logo_storage_dan_logo_bawaan(): void
    {
        [, $headers] = $this->buatPenggunaAktif('anggota');

        $pathLogo = UploadedFile::fake()->image('logo_umm.png')->storeAs('logo-universitas', 'logo_umm.png', 'public');
        $univWithLogo = Universitas::create([
            'nama_universitas' => 'UMM',
            'logo'             => $pathLogo,
        ]);

        $univWithoutLogo = Universitas::create([
            'nama_universitas' => 'UMSIDA',
            'logo'             => null,
        ]);

        $response = $this->getJson('/api/direktori/universitas', $headers);

        $response->assertStatus(200);
        $data = collect($response->json('data'));

        $itemWithLogo = $data->firstWhere('id_universitas', $univWithLogo->id_universitas);
        $this->assertFalse($itemWithLogo['is_default_logo']);
        $this->assertStringContainsString('logo-universitas/logo_umm.png', $itemWithLogo['logo']);

        $itemWithoutLogo = $data->firstWhere('id_universitas', $univWithoutLogo->id_universitas);
        $this->assertTrue($itemWithoutLogo['is_default_logo']);
        $this->assertStringContainsString('default-university.png', $itemWithoutLogo['logo']);
    }

    public function test_akses_direktori_universitas_oleh_administrator_dan_akun_organisasi(): void
    {
        [, $adminHeaders] = $this->buatPenggunaAktif('administrator');
        Universitas::create(['nama_universitas' => 'Universitas Muhammadiyah Jember']);

        $resAdmin = $this->getJson('/api/direktori/universitas', $adminHeaders);
        $resAdmin->assertStatus(200)->assertJson(['success' => true]);

        [, $orgHeaders] = $this->buatPenggunaAktif('akun_bem');
        $resOrg = $this->getJson('/api/direktori/universitas', $orgHeaders);
        $resOrg->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_direktori_organisasi_universitas_tidak_ditemukan_mengembalikan_404(): void
    {
        [, $headers] = $this->buatPenggunaAktif('anggota');

        $response = $this->getJson('/api/direktori/universitas/99999/organisasi', $headers);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Universitas tidak ditemukan',
            ]);
    }

    public function test_direktori_organisasi_kosong_mengembalikan_pesan_informatif(): void
    {
        [, $headers] = $this->buatPenggunaAktif('anggota');

        $univ = Universitas::create(['nama_universitas' => 'Universitas Baru']);

        $response = $this->getJson("/api/direktori/universitas/{$univ->id_universitas}/organisasi", $headers);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Belum ada organisasi mahasiswa yang terverifikasi pada universitas ini',
                'data'    => [
                    'universitas' => [
                        'id_universitas'   => $univ->id_universitas,
                        'nama_universitas' => 'Universitas Baru',
                    ],
                    'organisasi' => [],
                ],
            ]);
    }

    public function test_direktori_organisasi_hanya_menampilkan_organisasi_terverifikasi_aktif_di_universitas_terkait(): void
    {
        [, $headers] = $this->buatPenggunaAktif('anggota');

        $univTarget = Universitas::create(['nama_universitas' => 'Universitas Target']);
        $univLain   = Universitas::create(['nama_universitas' => 'Universitas Lain']);

        $bidang = BidangOrganisasi::create(['nama_bidang' => 'Penalaran']);

        $bemAkun = Pengguna::factory()->akunOrganisasi()->create(['status_akun' => 'aktif']);
        $bem = Organisasi::create([
            'id_universitas'    => $univTarget->id_universitas,
            'id_akun_organisasi'=> $bemAkun->id_pengguna,
            'id_bidang'         => $bidang->id_bidang,
            'nama_organisasi'   => 'BEM Universitas Target',
            'jenis_organisasi'  => 'bem',
            'status_organisasi' => 'aktif',
            'deskripsi'         => 'BEM Target',
        ]);

        $hmjAkun = Pengguna::factory()->akunOrganisasi()->create(['status_akun' => 'aktif']);
        $hmj = Organisasi::create([
            'id_universitas'      => $univTarget->id_universitas,
            'id_organisasi_induk' => $bem->id_organisasi,
            'id_akun_organisasi'  => $hmjAkun->id_pengguna,
            'id_bidang'           => $bidang->id_bidang,
            'nama_organisasi'     => 'HIMASIF',
            'jenis_organisasi'    => 'hmj',
            'status_organisasi'   => 'aktif',
            'deskripsi'           => 'Himpunan Mahasiswa Informatika',
        ]);

        // Organisasi di univTarget tetapi status bukan aktif (menunggu verifikasi)
        $ukmMenungguAkun = Pengguna::factory()->akunOrganisasi()->create(['status_akun' => 'menunggu']);
        Organisasi::create([
            'id_universitas'    => $univTarget->id_universitas,
            'id_akun_organisasi'=> $ukmMenungguAkun->id_pengguna,
            'id_bidang'         => $bidang->id_bidang,
            'nama_organisasi'   => 'UKM Robotika Menunggu',
            'jenis_organisasi'  => 'ukm',
            'status_organisasi' => 'menunggu',
        ]);

        // Organisasi aktif tetapi di universitas lain
        $bemLainAkun = Pengguna::factory()->akunOrganisasi()->create(['status_akun' => 'aktif']);
        Organisasi::create([
            'id_universitas'    => $univLain->id_universitas,
            'id_akun_organisasi'=> $bemLainAkun->id_pengguna,
            'id_bidang'         => $bidang->id_bidang,
            'nama_organisasi'   => 'BEM Universitas Lain',
            'jenis_organisasi'  => 'bem',
            'status_organisasi' => 'aktif',
        ]);

        $response = $this->getJson("/api/direktori/universitas/{$univTarget->id_universitas}/organisasi", $headers);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daftar organisasi mahasiswa berhasil diambil',
                'data'    => [
                    'universitas' => [
                        'id_universitas'   => $univTarget->id_universitas,
                        'nama_universitas' => 'Universitas Target',
                    ],
                ],
            ]);

        $organisasiList = $response->json('data.organisasi');
        $this->assertCount(2, $organisasiList);

        // BEM urutan pertama
        $this->assertEquals('BEM Universitas Target', $organisasiList[0]['nama_organisasi']);
        $this->assertEquals('bem', $organisasiList[0]['jenis_organisasi']);
        $this->assertEquals('Penalaran', $organisasiList[0]['nama_bidang']);

        // HMJ urutan kedua
        $this->assertEquals('HIMASIF', $organisasiList[1]['nama_organisasi']);
        $this->assertEquals('hmj', $organisasiList[1]['jenis_organisasi']);
    }

    public function test_direktori_organisasi_menampilkan_logo_organisasi_atau_ikon_bawaan(): void
    {
        [, $headers] = $this->buatPenggunaAktif('anggota');

        $univ = Universitas::create(['nama_universitas' => 'Universitas Tes']);
        $pathLogoOrg = UploadedFile::fake()->image('logo_bem.png')->storeAs('logo-organisasi', 'logo_bem.png', 'public');

        $bemAkun = Pengguna::factory()->akunOrganisasi()->create(['status_akun' => 'aktif']);
        $bem = Organisasi::create([
            'id_universitas'    => $univ->id_universitas,
            'id_akun_organisasi'=> $bemAkun->id_pengguna,
            'nama_organisasi'   => 'BEM Tes',
            'jenis_organisasi'  => 'bem',
            'status_organisasi' => 'aktif',
            'logo'              => $pathLogoOrg,
        ]);

        $ukmAkun = Pengguna::factory()->akunOrganisasi()->create(['status_akun' => 'aktif']);
        $ukm = Organisasi::create([
            'id_universitas'    => $univ->id_universitas,
            'id_akun_organisasi'=> $ukmAkun->id_pengguna,
            'nama_organisasi'   => 'UKM Seni',
            'jenis_organisasi'  => 'ukm',
            'status_organisasi' => 'aktif',
            'logo'              => null,
        ]);

        $response = $this->getJson("/api/direktori/universitas/{$univ->id_universitas}/organisasi", $headers);

        $response->assertStatus(200);
        $dataOrg = collect($response->json('data.organisasi'));

        $bemData = $dataOrg->firstWhere('id_organisasi', $bem->id_organisasi);
        $this->assertFalse($bemData['is_default_logo']);
        $this->assertStringContainsString('logo-organisasi/logo_bem.png', $bemData['logo']);

        $ukmData = $dataOrg->firstWhere('id_organisasi', $ukm->id_organisasi);
        $this->assertTrue($ukmData['is_default_logo']);
        $this->assertStringContainsString('default-organisasi.png', $ukmData['logo']);
    }
}
