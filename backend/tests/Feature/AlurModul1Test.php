<?php

namespace Tests\Feature;

use App\Models\Keanggotaan;
use App\Models\LogAktivitas;
use App\Models\Organisasi;
use App\Models\PendaftaranOrganisasi;
use App\Models\Pengguna;
use App\Models\Universitas;
use App\Services\LogAktivitasService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UniversitasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AlurModul1Test extends TestCase
{
    use RefreshDatabase;

    private function pdfHeader(): string
    {
        return '%PDF-1.4' . str_repeat("\x00", 100);
    }

    private function jpegHeader(): string
    {
        return "\xFF\xD8\xFF\xE0" . str_repeat("\x00", 100);
    }

    public function test_alur_lengkap_end_to_end_modul_1()
    {
        // 0. Setup Seeders & Fake Storage
        $this->seed(UniversitasSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
        Storage::fake('public');

        // Step 1: Super Admin login dan membuat Administrator
        $superAdmin = Pengguna::factory()->superAdmin()->create([
            'username'    => 'superadmin',
            'kata_sandi'  => 'SuperPassword123',
            'status_akun' => 'aktif',
        ]);
        $superAdmin->syncRoles('super_admin');
        $tokenSa = $superAdmin->createToken('auth_token', ['*'])->plainTextToken;

        $resAdmin = $this->withHeader('Authorization', "Bearer {$tokenSa}")
            ->postJson('/api/superadmin/administrators', [
                'nama'                  => 'Admin Satu',
                'username'              => 'admin_satu',
                'password'              => 'AdminPassword123',
                'password_confirmation' => 'AdminPassword123',
            ]);
        $resAdmin->assertStatus(201);
        $adminId = $resAdmin->json('data.administrator.id_pengguna');
        $admin = Pengguna::find($adminId);
        $this->assertEquals('aktif', $admin->status_akun);
        $this->assertEquals('administrator', $admin->peran);
        $this->assertTrue($admin->hasRole('administrator'));

        // Step 2: BEM mendaftar via /api/register/bem
        $univ = Universitas::first();
        $this->assertNotNull($univ);

        $resBemReg = $this->postJson('/api/register/bem', [
            'id_universitas'           => $univ->id_universitas,
            'nama_organisasi'          => 'BEM Universitas Utama',
            'nomor_telepon_organisasi' => '08111111111',
            'nama'                     => 'Ketua BEM Utama',
            'nim'                      => 'BEM001',
            'nomor_whatsapp'           => '08111111111',
            'username'                 => 'ketua_bem_utama',
            'password'                 => 'BemPassword123',
            'password_confirmation'    => 'BemPassword123',
            'foto_ktm'                 => UploadedFile::fake()->createWithContent('ktm_bem.jpg', $this->jpegHeader()),
            'sk_organisasi'            => UploadedFile::fake()->createWithContent('sk_bem.pdf', $this->pdfHeader()),
            'sk_kepengurusan'          => UploadedFile::fake()->createWithContent('sk_kep_bem.pdf', $this->pdfHeader()),
        ]);
        $resBemReg->assertStatus(201);
        $bemUser = Pengguna::where('username', 'ketua_bem_utama')->first();
        $this->assertEquals('menunggu', $bemUser->status_akun);
        $this->assertEquals('akun_organisasi', $bemUser->peran);
        $this->assertTrue($bemUser->hasRole('akun_bem'));

        $bemOrg = Organisasi::where('id_akun_organisasi', $bemUser->id_pengguna)->first();
        $this->assertEquals('menunggu', $bemOrg->status_organisasi);

        $bemPendaftaran = PendaftaranOrganisasi::where('id_organisasi', $bemOrg->id_organisasi)->first();
        $this->assertEquals('menunggu', $bemPendaftaran->status_pendaftaran);

        // BEM login terbatas
        $resBemLoginTerbatas = $this->postJson('/api/auth/login', [
            'username' => 'ketua_bem_utama',
            'password' => 'BemPassword123',
        ]);
        $resBemLoginTerbatas->assertStatus(200)
            ->assertJsonPath('data.akses_terbatas', true);
        $tokenBemTerbatas = $resBemLoginTerbatas->json('data.token');

        // BEM cek status registrasi
        $this->app['auth']->forgetGuards();
        $resBemStatus = $this->withHeader('Authorization', "Bearer {$tokenBemTerbatas}")
            ->getJson('/api/registration/status');
        $resBemStatus->assertStatus(200)
            ->assertJsonPath('data.status_pendaftaran', 'menunggu');

        // Step 3: Administrator login dan menyetujui BEM
        $resAdminLogin = $this->postJson('/api/auth/login', [
            'username' => 'admin_satu',
            'password' => 'AdminPassword123',
        ]);
        $resAdminLogin->assertStatus(200);
        $tokenAdmin = $resAdminLogin->json('data.token');

        $this->app['auth']->forgetGuards();
        $resApproveBem = $this->withHeader('Authorization', "Bearer {$tokenAdmin}")
            ->postJson("/api/admin/registrations/bem/{$bemPendaftaran->id_pendaftaran_organisasi}/approve");
        $resApproveBem->assertStatus(200);

        // Periksa data BEM setelah disetujui
        $bemUser->refresh();
        $bemOrg->refresh();
        $bemPendaftaran->refresh();
        $this->assertEquals('aktif', $bemUser->status_akun);
        $this->assertEquals('aktif', $bemOrg->status_organisasi);
        $this->assertEquals('disetujui', $bemPendaftaran->status_pendaftaran);
        // Token lama harus sudah tercabut
        $this->assertCount(0, $bemUser->tokens()->get());

        // Step 4: BEM login penuh
        $resBemLoginPenuh = $this->postJson('/api/auth/login', [
            'username' => 'ketua_bem_utama',
            'password' => 'BemPassword123',
        ]);
        $resBemLoginPenuh->assertStatus(200)
            ->assertJsonMissing(['akses_terbatas' => true]);
        $tokenBemPenuh = $resBemLoginPenuh->json('data.token');

        // Step 5: HMJ/UKM mendaftar ke BEM aktif
        $resHmjReg = $this->postJson('/api/register/hmj-ukm', [
            'id_organisasi_induk'      => $bemOrg->id_organisasi,
            'jenis_organisasi'         => 'hmj',
            'nama_organisasi'          => 'HMJ Teknik Informatika',
            'nomor_telepon_organisasi' => '08222222222',
            'nama'                     => 'Ketua HMJ TI',
            'nim'                      => 'HMJ001',
            'nomor_whatsapp'           => '08222222222',
            'username'                 => 'ketua_hmj_ti',
            'password'                 => 'HmjPassword123',
            'password_confirmation'    => 'HmjPassword123',
            'foto_ktm'                 => UploadedFile::fake()->createWithContent('ktm_hmj.jpg', $this->jpegHeader()),
            'sk_organisasi'            => UploadedFile::fake()->createWithContent('sk_hmj.pdf', $this->pdfHeader()),
            'sk_kepengurusan'          => UploadedFile::fake()->createWithContent('sk_kep_hmj.pdf', $this->pdfHeader()),
        ]);
        $resHmjReg->assertStatus(201);
        $hmjUser = Pengguna::where('username', 'ketua_hmj_ti')->first();
        $this->assertEquals('menunggu', $hmjUser->status_akun);
        $this->assertTrue($hmjUser->hasRole('akun_hmj_ukm'));

        $hmjOrg = Organisasi::where('id_akun_organisasi', $hmjUser->id_pengguna)->first();
        $hmjPendaftaran = PendaftaranOrganisasi::where('id_organisasi', $hmjOrg->id_organisasi)->first();
        $this->assertEquals('menunggu', $hmjPendaftaran->status_pendaftaran);

        // Step 6: BEM menolak pendaftaran HMJ dengan alasan
        $this->app['auth']->forgetGuards();
        $resHmjTolak = $this->withHeader('Authorization', "Bearer {$tokenBemPenuh}")
            ->postJson("/api/org/registrations/hmj-ukm/{$hmjPendaftaran->id_pendaftaran_organisasi}/reject", [
                'alasan' => 'SK Kepengurusan tidak ada tanda tangan Dekan',
            ]);
        $resHmjTolak->assertStatus(200);

        $hmjPendaftaran->refresh();
        $hmjUser->refresh();
        $this->assertEquals('ditolak', $hmjPendaftaran->status_pendaftaran);
        $this->assertEquals('SK Kepengurusan tidak ada tanda tangan Dekan', $hmjPendaftaran->alasan_penolakan);
        $this->assertEquals('ditolak', $hmjUser->status_akun);

        // Step 7: Pendaftar HMJ login terbatas, melihat alasan penolakan, dan mengunggah ulang SK
        $resHmjLogin = $this->postJson('/api/auth/login', [
            'username' => 'ketua_hmj_ti',
            'password' => 'HmjPassword123',
        ]);
        $resHmjLogin->assertStatus(200)->assertJsonPath('data.akses_terbatas', true);
        $tokenHmjTerbatas = $resHmjLogin->json('data.token');

        $this->app['auth']->forgetGuards();
        $resHmjCheckStatus = $this->withHeader('Authorization', "Bearer {$tokenHmjTerbatas}")
            ->getJson('/api/registration/status');
        $resHmjCheckStatus->assertStatus(200)
            ->assertJsonPath('data.status_pendaftaran', 'ditolak')
            ->assertJsonPath('data.alasan_penolakan', 'SK Kepengurusan tidak ada tanda tangan Dekan')
            ->assertJsonPath('data.bisa_unggah_ulang', true);

        // Unggah ulang SK kepengurusan yang valid
        $resResubmit = $this->withHeader('Authorization', "Bearer {$tokenHmjTerbatas}")
            ->postJson('/api/registration/resubmit-sk', [
                'sk_kepengurusan' => UploadedFile::fake()->createWithContent('sk_kep_revisi.pdf', $this->pdfHeader()),
            ]);
        $resResubmit->assertStatus(200);

        $hmjPendaftaran->refresh();
        $hmjUser->refresh();
        $this->assertEquals('menunggu', $hmjPendaftaran->status_pendaftaran);
        $this->assertNull($hmjPendaftaran->alasan_penolakan);
        $this->assertEquals('menunggu', $hmjUser->status_akun);

        // Step 8: BEM menyetujui HMJ
        $this->app['auth']->forgetGuards();
        $resHmjApprove = $this->withHeader('Authorization', "Bearer {$tokenBemPenuh}")
            ->postJson("/api/org/registrations/hmj-ukm/{$hmjPendaftaran->id_pendaftaran_organisasi}/approve");
        $resHmjApprove->assertStatus(200);

        $hmjPendaftaran->refresh();
        $hmjOrg->refresh();
        $hmjUser->refresh();
        $this->assertEquals('disetujui', $hmjPendaftaran->status_pendaftaran);
        $this->assertEquals('aktif', $hmjOrg->status_organisasi);
        $this->assertEquals('aktif', $hmjUser->status_akun);
        $this->assertCount(0, $hmjUser->tokens()->get());

        // Step 9: HMJ login penuh
        $resHmjLoginPenuh = $this->postJson('/api/auth/login', [
            'username' => 'ketua_hmj_ti',
            'password' => 'HmjPassword123',
        ]);
        $resHmjLoginPenuh->assertStatus(200)
            ->assertJsonMissing(['akses_terbatas' => true]);
        $tokenHmjPenuh = $resHmjLoginPenuh->json('data.token');

        // Step 10: Anggota mendaftar ke HMJ/UKM
        $resAnggotaReg = $this->postJson('/api/register/anggota', [
            'id_organisasi'         => $hmjOrg->id_organisasi,
            'nama'                  => 'Mahasiswa Anggota',
            'nim'                   => 'MHS001',
            'nomor_whatsapp'        => '08333333333',
            'username'              => 'mhs_anggota_ti',
            'password'              => 'MhsPassword123',
            'password_confirmation' => 'MhsPassword123',
            'foto_ktm'              => UploadedFile::fake()->createWithContent('ktm_mhs.jpg', $this->jpegHeader()),
        ]);
        $resAnggotaReg->assertStatus(201);
        $anggotaUser = Pengguna::where('username', 'mhs_anggota_ti')->first();
        $this->assertEquals('menunggu', $anggotaUser->status_akun);
        $this->assertTrue($anggotaUser->hasRole('anggota'));

        $keanggotaan = Keanggotaan::where('id_pengguna', $anggotaUser->id_pengguna)->first();
        $this->assertEquals('menunggu', $keanggotaan->status_keanggotaan);

        // Anggota login terbatas
        $resAnggotaLogin = $this->postJson('/api/auth/login', [
            'username' => 'mhs_anggota_ti',
            'password' => 'MhsPassword123',
        ]);
        $resAnggotaLogin->assertStatus(200)->assertJsonPath('data.akses_terbatas', true);

        // Step 11: Akun Organisasi (HMJ) menyetujui keanggotaan
        $this->app['auth']->forgetGuards();
        $resApproveAnggota = $this->withHeader('Authorization', "Bearer {$tokenHmjPenuh}")
            ->postJson("/api/org/member-requests/{$keanggotaan->id_keanggotaan}/approve");
        $resApproveAnggota->assertStatus(200);

        $keanggotaan->refresh();
        $anggotaUser->refresh();
        $this->assertEquals('disetujui', $keanggotaan->status_keanggotaan);
        $this->assertEquals('aktif', $anggotaUser->status_akun);
        $this->assertCount(0, $anggotaUser->tokens()->get());

        // Step 12: Anggota login penuh
        $resAnggotaLoginPenuh = $this->postJson('/api/auth/login', [
            'username' => 'mhs_anggota_ti',
            'password' => 'MhsPassword123',
        ]);
        $resAnggotaLoginPenuh->assertStatus(200)
            ->assertJsonMissing(['akses_terbatas' => true])
            ->assertJsonPath('data.pengguna.status_akun', 'aktif')
            ->assertJsonPath('data.roles.0', 'anggota');

        // Step 13: Super Admin melihat seluruh rangkaian di log aktivitas
        $this->app['auth']->forgetGuards();
        $resActivities = $this->withHeader('Authorization', "Bearer {$tokenSa}")
            ->getJson('/api/admin-panel/activities?per_page=50');
        $resActivities->assertStatus(200);

        $activities = $resActivities->json('data.activities');
        $this->assertNotEmpty($activities);

        $allJenis = collect($activities)->pluck('jenis_aktivitas')->unique()->values()->all();
        $this->assertContains(LogAktivitasService::JENIS_KELOLA_AKUN, $allJenis);
        $this->assertContains(LogAktivitasService::JENIS_PENDAFTARAN, $allJenis);
        $this->assertContains(LogAktivitasService::JENIS_VERIFIKASI, $allJenis);
        $this->assertContains(LogAktivitasService::JENIS_LOGIN, $allJenis);
    }
}
