<?php

namespace Tests\Feature;

use App\Models\BidangOrganisasi;
use App\Models\Keanggotaan;
use App\Models\Organisasi;
use App\Models\Pengguna;
use App\Models\PendaftaranOrganisasi;
use App\Models\ProfilAnggota;
use App\Models\Universitas;
use Database\Seeders\BidangOrganisasiSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UniversitasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MasterDataDanRefactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(UniversitasSeeder::class);
        $this->seed(BidangOrganisasiSeeder::class);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function jpegHeader(): string
    {
        return "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01\x00`\x00`\x00\x00";
    }

    private function pdfHeader(): string
    {
        return "%PDF-1.4\n1 0 obj\n<<\n/Type /Catalog\n>>\nendobj\ntrailer\n<<\n>>\n%%EOF";
    }

    public function test_endpoint_master_universitas_mengembalikan_200_ok(): void
    {
        $response = $this->getJson('/api/master/universitas');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id_universitas', 'nama_universitas', 'logo'],
                ],
            ]);

        $this->assertGreaterThanOrEqual(5, count($response->json('data')));
    }

    public function test_endpoint_master_bidang_organisasi_mengembalikan_200_ok_dengan_8_bidang(): void
    {
        $response = $this->getJson('/api/master/bidang-organisasi');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id_bidang', 'nama_bidang'],
                ],
            ]);

        $this->assertCount(8, $response->json('data'));
    }

    public function test_pendaftaran_bem_dengan_id_organisasi_induk_null_dan_id_bidang(): void
    {
        Storage::fake('local');
        $univ = Universitas::first();
        $bidang = BidangOrganisasi::first();

        $response = $this->postJson('/api/register/bem', [
            'id_universitas'           => $univ->id_universitas,
            'id_bidang'                => $bidang->id_bidang,
            'nama_organisasi'          => 'BEM Fakultas Baru',
            'nomor_telepon_organisasi' => '081234567890',
            'nama'                     => 'Ketua BEM Terpilih',
            'nim'                      => '20269999',
            'nomor_whatsapp'           => '081234567890',
            'username'                 => 'ketua_bem_baru',
            'password'                 => 'PasswordKuat123',
            'password_confirmation'    => 'PasswordKuat123',
            'foto_ktm'                 => UploadedFile::fake()->createWithContent('ktm.jpg', $this->jpegHeader()),
            'sk_organisasi'            => UploadedFile::fake()->createWithContent('sk_org.pdf', $this->pdfHeader()),
            'sk_kepengurusan'          => UploadedFile::fake()->createWithContent('sk_kep.pdf', $this->pdfHeader()),
        ]);

        $response->assertStatus(201);

        // Pastikan akun pengguna hanya berisi kredensial (tanpa nim, whatsapp, ktm di tabel pengguna)
        $this->assertDatabaseHas('pengguna', [
            'username'    => 'ketua_bem_baru',
            'peran'       => 'akun_organisasi',
            'status_akun' => 'menunggu',
        ]);

        $user = Pengguna::where('username', 'ketua_bem_baru')->first();

        // Pastikan detail personal tersimpan di profil_anggota
        $this->assertDatabaseHas('profil_anggota', [
            'id_pengguna'    => $user->id_pengguna,
            'nim'            => '20269999',
            'nomor_whatsapp' => '081234567890',
            'id_universitas' => $univ->id_universitas,
        ]);

        // Pastikan organisasi memiliki id_organisasi_induk NULL dan id_bidang valid
        $this->assertDatabaseHas('organisasi', [
            'id_akun_organisasi'  => $user->id_pengguna,
            'id_organisasi_induk' => null,
            'id_bidang'           => $bidang->id_bidang,
            'jenis_organisasi'    => 'bem',
        ]);
    }

    public function test_penolakan_pendaftaran_organisasi_menghapus_data_dan_file_secara_permanen(): void
    {
        Storage::fake('local');
        $admin = Pengguna::factory()->administrator()->create();
        $adminToken = $admin->createToken('auth', ['*'])->plainTextToken;

        $univ = Universitas::first();
        $this->postJson('/api/register/bem', [
            'id_universitas'           => $univ->id_universitas,
            'nama_organisasi'          => 'BEM Yang Akan Ditolak',
            'nomor_telepon_organisasi' => '081234567891',
            'nama'                     => 'Ketua BEM Ditolak',
            'nim'                      => '20268888',
            'nomor_whatsapp'           => '081234567891',
            'username'                 => 'ketua_bem_ditolak',
            'password'                 => 'PasswordKuat123',
            'password_confirmation'    => 'PasswordKuat123',
            'foto_ktm'                 => UploadedFile::fake()->createWithContent('ktm.jpg', $this->jpegHeader()),
            'sk_organisasi'            => UploadedFile::fake()->createWithContent('sk_org.pdf', $this->pdfHeader()),
            'sk_kepengurusan'          => UploadedFile::fake()->createWithContent('sk_kep.pdf', $this->pdfHeader()),
        ])->assertStatus(201);

        $user = Pengguna::where('username', 'ketua_bem_ditolak')->first();
        $org = Organisasi::where('id_akun_organisasi', $user->id_pengguna)->first();
        $pend = PendaftaranOrganisasi::where('id_organisasi', $org->id_organisasi)->first();

        $idPend = $pend->id_pendaftaran_organisasi;
        $idOrg  = $org->id_organisasi;
        $idUser = $user->id_pengguna;

        // Admin tolak pendaftaran
        $resTolak = $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->postJson("/api/admin/registrations/bem/{$idPend}/reject", [
                'alasan' => 'Dokumen tidak valid sama sekali',
            ]);

        $resTolak->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Pendaftaran ditolak dan data dibersihkan secara permanen.',
            ]);

        // Pastikan hard delete bersih di seluruh tabel
        $this->assertDatabaseMissing('pendaftaran_organisasi', ['id_pendaftaran_organisasi' => $idPend]);
        $this->assertDatabaseMissing('pendaftaran_organisasi_file', ['id_pendaftaran_organisasi' => $idPend]);
        $this->assertDatabaseMissing('organisasi', ['id_organisasi' => $idOrg]);
        $this->assertDatabaseMissing('profil_anggota', ['id_pengguna' => $idUser]);
        $this->assertDatabaseMissing('pengguna', ['id_pengguna' => $idUser]);
    }

    public function test_penolakan_keanggotaan_biasa_hanya_menghapus_record_keanggotaan(): void
    {
        Storage::fake('local');

        // BEM aktif
        $bemUser = Pengguna::factory()->akunOrganisasi()->create();
        $bemUser->syncRoles('akun_bem');
        $bemToken = $bemUser->createToken('auth', ['*'])->plainTextToken;

        $univ = Universitas::first();
        $bemOrg = Organisasi::create([
            'id_universitas'     => $univ->id_universitas,
            'id_organisasi_induk'=> null,
            'id_akun_organisasi' => $bemUser->id_pengguna,
            'nama_organisasi'    => 'BEM Pusat',
            'jenis_organisasi'   => 'bem',
            'nomor_telepon'      => '081234567890',
            'status_organisasi'  => 'aktif',
        ]);

        // Anggota mendaftar
        $this->postJson('/api/register/anggota', [
            'id_organisasi'         => $bemOrg->id_organisasi,
            'nama'                  => 'Mahasiswa Biasa',
            'nim'                   => 'MHS12345',
            'username'              => 'mhs_biasa_1',
            'password'              => 'PasswordKuat123',
            'password_confirmation' => 'PasswordKuat123',
            'foto_ktm'              => UploadedFile::fake()->createWithContent('ktm.jpg', $this->jpegHeader()),
        ])->assertStatus(201);

        $anggotaUser = Pengguna::where('username', 'mhs_biasa_1')->first();
        $keanggotaan = Keanggotaan::where('id_pengguna', $anggotaUser->id_pengguna)->first();
        $idKeanggotaan = $keanggotaan->id_keanggotaan;

        // BEM menolak keanggotaan
        $this->withHeader('Authorization', "Bearer {$bemToken}")
            ->postJson("/api/org/member-requests/{$idKeanggotaan}/reject", [
                'alasan' => 'Bukan mahasiswa jurusan terkait',
            ])->assertStatus(200);

        // Hanya record keanggotaan yang terhapus
        $this->assertDatabaseMissing('keanggotaan', [
            'id_keanggotaan' => $idKeanggotaan,
        ]);

        // Akun pengguna dan profil anggota TETAP AMAN dan ADA
        $this->assertDatabaseHas('pengguna', [
            'id_pengguna' => $anggotaUser->id_pengguna,
            'username'    => 'mhs_biasa_1',
        ]);
        $this->assertDatabaseHas('profil_anggota', [
            'id_pengguna' => $anggotaUser->id_pengguna,
            'nim'         => 'MHS12345',
        ]);
    }
}
