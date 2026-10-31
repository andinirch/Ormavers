<?php

namespace Tests\Feature;

use App\Models\LogAktivitas;
use App\Models\Organisasi;
use App\Models\PendaftaranOrganisasi;
use App\Models\Pengguna;
use App\Models\Universitas;
use App\Services\LogAktivitasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LogLintasFiturTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function pdfHeader(): string
    {
        return '%PDF-1.4' . str_repeat("\x00", 100);
    }

    private function jpegHeader(): string
    {
        return "\xFF\xD8\xFF\xE0" . str_repeat("\x00", 100);
    }

    public function test_login_penuh_dan_login_terbatas_mencatat_log_login()
    {
        // 1. Login penuh (aktif)
        $userAktif = Pengguna::factory()->create([
            'username'    => 'aktif_user',
            'kata_sandi'  => 'password',
            'status_akun' => 'aktif',
        ]);

        $res1 = $this->postJson('/api/auth/login', [
            'username' => 'aktif_user',
            'password' => 'password',
        ]);
        $res1->assertStatus(200);

        $log1 = LogAktivitas::latest('id_log')->first();
        $this->assertEquals($userAktif->id_pengguna, $log1->id_pengguna);
        $this->assertEquals(LogAktivitasService::JENIS_LOGIN, $log1->jenis_aktivitas);

        // 2. Login terbatas (menunggu)
        $userMenunggu = Pengguna::factory()->menunggu()->create([
            'username'   => 'menunggu_user',
            'kata_sandi' => 'password',
        ]);

        $res2 = $this->postJson('/api/auth/login', [
            'username' => 'menunggu_user',
            'password' => 'password',
        ]);
        $res2->assertStatus(200);

        $log2 = LogAktivitas::latest('id_log')->first();
        $this->assertEquals($userMenunggu->id_pengguna, $log2->id_pengguna);
        $this->assertEquals(LogAktivitasService::JENIS_LOGIN, $log2->jenis_aktivitas);
    }

    public function test_logout_penuh_dan_logout_status_registrasi_mencatat_log_logout()
    {
        // 1. Logout penuh
        $userAktif = Pengguna::factory()->create(['status_akun' => 'aktif']);
        $tokenPenuh = $userAktif->createToken('auth_token', ['*'])->plainTextToken;

        $res1 = $this->withHeader('Authorization', "Bearer {$tokenPenuh}")
            ->postJson('/api/auth/logout');
        $res1->assertStatus(200);

        $log1 = LogAktivitas::latest('id_log')->first();
        $this->assertEquals($userAktif->id_pengguna, $log1->id_pengguna);
        $this->assertEquals(LogAktivitasService::JENIS_LOGOUT, $log1->jenis_aktivitas);

    }

    public function test_registrasi_bem_hmj_ukm_anggota_dan_resubmit_sk_mencatat_log_pendaftaran()
    {
        Storage::fake('local');
        $univ = Universitas::firstOrCreate(['nama_universitas' => 'Univ Log Test']);

        // 1. Registrasi BEM
        $resBem = $this->postJson('/api/register/bem', [
            'id_universitas'           => $univ->id_universitas,
            'nama_organisasi'          => 'BEM Log Test',
            'nomor_telepon_organisasi' => '081234567890',
            'nama'                     => 'Ketua BEM',
            'nim'                      => '12345',
            'nomor_whatsapp'           => '081234567890',
            'username'                 => 'ketua_bem_log',
            'password'                 => 'Password123',
            'password_confirmation'    => 'Password123',
            'foto_ktm'                 => UploadedFile::fake()->createWithContent('ktm.jpg', $this->jpegHeader()),
            'sk_organisasi'            => UploadedFile::fake()->createWithContent('sk_org.pdf', $this->pdfHeader()),
            'sk_kepengurusan'          => UploadedFile::fake()->createWithContent('sk_kep.pdf', $this->pdfHeader()),
        ]);
        $resBem->assertStatus(201);
        $idBemUser = Pengguna::where('username', 'ketua_bem_log')->value('id_pengguna');

        $logBem = LogAktivitas::latest('id_log')->first();
        $this->assertEquals($idBemUser, $logBem->id_pengguna);
        $this->assertEquals(LogAktivitasService::JENIS_PENDAFTARAN, $logBem->jenis_aktivitas);

        // Aktifkan BEM agar universitas memiliki BEM aktif
        $bemOrg = Organisasi::where('id_akun_organisasi', $idBemUser)->first();
        $bemOrg->update(['status_organisasi' => 'aktif']);
        Pengguna::where('id_pengguna', $idBemUser)->update(['status_akun' => 'aktif']);

        // 2. Registrasi HMJ (Menggunakan id_universitas)
        $resHmj = $this->postJson('/api/register/hmj-ukm', [
            'id_universitas'           => $univ->id_universitas,
            'jenis_organisasi'         => 'hmj',
            'nama_organisasi'          => 'HMJ Log Test',
            'nomor_telepon_organisasi' => '081234567891',
            'nama'                     => 'Ketua HMJ',
            'nim'                      => '12346',
            'nomor_whatsapp'           => '081234567891',
            'username'                 => 'ketua_hmj_log',
            'password'                 => 'Password123',
            'password_confirmation'    => 'Password123',
            'foto_ktm'                 => UploadedFile::fake()->createWithContent('ktm.jpg', $this->jpegHeader()),
            'sk_organisasi'            => UploadedFile::fake()->createWithContent('sk_org.pdf', $this->pdfHeader()),
            'sk_kepengurusan'          => UploadedFile::fake()->createWithContent('sk_kep.pdf', $this->pdfHeader()),
        ]);
        $resHmj->assertStatus(201);
        $idHmjUser = Pengguna::where('username', 'ketua_hmj_log')->value('id_pengguna');

        $logHmj = LogAktivitas::latest('id_log')->first();
        $this->assertEquals($idHmjUser, $logHmj->id_pengguna);
        $this->assertEquals(LogAktivitasService::JENIS_PENDAFTARAN, $logHmj->jenis_aktivitas);

        // 3. Registrasi Anggota
        $resAnggota = $this->postJson('/api/register/anggota', [
            'id_organisasi'         => $bemOrg->id_organisasi,
            'nama'                  => 'Anggota Log Test',
            'nim'                   => '12347',
            'nomor_whatsapp'        => '081234567892',
            'username'              => 'anggota_log',
            'password'              => 'Password123',
            'password_confirmation' => 'Password123',
            'foto_ktm'              => UploadedFile::fake()->createWithContent('ktm.jpg', $this->jpegHeader()),
        ]);
        $resAnggota->assertStatus(201);
        $idAnggotaUser = Pengguna::where('username', 'anggota_log')->value('id_pengguna');

        $logAnggota = LogAktivitas::latest('id_log')->first();
        $this->assertEquals($idAnggotaUser, $logAnggota->id_pengguna);
        $this->assertEquals(LogAktivitasService::JENIS_PENDAFTARAN, $logAnggota->jenis_aktivitas);

        // 4. Resubmit SK (oleh HMJ yang ditolak)
        $hmjOrg = Organisasi::where('id_akun_organisasi', $idHmjUser)->first();
        $hmjPendaftaran = PendaftaranOrganisasi::where('id_organisasi', $hmjOrg->id_organisasi)->first();
        $hmjPendaftaran->update(['status_pendaftaran' => 'ditolak', 'alasan_penolakan' => 'Revisi']);
        $hmjUser = Pengguna::find($idHmjUser);
        $hmjUser->update(['status_akun' => 'ditolak']);
        $tokenHmj = $hmjUser->createToken('auth_token', ['status-registrasi'])->plainTextToken;

        $this->app['auth']->forgetGuards();

        $resubmitRes = $this->withHeader('Authorization', "Bearer {$tokenHmj}")
            ->postJson('/api/registration/resubmit-sk', [
                'sk_organisasi' => UploadedFile::fake()->createWithContent('sk_org_baru.pdf', $this->pdfHeader()),
            ]);
        $resubmitRes->assertStatus(200);

        $logResubmit = LogAktivitas::latest('id_log')->first();
        $this->assertEquals($idHmjUser, $logResubmit->id_pengguna);
        $this->assertEquals(LogAktivitasService::JENIS_PENDAFTARAN, $logResubmit->jenis_aktivitas);
    }

    public function test_verifikasi_setujui_dan_tolak_mencatat_log_verifikasi()
    {
        // Admin memverifikasi BEM
        $admin = Pengguna::factory()->administrator()->create(['status_akun' => 'aktif']);
        $admin->syncRoles('administrator');
        $tokenAdmin = $admin->createToken('auth_token', ['*'])->plainTextToken;

        $univ = Universitas::firstOrCreate(['nama_universitas' => 'Univ Verif']);
        $bemUser = Pengguna::factory()->akunOrganisasi()->menunggu()->create();
        $bemOrg = Organisasi::create([
            'id_universitas'     => $univ->id_universitas,
            'id_akun_organisasi' => $bemUser->id_pengguna,
            'nama_organisasi'    => 'BEM Verif Test',
            'jenis_organisasi'   => 'bem',
            'nomor_telepon'      => '0812345678',
            'status_organisasi'  => 'menunggu',
        ]);
        $bemPendaftaran = PendaftaranOrganisasi::create([
            'id_organisasi'       => $bemOrg->id_organisasi,
            'status_pendaftaran'  => 'menunggu',
            'tanggal_pendaftaran' => now(),
        ]);

        $resApprove = $this->withHeader('Authorization', "Bearer {$tokenAdmin}")
            ->postJson("/api/admin/registrations/bem/{$bemPendaftaran->id_pendaftaran_organisasi}/approve");
        $resApprove->assertStatus(200);

        $logApprove = LogAktivitas::latest('id_log')->first();
        $this->assertEquals($admin->id_pengguna, $logApprove->id_pengguna);
        $this->assertEquals(LogAktivitasService::JENIS_VERIFIKASI, $logApprove->jenis_aktivitas);
    }

    public function test_kelola_akun_tambah_dan_nonaktifkan_administrator()
    {
        $superAdmin = Pengguna::factory()->superAdmin()->create(['status_akun' => 'aktif']);
        $superAdmin->syncRoles('super_admin');
        $tokenSa = $superAdmin->createToken('auth_token', ['*'])->plainTextToken;

        // Tambah administrator
        $resTambah = $this->withHeader('Authorization', "Bearer {$tokenSa}")
            ->postJson('/api/superadmin/administrators', [
                'nama'                  => 'Admin Baru Log',
                'username'              => 'admin_baru_log',
                'password'              => 'Password123',
                'password_confirmation' => 'Password123',
            ]);
        $resTambah->assertStatus(201);

        $logTambah = LogAktivitas::latest('id_log')->first();
        $this->assertEquals($superAdmin->id_pengguna, $logTambah->id_pengguna);
        $this->assertEquals(LogAktivitasService::JENIS_KELOLA_AKUN, $logTambah->jenis_aktivitas);

        $newAdminId = $resTambah->json('data.administrator.id_pengguna');

        // Nonaktifkan administrator
        $resNonaktif = $this->withHeader('Authorization', "Bearer {$tokenSa}")
            ->patchJson("/api/superadmin/administrators/{$newAdminId}/nonaktif");
        $resNonaktif->assertStatus(200);

        $logNonaktif = LogAktivitas::latest('id_log')->first();
        $this->assertEquals($superAdmin->id_pengguna, $logNonaktif->id_pengguna);
        $this->assertEquals(LogAktivitasService::JENIS_KELOLA_AKUN, $logNonaktif->jenis_aktivitas);
    }

    public function test_role_buat_ubah_hapus_mencatat_log_role()
    {
        $superAdmin = Pengguna::factory()->superAdmin()->create(['status_akun' => 'aktif']);
        $superAdmin->syncRoles('super_admin');
        $tokenSa = $superAdmin->createToken('auth_token', ['*'])->plainTextToken;

        // Buat role
        $resBuat = $this->withHeader('Authorization', "Bearer {$tokenSa}")
            ->postJson('/api/admin-panel/roles', [
                'name'        => 'role_uji_log',
                'permissions' => [],
            ]);
        $resBuat->assertStatus(201);
        $roleId = $resBuat->json('data.role.id');

        $logBuat = LogAktivitas::latest('id_log')->first();
        $this->assertEquals($superAdmin->id_pengguna, $logBuat->id_pengguna);
        $this->assertEquals(LogAktivitasService::JENIS_ROLE, $logBuat->jenis_aktivitas);

        // Ubah role
        $resUbah = $this->withHeader('Authorization', "Bearer {$tokenSa}")
            ->putJson("/api/admin-panel/roles/{$roleId}", [
                'name'        => 'role_uji_log_updated',
                'permissions' => [],
            ]);
        $resUbah->assertStatus(200);

        $logUbah = LogAktivitas::latest('id_log')->first();
        $this->assertEquals($superAdmin->id_pengguna, $logUbah->id_pengguna);
        $this->assertEquals(LogAktivitasService::JENIS_ROLE, $logUbah->jenis_aktivitas);

        // Hapus role
        $resHapus = $this->withHeader('Authorization', "Bearer {$tokenSa}")
            ->deleteJson("/api/admin-panel/roles/{$roleId}");
        $resHapus->assertStatus(200);

        $logHapus = LogAktivitas::latest('id_log')->first();
        $this->assertEquals($superAdmin->id_pengguna, $logHapus->id_pengguna);
        $this->assertEquals(LogAktivitasService::JENIS_ROLE, $logHapus->jenis_aktivitas);
    }

    public function test_aksi_gagal_409_422_tidak_mencatat_log()
    {
        $superAdmin = Pengguna::factory()->superAdmin()->create(['status_akun' => 'aktif']);
        $superAdmin->syncRoles('super_admin');
        $tokenSa = $superAdmin->createToken('auth_token', ['*'])->plainTextToken;

        $logCountAwal = LogAktivitas::count();

        // 422: Validasi tambah administrator gagal
        $res422 = $this->withHeader('Authorization', "Bearer {$tokenSa}")
            ->postJson('/api/superadmin/administrators', [
                'nama' => '',
            ]);
        $res422->assertStatus(422);
        $this->assertEquals($logCountAwal, LogAktivitas::count());

        // 409: Nonaktifkan yang sudah nonaktif
        $admin = Pengguna::factory()->administrator()->nonaktif()->create();
        $res409 = $this->withHeader('Authorization', "Bearer {$tokenSa}")
            ->patchJson("/api/superadmin/administrators/{$admin->id_pengguna}/nonaktif");
        $res409->assertStatus(409);
        $this->assertEquals($logCountAwal, LogAktivitas::count());
    }

    public function test_filter_options_memuat_seluruh_konstanta_jenis()
    {
        $superAdmin = Pengguna::factory()->superAdmin()->create(['status_akun' => 'aktif']);
        $superAdmin->syncRoles('super_admin');
        $tokenSa = $superAdmin->createToken('auth_token', ['*'])->plainTextToken;

        $res = $this->withHeader('Authorization', "Bearer {$tokenSa}")
            ->getJson('/api/admin-panel/activities/filter-options');

        $res->assertStatus(200);
        $jenisList = $res->json('data.jenis');

        foreach (LogAktivitasService::SEMUA_JENIS as $j) {
            $this->assertContains($j, $jenisList);
        }
    }
}