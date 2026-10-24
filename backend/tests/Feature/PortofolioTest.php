<?php

namespace Tests\Feature;

use App\Models\Organisasi;
use App\Models\Pengguna;
use App\Models\Portofolio;
use App\Models\Universitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PortofolioTest extends TestCase
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

    public function test_tambah_portofolio_berhasil_dengan_dokumentasi_string(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');

        $payload = [
            'judul'       => 'Seminar Nasional Kepemimpinan Mahasiswa',
            'tahun'       => 2025,
            'deskripsi'   => 'Kegiatan seminar tahunan yang dihadiri oleh ratusan delegasi kampus.',
            'dokumentasi' => 'https://drive.google.com/file/d/seminar-2025.pdf',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/portofolio', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Portofolio berhasil ditambahkan',
            ])
            ->assertJsonPath('data.0.judul', 'Seminar Nasional Kepemimpinan Mahasiswa')
            ->assertJsonPath('data.0.tahun', 2025)
            ->assertJsonPath('data.0.deskripsi', 'Kegiatan seminar tahunan yang dihadiri oleh ratusan delegasi kampus.')
            ->assertJsonPath('data.0.dokumentasi', 'https://drive.google.com/file/d/seminar-2025.pdf');

        $this->assertDatabaseHas('portofolio', [
            'id_organisasi' => $org->id_organisasi,
            'judul'         => 'Seminar Nasional Kepemimpinan Mahasiswa',
            'tahun'         => 2025,
        ]);
    }

    public function test_tambah_portofolio_berhasil_dengan_file_dokumentasi(): void
    {
        Storage::fake('public');

        [$user, $org, $token] = $this->buatAkunOrganisasi('hmj', 'aktif', 'aktif');

        $file = UploadedFile::fake()->create('laporan-portofolio.pdf', 1000, 'application/pdf');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->post('/api/organisasi/portofolio', [
                'judul'       => 'Pekan Ilmiah dan Teknologi',
                'tahun'       => 2024,
                'deskripsi'   => 'Pameran karya inovasi teknologi mahasiswa.',
                'dokumentasi' => $file,
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Portofolio berhasil ditambahkan',
            ]);

        $this->assertDatabaseHas('portofolio', [
            'id_organisasi' => $org->id_organisasi,
            'judul'         => 'Pekan Ilmiah dan Teknologi',
            'tahun'         => 2024,
        ]);

        $portofolio = Portofolio::where('id_organisasi', $org->id_organisasi)->first();
        $this->assertNotNull($portofolio->dokumentasi);
    }

    public function test_seluruh_portofolio_diurutkan_tahun_secara_descending(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('ukm', 'aktif', 'aktif');

        // Tambah portofolio tahun 2021
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/portofolio', [
                'judul'       => 'Turnamen Olahraga 2021',
                'tahun'       => 2021,
                'deskripsi'   => 'Turnamen tahun 2021',
                'dokumentasi' => 'https://example.com/2021.jpg',
            ])->assertStatus(201);

        // Tambah portofolio tahun 2025
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/portofolio', [
                'judul'       => 'Turnamen Olahraga 2025',
                'tahun'       => 2025,
                'deskripsi'   => 'Turnamen tahun 2025',
                'dokumentasi' => 'https://example.com/2025.jpg',
            ])->assertStatus(201);

        // Tambah portofolio tahun 2023
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/portofolio', [
                'judul'       => 'Turnamen Olahraga 2023',
                'tahun'       => 2023,
                'deskripsi'   => 'Turnamen tahun 2023',
                'dokumentasi' => 'https://example.com/2023.jpg',
            ]);

        $response->assertStatus(201);

        $data = $response->json('data');
        $this->assertCount(3, $data);
        // Pastikan urutan tahun: 2025, 2023, 2021
        $this->assertEquals(2025, $data[0]['tahun']);
        $this->assertEquals(2023, $data[1]['tahun']);
        $this->assertEquals(2021, $data[2]['tahun']);
    }

    public function test_tambah_portofolio_gagal_jika_field_wajib_kosong(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/portofolio', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid',
            ])
            ->assertJsonValidationErrors(['judul', 'tahun', 'deskripsi', 'dokumentasi']);
    }

    public function test_tambah_portofolio_gagal_jika_tahun_tidak_valid(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');

        // Tahun di bawah batas min (2000)
        $responseMin = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/portofolio', [
                'judul'       => 'Kegiatan Lama',
                'tahun'       => 1999,
                'deskripsi'   => 'Deskripsi kegiatan lama',
                'dokumentasi' => 'https://example.com/doc.jpg',
            ]);

        $responseMin->assertStatus(422)
            ->assertJsonValidationErrors(['tahun']);

        // Tahun bukan integer
        $responseString = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/portofolio', [
                'judul'       => 'Kegiatan Salah Tahun',
                'tahun'       => 'dua-ribu-dua-puluh',
                'deskripsi'   => 'Deskripsi',
                'dokumentasi' => 'https://example.com/doc.jpg',
            ]);

        $responseString->assertStatus(422)
            ->assertJsonValidationErrors(['tahun']);
    }

    public function test_tambah_portofolio_gagal_jika_judul_lebih_dari_200_karakter(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/portofolio', [
                'judul'       => str_repeat('A', 201),
                'tahun'       => 2024,
                'deskripsi'   => 'Deskripsi portofolio',
                'dokumentasi' => 'https://example.com/doc.jpg',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['judul']);
    }

    public function test_akses_ditolak_jika_tanpa_token(): void
    {
        $response = $this->postJson('/api/organisasi/portofolio', [
            'judul'       => 'Judul Portofolio',
            'tahun'       => 2024,
            'deskripsi'   => 'Deskripsi',
            'dokumentasi' => 'https://example.com/doc.jpg',
        ]);

        $response->assertStatus(401);
    }

    public function test_akses_ditolak_jika_peran_bukan_akun_organisasi(): void
    {
        // Anggota
        $anggota = Pengguna::factory()->create([
            'peran'       => 'anggota',
            'status_akun' => 'aktif',
        ]);
        $tokenAnggota = $anggota->createToken('auth_token', ['*'])->plainTextToken;

        $responseAnggota = $this->withHeader('Authorization', 'Bearer ' . $tokenAnggota)
            ->postJson('/api/organisasi/portofolio', [
                'judul'       => 'Judul Anggota',
                'tahun'       => 2024,
                'deskripsi'   => 'Deskripsi',
                'dokumentasi' => 'https://example.com/doc.jpg',
            ]);

        $responseAnggota->assertStatus(403);

        // Administrator
        $admin = Pengguna::factory()->administrator()->create([
            'status_akun' => 'aktif',
        ]);
        $tokenAdmin = $admin->createToken('auth_token', ['*'])->plainTextToken;

        $responseAdmin = $this->withHeader('Authorization', 'Bearer ' . $tokenAdmin)
            ->postJson('/api/organisasi/portofolio', [
                'judul'       => 'Judul Admin',
                'tahun'       => 2024,
                'deskripsi'   => 'Deskripsi',
                'dokumentasi' => 'https://example.com/doc.jpg',
            ]);

        $responseAdmin->assertStatus(403);
    }

    public function test_akses_ditolak_jika_akun_belum_verifikasi(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'menunggu', 'menunggu');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/portofolio', [
                'judul'       => 'Judul Portofolio Belum Verif',
                'tahun'       => 2024,
                'deskripsi'   => 'Deskripsi',
                'dokumentasi' => 'https://example.com/doc.jpg',
            ]);

        $response->assertStatus(403);
    }

    public function test_akses_ditolak_jika_organisasi_belum_aktif(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'menunggu');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/organisasi/portofolio', [
                'judul'       => 'Judul Portofolio Org Nonaktif',
                'tahun'       => 2024,
                'deskripsi'   => 'Deskripsi',
                'dokumentasi' => 'https://example.com/doc.jpg',
            ]);

        $response->assertStatus(403);
    }

    public function test_portofolio_terisolasi_per_organisasi(): void
    {
        [$user1, $org1, $token1] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');
        [$user2, $org2, $token2] = $this->buatAkunOrganisasi('hmj', 'aktif', 'aktif');

        auth()->forgetGuards();

        // Org 1 tambah portofolio
        $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->postJson('/api/organisasi/portofolio', [
                'judul'       => 'Portofolio Khusus BEM',
                'tahun'       => 2024,
                'deskripsi'   => 'Deskripsi BEM',
                'dokumentasi' => 'https://example.com/bem.jpg',
            ])->assertStatus(201);

        auth()->forgetGuards();

        // Org 2 tambah portofolio
        $responseOrg2 = $this->withHeader('Authorization', 'Bearer ' . $token2)
            ->postJson('/api/organisasi/portofolio', [
                'judul'       => 'Portofolio Khusus HMJ',
                'tahun'       => 2025,
                'deskripsi'   => 'Deskripsi HMJ',
                'dokumentasi' => 'https://example.com/hmj.jpg',
            ]);

        $responseOrg2->assertStatus(201);

        // Org 2 data hanya berisi 1 portofolio milik HMJ
        $dataOrg2 = $responseOrg2->json('data');
        $this->assertCount(1, $dataOrg2);
        $this->assertEquals('Portofolio Khusus HMJ', $dataOrg2[0]['judul']);

        auth()->forgetGuards();

        // Cek GET daftar portofolio Org 1
        $getOrg1 = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->getJson('/api/organisasi/portofolio');

        $getOrg1->assertStatus(200);
        $dataOrg1 = $getOrg1->json('data');
        $this->assertCount(1, $dataOrg1);
        $this->assertEquals('Portofolio Khusus BEM', $dataOrg1[0]['judul']);
    }

    public function test_ambil_detail_portofolio_berhasil(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');

        $portofolio = Portofolio::create([
            'id_organisasi' => $org->id_organisasi,
            'judul'         => 'Seminar Nasional 2025',
            'tahun'         => 2025,
            'deskripsi'     => 'Deskripsi Seminar Nasional',
            'dokumentasi'   => 'https://example.com/doc.pdf',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/organisasi/portofolio/' . $portofolio->id_portofolio);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Detail portofolio berhasil diambil',
                'data'    => [
                    'id_portofolio' => $portofolio->id_portofolio,
                    'id_organisasi' => $org->id_organisasi,
                    'judul'         => 'Seminar Nasional 2025',
                    'tahun'         => 2025,
                    'deskripsi'     => 'Deskripsi Seminar Nasional',
                    'dokumentasi'   => 'https://example.com/doc.pdf',
                ],
            ]);
    }

    public function test_ambil_detail_portofolio_gagal_jika_bukan_milik_organisasi(): void
    {
        [$user1, $org1, $token1] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');
        [$user2, $org2, $token2] = $this->buatAkunOrganisasi('hmj', 'aktif', 'aktif');

        $portofolioOrg2 = Portofolio::create([
            'id_organisasi' => $org2->id_organisasi,
            'judul'         => 'Kegiatan HMJ',
            'tahun'         => 2024,
            'deskripsi'     => 'Deskripsi HMJ',
            'dokumentasi'   => 'https://example.com/hmj.pdf',
        ]);

        // Org 1 mencoba melihat detail portofolio Org 2
        $response = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->getJson('/api/organisasi/portofolio/' . $portofolioOrg2->id_portofolio);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Portofolio tidak ditemukan',
            ]);
    }

    public function test_ambil_detail_portofolio_gagal_jika_id_tidak_ditemukan(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/organisasi/portofolio/999999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Portofolio tidak ditemukan',
            ]);
    }

    public function test_ubah_portofolio_berhasil_dengan_dokumentasi_string(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');

        $portofolio = Portofolio::create([
            'id_organisasi' => $org->id_organisasi,
            'judul'         => 'Judul Lama',
            'tahun'         => 2022,
            'deskripsi'     => 'Deskripsi Lama',
            'dokumentasi'   => 'https://example.com/lama.pdf',
        ]);

        $payload = [
            'judul'       => 'Judul Diperbarui',
            'tahun'       => 2025,
            'deskripsi'   => 'Deskripsi baru yang sudah diperbarui',
            'dokumentasi' => 'https://example.com/baru.pdf',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/organisasi/portofolio/' . $portofolio->id_portofolio, $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Portofolio berhasil diperbarui',
            ])
            ->assertJsonPath('data.0.judul', 'Judul Diperbarui')
            ->assertJsonPath('data.0.tahun', 2025)
            ->assertJsonPath('data.0.deskripsi', 'Deskripsi baru yang sudah diperbarui')
            ->assertJsonPath('data.0.dokumentasi', 'https://example.com/baru.pdf');

        $this->assertDatabaseHas('portofolio', [
            'id_portofolio' => $portofolio->id_portofolio,
            'id_organisasi' => $org->id_organisasi,
            'judul'         => 'Judul Diperbarui',
            'tahun'         => 2025,
            'deskripsi'     => 'Deskripsi baru yang sudah diperbarui',
            'dokumentasi'   => 'https://example.com/baru.pdf',
        ]);
    }

    public function test_ubah_portofolio_berhasil_dengan_file_dokumentasi(): void
    {
        Storage::fake('public');

        [$user, $org, $token] = $this->buatAkunOrganisasi('hmj', 'aktif', 'aktif');

        $portofolio = Portofolio::create([
            'id_organisasi' => $org->id_organisasi,
            'judul'         => 'Pameran Awal',
            'tahun'         => 2023,
            'deskripsi'     => 'Deskripsi pameran awal',
            'dokumentasi'   => 'https://example.com/old.pdf',
        ]);

        $file = UploadedFile::fake()->create('laporan-revisi.pdf', 1000, 'application/pdf');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->post('/api/organisasi/portofolio/' . $portofolio->id_portofolio, [
                '_method'     => 'PUT',
                'judul'       => 'Pameran Hasil Revisi',
                'tahun'       => 2024,
                'deskripsi'   => 'Deskripsi pameran hasil revisi.',
                'dokumentasi' => $file,
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Portofolio berhasil diperbarui',
            ]);

        $this->assertDatabaseHas('portofolio', [
            'id_portofolio' => $portofolio->id_portofolio,
            'id_organisasi' => $org->id_organisasi,
            'judul'         => 'Pameran Hasil Revisi',
            'tahun'         => 2024,
        ]);

        $portofolioUpdated = Portofolio::find($portofolio->id_portofolio);
        $this->assertNotEquals('https://example.com/old.pdf', $portofolioUpdated->dokumentasi);
    }

    public function test_ubah_portofolio_gagal_jika_field_wajib_kosong(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');

        $portofolio = Portofolio::create([
            'id_organisasi' => $org->id_organisasi,
            'judul'         => 'Judul Asli',
            'tahun'         => 2023,
            'deskripsi'     => 'Deskripsi Asli',
            'dokumentasi'   => 'https://example.com/doc.pdf',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/organisasi/portofolio/' . $portofolio->id_portofolio, []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid',
            ])
            ->assertJsonValidationErrors(['judul', 'tahun', 'deskripsi', 'dokumentasi']);
    }

    public function test_ubah_portofolio_gagal_jika_tahun_tidak_valid(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');

        $portofolio = Portofolio::create([
            'id_organisasi' => $org->id_organisasi,
            'judul'         => 'Judul Asli',
            'tahun'         => 2023,
            'deskripsi'     => 'Deskripsi Asli',
            'dokumentasi'   => 'https://example.com/doc.pdf',
        ]);

        // Tahun di bawah min (1999)
        $responseMin = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/organisasi/portofolio/' . $portofolio->id_portofolio, [
                'judul'       => 'Judul Valid',
                'tahun'       => 1999,
                'deskripsi'   => 'Deskripsi Valid',
                'dokumentasi' => 'https://example.com/doc.pdf',
            ]);

        $responseMin->assertStatus(422)
            ->assertJsonValidationErrors(['tahun']);

        // Tahun di atas max (2101)
        $responseMax = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/organisasi/portofolio/' . $portofolio->id_portofolio, [
                'judul'       => 'Judul Valid',
                'tahun'       => 2101,
                'deskripsi'   => 'Deskripsi Valid',
                'dokumentasi' => 'https://example.com/doc.pdf',
            ]);

        $responseMax->assertStatus(422)
            ->assertJsonValidationErrors(['tahun']);
    }

    public function test_ubah_portofolio_gagal_jika_bukan_milik_organisasi(): void
    {
        [$user1, $org1, $token1] = $this->buatAkunOrganisasi('bem', 'aktif', 'aktif');
        [$user2, $org2, $token2] = $this->buatAkunOrganisasi('hmj', 'aktif', 'aktif');

        $portofolioOrg2 = Portofolio::create([
            'id_organisasi' => $org2->id_organisasi,
            'judul'         => 'Portofolio Milik HMJ',
            'tahun'         => 2023,
            'deskripsi'     => 'Deskripsi HMJ',
            'dokumentasi'   => 'https://example.com/hmj.pdf',
        ]);

        // Org 1 mencoba mengubah portofolio milik Org 2 (pencegahan IDOR)
        $response = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->putJson('/api/organisasi/portofolio/' . $portofolioOrg2->id_portofolio, [
                'judul'       => 'Coba Bajak Judul',
                'tahun'       => 2025,
                'deskripsi'   => 'Deskripsi Bajakan',
                'dokumentasi' => 'https://example.com/bajak.pdf',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Portofolio tidak ditemukan',
            ]);

        // Pastikan data di database tidak berubah
        $this->assertDatabaseHas('portofolio', [
            'id_portofolio' => $portofolioOrg2->id_portofolio,
            'judul'         => 'Portofolio Milik HMJ',
        ]);
    }

    public function test_ubah_portofolio_gagal_tanpa_token(): void
    {
        $response = $this->putJson('/api/organisasi/portofolio/1', [
            'judul'       => 'Judul Portofolio',
            'tahun'       => 2024,
            'deskripsi'   => 'Deskripsi',
            'dokumentasi' => 'https://example.com/doc.jpg',
        ]);

        $response->assertStatus(401);
    }

    public function test_ubah_portofolio_gagal_jika_peran_bukan_akun_organisasi(): void
    {
        $anggota = Pengguna::factory()->create([
            'peran'       => 'anggota',
            'status_akun' => 'aktif',
        ]);
        $tokenAnggota = $anggota->createToken('auth_token', ['*'])->plainTextToken;

        $responseAnggota = $this->withHeader('Authorization', 'Bearer ' . $tokenAnggota)
            ->putJson('/api/organisasi/portofolio/1', [
                'judul'       => 'Judul Anggota',
                'tahun'       => 2024,
                'deskripsi'   => 'Deskripsi',
                'dokumentasi' => 'https://example.com/doc.jpg',
            ]);

        $responseAnggota->assertStatus(403);
    }

    public function test_ubah_portofolio_mengembalikan_seluruh_portofolio_terurut_tahun_desc(): void
    {
        [$user, $org, $token] = $this->buatAkunOrganisasi('ukm', 'aktif', 'aktif');

        // Buat 3 portofolio awal: 2021, 2022, 2024
        Portofolio::create([
            'id_organisasi' => $org->id_organisasi,
            'judul'         => 'Portofolio 2021',
            'tahun'         => 2021,
            'deskripsi'     => 'Deskripsi 2021',
            'dokumentasi'   => 'https://example.com/2021.pdf',
        ]);

        $portofolioToUpdate = Portofolio::create([
            'id_organisasi' => $org->id_organisasi,
            'judul'         => 'Portofolio 2022',
            'tahun'         => 2022,
            'deskripsi'     => 'Deskripsi 2022',
            'dokumentasi'   => 'https://example.com/2022.pdf',
        ]);

        Portofolio::create([
            'id_organisasi' => $org->id_organisasi,
            'judul'         => 'Portofolio 2024',
            'tahun'         => 2024,
            'deskripsi'     => 'Deskripsi 2024',
            'dokumentasi'   => 'https://example.com/2024.pdf',
        ]);

        // Ubah portofolio tahun 2022 menjadi tahun 2025
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/organisasi/portofolio/' . $portofolioToUpdate->id_portofolio, [
                'judul'       => 'Portofolio Diperbarui Jadi 2025',
                'tahun'       => 2025,
                'deskripsi'   => 'Deskripsi revisi',
                'dokumentasi' => 'https://example.com/2025.pdf',
            ]);

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(3, $data);
        // Pastikan terurut DESC: 2025, 2024, 2021
        $this->assertEquals(2025, $data[0]['tahun']);
        $this->assertEquals('Portofolio Diperbarui Jadi 2025', $data[0]['judul']);
        $this->assertEquals(2024, $data[1]['tahun']);
        $this->assertEquals(2021, $data[2]['tahun']);
    }
}
