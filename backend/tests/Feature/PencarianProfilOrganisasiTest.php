<?php

namespace Tests\Feature;

use App\Models\BidangOrganisasi;
use App\Models\KontakOrganisasi;
use App\Models\Organisasi;
use App\Models\Pengguna;
use App\Models\Portofolio;
use App\Models\Universitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PencarianProfilOrganisasiTest extends TestCase
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

    /**
     * Helper: buat pengguna aktif dengan token.
     */
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

    /**
     * Helper: buat organisasi aktif lengkap dengan akun pengguna.
     */
    private function buatOrganisasiAktif(
        Universitas $univ,
        ?BidangOrganisasi $bidang,
        string $nama,
        string $jenis = 'bem',
        string $statusOrganisasi = 'aktif',
        ?int $idInduk = null,
    ): Organisasi {
        $akun = Pengguna::factory()->akunOrganisasi()->create([
            'status_akun' => $statusOrganisasi === 'aktif' ? 'aktif' : 'menunggu',
        ]);

        return Organisasi::create([
            'id_universitas'      => $univ->id_universitas,
            'id_organisasi_induk' => $idInduk,
            'id_akun_organisasi'  => $akun->id_pengguna,
            'id_bidang'           => $bidang?->id_bidang,
            'nama_organisasi'     => $nama,
            'jenis_organisasi'    => $jenis,
            'status_organisasi'   => $statusOrganisasi,
            'deskripsi'           => "Deskripsi {$nama}",
        ]);
    }

    // =========================================================================
    // MIDDLEWARE / AUTH TESTS
    // =========================================================================

    public function test_akses_cari_organisasi_tanpa_token_gagal_401(): void
    {
        $response = $this->getJson('/api/organisasi/cari');
        $response->assertStatus(401);
    }

    public function test_akses_profil_organisasi_tanpa_token_gagal_401(): void
    {
        $response = $this->getJson('/api/organisasi/1');
        $response->assertStatus(401);
    }

    public function test_akses_cari_organisasi_oleh_pengguna_nonaktif_gagal_403(): void
    {
        $user = Pengguna::factory()->create([
            'peran'       => 'anggota',
            'status_akun' => 'nonaktif',
        ]);
        $token = $user->createToken('auth_token', ['*'])->plainTextToken;

        $response = $this->getJson('/api/organisasi/cari', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akun dinonaktifkan',
            ]);
    }

    public function test_akses_profil_organisasi_oleh_pengguna_nonaktif_gagal_403(): void
    {
        $user = Pengguna::factory()->create([
            'peran'       => 'anggota',
            'status_akun' => 'nonaktif',
        ]);
        $token = $user->createToken('auth_token', ['*'])->plainTextToken;

        $response = $this->getJson('/api/organisasi/1', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akun dinonaktifkan',
            ]);
    }

    // =========================================================================
    // ORMV-1-19: CARI & FILTER ORGANISASI
    // =========================================================================

    public function test_cari_organisasi_tanpa_filter_mengembalikan_semua_organisasi_aktif(): void
    {
        [, $headers] = $this->buatPenggunaAktif('anggota');

        $univ = Universitas::create(['nama_universitas' => 'Universitas Test']);
        $bidang = BidangOrganisasi::create(['nama_bidang' => 'Teknologi']);

        $this->buatOrganisasiAktif($univ, $bidang, 'BEM Test', 'bem');
        $this->buatOrganisasiAktif($univ, $bidang, 'HIMASIF', 'hmj');
        // Organisasi tidak aktif — harus disembunyikan
        $this->buatOrganisasiAktif($univ, $bidang, 'UKM Menunggu', 'ukm', 'menunggu');

        $response = $this->getJson('/api/organisasi/cari', $headers);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daftar organisasi berhasil diambil',
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        // Diurutkan berdasarkan nama_organisasi ASC
        $this->assertEquals('BEM Test', $data[0]['nama_organisasi']);
        $this->assertEquals('HIMASIF', $data[1]['nama_organisasi']);
    }

    public function test_cari_organisasi_dengan_keyword_nama(): void
    {
        [, $headers] = $this->buatPenggunaAktif('anggota');

        $univ = Universitas::create(['nama_universitas' => 'Universitas Test']);
        $bidang = BidangOrganisasi::create(['nama_bidang' => 'Sosial']);

        $this->buatOrganisasiAktif($univ, $bidang, 'BEM Universitas Test', 'bem');
        $this->buatOrganisasiAktif($univ, $bidang, 'HIMASIF', 'hmj');
        $this->buatOrganisasiAktif($univ, $bidang, 'UKM Basket', 'ukm');

        $response = $this->getJson('/api/organisasi/cari?keyword=BEM', $headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('BEM Universitas Test', $data[0]['nama_organisasi']);
    }

    public function test_cari_organisasi_dengan_filter_universitas(): void
    {
        [, $headers] = $this->buatPenggunaAktif('anggota');

        $univA = Universitas::create(['nama_universitas' => 'Universitas A']);
        $univB = Universitas::create(['nama_universitas' => 'Universitas B']);
        $bidang = BidangOrganisasi::create(['nama_bidang' => 'Seni']);

        $this->buatOrganisasiAktif($univA, $bidang, 'BEM Univ A', 'bem');
        $this->buatOrganisasiAktif($univB, $bidang, 'BEM Univ B', 'bem');

        $response = $this->getJson("/api/organisasi/cari?id_universitas={$univA->id_universitas}", $headers);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('BEM Univ A', $data[0]['nama_organisasi']);
        $this->assertEquals($univA->id_universitas, $data[0]['id_universitas']);
    }

    public function test_cari_organisasi_dengan_filter_jenis(): void
    {
        [, $headers] = $this->buatPenggunaAktif('anggota');

        $univ = Universitas::create(['nama_universitas' => 'Universitas Test']);
        $bidang = BidangOrganisasi::create(['nama_bidang' => 'Olahraga']);

        $this->buatOrganisasiAktif($univ, $bidang, 'BEM Test', 'bem');
        $this->buatOrganisasiAktif($univ, $bidang, 'HIMASIF', 'hmj');
        $this->buatOrganisasiAktif($univ, $bidang, 'UKM Basket', 'ukm');

        $response = $this->getJson('/api/organisasi/cari?jenis_organisasi=hmj', $headers);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('hmj', $data[0]['jenis_organisasi']);
    }

    public function test_cari_organisasi_dengan_filter_bidang(): void
    {
        [, $headers] = $this->buatPenggunaAktif('anggota');

        $univ = Universitas::create(['nama_universitas' => 'Universitas Test']);
        $bidangA = BidangOrganisasi::create(['nama_bidang' => 'Penalaran']);
        $bidangB = BidangOrganisasi::create(['nama_bidang' => 'Seni']);

        $this->buatOrganisasiAktif($univ, $bidangA, 'UKM Robotika', 'ukm');
        $this->buatOrganisasiAktif($univ, $bidangB, 'UKM Teater', 'ukm');

        $response = $this->getJson("/api/organisasi/cari?id_bidang={$bidangA->id_bidang}", $headers);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('UKM Robotika', $data[0]['nama_organisasi']);
        $this->assertEquals($bidangA->id_bidang, $data[0]['id_bidang']);
        $this->assertEquals('Penalaran', $data[0]['nama_bidang']);
    }

    public function test_cari_organisasi_dengan_kombinasi_filter(): void
    {
        [, $headers] = $this->buatPenggunaAktif('anggota');

        $univA = Universitas::create(['nama_universitas' => 'Universitas A']);
        $univB = Universitas::create(['nama_universitas' => 'Universitas B']);
        $bidangTeknologi = BidangOrganisasi::create(['nama_bidang' => 'Teknologi']);
        $bidangSeni = BidangOrganisasi::create(['nama_bidang' => 'Seni']);

        $target = $this->buatOrganisasiAktif($univA, $bidangTeknologi, 'HIMASIF', 'hmj');
        $this->buatOrganisasiAktif($univA, $bidangSeni, 'UKM Teater', 'ukm');
        $this->buatOrganisasiAktif($univB, $bidangTeknologi, 'HIMASIF B', 'hmj');
        $this->buatOrganisasiAktif($univA, $bidangTeknologi, 'BEM A', 'bem');

        $query = http_build_query([
            'keyword'           => 'HIMASIF',
            'id_universitas'    => $univA->id_universitas,
            'jenis_organisasi'  => 'hmj',
            'id_bidang'         => $bidangTeknologi->id_bidang,
        ]);

        $response = $this->getJson("/api/organisasi/cari?{$query}", $headers);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('HIMASIF', $data[0]['nama_organisasi']);
        $this->assertEquals($target->id_organisasi, $data[0]['id_organisasi']);
    }

    public function test_cari_organisasi_tidak_menampilkan_organisasi_nonaktif(): void
    {
        [, $headers] = $this->buatPenggunaAktif('anggota');

        $univ = Universitas::create(['nama_universitas' => 'Universitas Test']);
        $bidang = BidangOrganisasi::create(['nama_bidang' => 'Umum']);

        $this->buatOrganisasiAktif($univ, $bidang, 'Org Menunggu', 'bem', 'menunggu');
        $this->buatOrganisasiAktif($univ, $bidang, 'Org Ditangguhkan', 'hmj', 'ditangguhkan');
        $this->buatOrganisasiAktif($univ, $bidang, 'Org Nonaktif', 'ukm', 'nonaktif');
        $orgAktif = $this->buatOrganisasiAktif($univ, $bidang, 'Org Aktif', 'bem');

        $response = $this->getJson('/api/organisasi/cari', $headers);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($orgAktif->id_organisasi, $data[0]['id_organisasi']);
    }

    public function test_cari_organisasi_hasil_kosong_mengembalikan_pesan(): void
    {
        [, $headers] = $this->buatPenggunaAktif('anggota');

        $response = $this->getJson('/api/organisasi/cari?keyword=TidakAdaOrganisasiIni', $headers);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Organisasi tidak ditemukan',
                'data'    => [],
            ]);
    }

    public function test_cari_organisasi_menampilkan_struktur_data_lengkap(): void
    {
        [, $headers] = $this->buatPenggunaAktif('anggota');

        $univ = Universitas::create(['nama_universitas' => 'Universitas Lengkap']);
        $bidang = BidangOrganisasi::create(['nama_bidang' => 'Penalaran']);
        $this->buatOrganisasiAktif($univ, $bidang, 'BEM Lengkap', 'bem');

        $response = $this->getJson('/api/organisasi/cari', $headers);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id_organisasi',
                        'id_universitas',
                        'nama_organisasi',
                        'jenis_organisasi',
                        'id_bidang',
                        'nama_bidang',
                        'nama_universitas',
                        'deskripsi',
                        'alamat',
                        'logo',
                        'is_default_logo',
                    ],
                ],
            ]);
    }

    public function test_cari_organisasi_dapat_diakses_oleh_akun_organisasi(): void
    {
        [, $headers] = $this->buatPenggunaAktif('akun_bem');

        $univ = Universitas::create(['nama_universitas' => 'Universitas Test']);
        $bidang = BidangOrganisasi::create(['nama_bidang' => 'Umum']);
        $this->buatOrganisasiAktif($univ, $bidang, 'BEM Test', 'bem');

        $response = $this->getJson('/api/organisasi/cari', $headers);

        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    // =========================================================================
    // ORMV-1-20: LIHAT PROFIL ORGANISASI
    // =========================================================================

    public function test_lihat_profil_organisasi_lengkap_dengan_portofolio_dan_kontak(): void
    {
        [, $headers] = $this->buatPenggunaAktif('anggota');

        $univ = Universitas::create(['nama_universitas' => 'Universitas Test']);
        $bidang = BidangOrganisasi::create(['nama_bidang' => 'Teknologi']);
        $org = $this->buatOrganisasiAktif($univ, $bidang, 'BEM Profil Test', 'bem');

        // Buat portofolio dengan tahun berbeda
        Portofolio::create([
            'id_organisasi' => $org->id_organisasi,
            'judul'         => 'Kegiatan 2024',
            'tahun'         => 2024,
            'deskripsi'     => 'Deskripsi kegiatan 2024',
            'dokumentasi'   => 'https://example.com/foto2024.jpg',
        ]);
        Portofolio::create([
            'id_organisasi' => $org->id_organisasi,
            'judul'         => 'Kegiatan 2026',
            'tahun'         => 2026,
            'deskripsi'     => 'Deskripsi kegiatan 2026',
            'dokumentasi'   => 'https://example.com/foto2026.jpg',
        ]);
        Portofolio::create([
            'id_organisasi' => $org->id_organisasi,
            'judul'         => 'Kegiatan 2025',
            'tahun'         => 2025,
            'deskripsi'     => 'Deskripsi kegiatan 2025',
            'dokumentasi'   => 'https://example.com/foto2025.jpg',
        ]);

        // Buat kontak
        KontakOrganisasi::create([
            'id_organisasi' => $org->id_organisasi,
            'jenis'         => 'instagram',
            'tautan'        => 'https://instagram.com/bemtest',
        ]);
        KontakOrganisasi::create([
            'id_organisasi' => $org->id_organisasi,
            'jenis'         => 'wa',
            'tautan'        => '081234567890',
        ]);

        $response = $this->getJson("/api/organisasi/{$org->id_organisasi}", $headers);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profil organisasi berhasil diambil',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id_organisasi',
                    'nama_organisasi',
                    'jenis_organisasi',
                    'deskripsi',
                    'alamat',
                    'nomor_telepon',
                    'logo',
                    'is_default_logo',
                    'status_organisasi',
                    'universitas' => ['id_universitas', 'nama_universitas'],
                    'bidang' => ['id_bidang', 'nama_bidang'],
                    'portofolio' => [
                        '*' => ['id_portofolio', 'judul', 'tahun', 'deskripsi', 'dokumentasi'],
                    ],
                    'kontak' => [
                        '*' => ['id_kontak', 'jenis', 'tautan'],
                    ],
                ],
            ]);

        $data = $response->json('data');

        // Verifikasi informasi umum
        $this->assertEquals('BEM Profil Test', $data['nama_organisasi']);
        $this->assertEquals('bem', $data['jenis_organisasi']);
        $this->assertEquals('Universitas Test', $data['universitas']['nama_universitas']);
        $this->assertEquals('Teknologi', $data['bidang']['nama_bidang']);

        // Verifikasi portofolio diurutkan tahun DESC
        $this->assertCount(3, $data['portofolio']);
        $this->assertEquals(2026, $data['portofolio'][0]['tahun']);
        $this->assertEquals(2025, $data['portofolio'][1]['tahun']);
        $this->assertEquals(2024, $data['portofolio'][2]['tahun']);

        // Verifikasi kontak
        $this->assertCount(2, $data['kontak']);
    }

    public function test_lihat_profil_organisasi_tidak_ditemukan_404(): void
    {
        [, $headers] = $this->buatPenggunaAktif('anggota');

        $response = $this->getJson('/api/organisasi/99999', $headers);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Organisasi tidak ditemukan',
            ]);
    }

    public function test_lihat_profil_organisasi_nonaktif_mengembalikan_404(): void
    {
        [, $headers] = $this->buatPenggunaAktif('anggota');

        $univ = Universitas::create(['nama_universitas' => 'Universitas Test']);
        $bidang = BidangOrganisasi::create(['nama_bidang' => 'Umum']);

        $orgMenunggu = $this->buatOrganisasiAktif($univ, $bidang, 'Org Menunggu', 'bem', 'menunggu');

        $response = $this->getJson("/api/organisasi/{$orgMenunggu->id_organisasi}", $headers);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Organisasi tidak ditemukan',
            ]);
    }

    public function test_lihat_profil_organisasi_tanpa_portofolio_dan_kontak(): void
    {
        [, $headers] = $this->buatPenggunaAktif('anggota');

        $univ = Universitas::create(['nama_universitas' => 'Universitas Test']);
        $bidang = BidangOrganisasi::create(['nama_bidang' => 'Umum']);
        $org = $this->buatOrganisasiAktif($univ, $bidang, 'Org Baru', 'ukm');

        $response = $this->getJson("/api/organisasi/{$org->id_organisasi}", $headers);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profil organisasi berhasil diambil',
                'data'    => [
                    'nama_organisasi' => 'Org Baru',
                    'portofolio'      => [],
                    'kontak'          => [],
                ],
            ]);
    }

    public function test_lihat_profil_organisasi_dapat_diakses_oleh_akun_organisasi(): void
    {
        [, $headers] = $this->buatPenggunaAktif('akun_bem');

        $univ = Universitas::create(['nama_universitas' => 'Universitas Test']);
        $bidang = BidangOrganisasi::create(['nama_bidang' => 'Umum']);
        $org = $this->buatOrganisasiAktif($univ, $bidang, 'BEM Test', 'bem');

        $response = $this->getJson("/api/organisasi/{$org->id_organisasi}", $headers);

        $response->assertStatus(200)->assertJson(['success' => true]);
    }
}
