<?php

namespace Tests\Feature;

use App\Models\LogAktivitas;
use App\Models\Organisasi;
use App\Models\Pengguna;
use App\Models\PendaftaranOrganisasi;
use App\Models\Universitas;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LogAktivitasTest extends TestCase
{
    use RefreshDatabase;

    private function tokenSuperAdmin(): array
    {
        $superAdmin = Pengguna::factory()->superAdmin()->create();
        $token = $superAdmin->createToken('auth_token')->plainTextToken;

        return [$superAdmin, $token, ['Authorization' => 'Bearer ' . $token]];
    }

    private function validPayloadBem(): array
    {
        $univ = Universitas::firstOrCreate(['nama_universitas' => 'Institut Teknologi Contoh']);

        return [
            'id_universitas'           => $univ->id_universitas,
            'nama_organisasi'          => 'BEM IT Contoh',
            'nomor_telepon_organisasi' => '02198765432',
            'nama'                     => 'Ahmad Ketua',
            'nim'                      => '12345678',
            'nomor_whatsapp'           => '081234567890',
            'username'                 => 'ahmad_bem',
            'password'                 => 'Password123',
            'foto_ktm'                 => UploadedFile::fake()->createWithContent(
                'ktm.jpg',
                "\xFF\xD8\xFF\xE0" . str_repeat("\x00", 100)
            ),
            'sk_organisasi'            => UploadedFile::fake()->createWithContent(
                'sk_org.pdf',
                '%PDF-1.4' . str_repeat("\x00", 100)
            ),
            'sk_kepengurusan'          => UploadedFile::fake()->createWithContent(
                'sk_kep.pdf',
                '%PDF-1.4' . str_repeat("\x00", 100)
            ),
        ];
    }

    // -------------------------------------------------------
    // LOGIN & LOGOUT
    // -------------------------------------------------------

    public function test_login_berhasil_mencatat_log_aktivitas(): void
    {
        $user = Pengguna::factory()->create([
            'username'   => 'user_login_test',
            'kata_sandi' => 'password123',
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.50'])
            ->postJson('/api/auth/login', [
                'username' => 'user_login_test',
                'password' => 'password123',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna'     => $user->id_pengguna,
            'jenis_aktivitas' => 'login',
            'keterangan'      => 'Berhasil masuk ke sistem dari IP 192.168.1.50',
        ]);
        $this->assertSame(1, LogAktivitas::count());
    }

    public function test_login_gagal_tidak_mencatat_log(): void
    {
        $user = Pengguna::factory()->create([
            'username'   => 'user_gagal_test',
            'kata_sandi' => 'password123',
        ]);

        // 1. Password salah
        $this->postJson('/api/auth/login', [
            'username' => 'user_gagal_test',
            'password' => 'passwordsalah',
        ])->assertStatus(401);

        // 2. User tidak ada
        $this->postJson('/api/auth/login', [
            'username' => 'tidak_ada',
            'password' => 'password123',
        ])->assertStatus(401);

        $this->assertSame(0, LogAktivitas::count());
    }

    public function test_akun_ditangguhkan_tidak_bisa_login_403_dan_tidak_mencatat_log(): void
    {
        $user = Pengguna::factory()->ditangguhkan()->create([
            'username'   => 'user_ditangguhkan',
            'kata_sandi' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'user_ditangguhkan',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akun ditangguhkan',
            ]);

        $this->assertSame(0, LogAktivitas::count());
    }

    public function test_logout_mencatat_log_aktivitas(): void
    {
        $user = Pengguna::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->postJson('/api/auth/logout', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna'     => $user->id_pengguna,
            'jenis_aktivitas' => 'logout',
            'keterangan'      => 'Keluar dari sistem',
        ]);
    }

    // -------------------------------------------------------
    // REGISTRASI BEM
    // -------------------------------------------------------

    public function test_registrasi_bem_mencatat_log_pendaftaran(): void
    {
        Storage::fake('local');

        $payload = $this->validPayloadBem();
        $response = $this->postJson('/api/register/bem', $payload);

        $response->assertStatus(201);

        $pengguna = Pengguna::where('username', 'ahmad_bem')->first();
        $this->assertNotNull($pengguna);

        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna'     => $pengguna->id_pengguna,
            'jenis_aktivitas' => 'pendaftaran',
            'keterangan'      => 'Pendaftaran BEM BEM IT Contoh dari Institut Teknologi Contoh',
        ]);
    }

    public function test_registrasi_bem_gagal_validasi_atau_duplikat_tidak_membuat_log(): void
    {
        Storage::fake('local');

        // Gagal 422 karena field kosong
        $this->postJson('/api/register/bem', [])->assertStatus(422);

        $this->assertSame(0, LogAktivitas::count());
    }

    // -------------------------------------------------------
    // VERIFIKASI BEM
    // -------------------------------------------------------

    public function test_setujui_bem_mencatat_log_verifikasi(): void
    {
        Storage::fake('local');
        $admin = Pengguna::factory()->administrator()->create();
        $token = $admin->createToken('auth_token')->plainTextToken;

        $univ = Universitas::firstOrCreate(['nama_universitas' => 'Univ Merdeka']);
        $pendaftar = Pengguna::factory()->akunOrganisasi()->menunggu()->create();
        $organisasi = Organisasi::create([
            'id_universitas'     => $univ->id_universitas,
            'id_akun_organisasi' => $pendaftar->id_pengguna,
            'nama_organisasi'    => 'BEM Kampus Merdeka',
            'jenis_organisasi'   => 'bem',
            'nomor_telepon'      => '021111111',
            'status_organisasi'  => 'menunggu',
        ]);
        $pendaftaran = PendaftaranOrganisasi::create([
            'id_organisasi'       => $organisasi->id_organisasi,
            'status_pendaftaran'  => 'menunggu',
            'tanggal_pendaftaran' => now(),
        ]);

        $response = $this->postJson("/api/admin/registrations/bem/{$pendaftaran->id_pendaftaran_organisasi}/approve", [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna'     => $admin->id_pengguna,
            'jenis_aktivitas' => 'verifikasi',
            'keterangan'      => 'Menyetujui pendaftaran BEM BEM Kampus Merdeka',
        ]);
    }

    public function test_tolak_bem_mencatat_log_verifikasi_dengan_alasan(): void
    {
        Storage::fake('local');
        $admin = Pengguna::factory()->administrator()->create();
        $token = $admin->createToken('auth_token')->plainTextToken;

        $univ = Universitas::firstOrCreate(['nama_universitas' => 'Univ Maju']);
        $pendaftar = Pengguna::factory()->akunOrganisasi()->menunggu()->create();
        $organisasi = Organisasi::create([
            'id_universitas'     => $univ->id_universitas,
            'id_akun_organisasi' => $pendaftar->id_pengguna,
            'nama_organisasi'    => 'BEM Kampus Maju',
            'jenis_organisasi'   => 'bem',
            'nomor_telepon'      => '021222222',
            'status_organisasi'  => 'menunggu',
        ]);
        $pendaftaran = PendaftaranOrganisasi::create([
            'id_organisasi'       => $organisasi->id_organisasi,
            'status_pendaftaran'  => 'menunggu',
            'tanggal_pendaftaran' => now(),
        ]);

        $response = $this->postJson("/api/admin/registrations/bem/{$pendaftaran->id_pendaftaran_organisasi}/reject", [
            'alasan' => 'Dokumen SK Kepengurusan tidak sah',
        ], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna'     => $admin->id_pengguna,
            'jenis_aktivitas' => 'verifikasi',
            'keterangan'      => 'Menolak pendaftaran BEM BEM Kampus Maju dengan alasan: Dokumen SK Kepengurusan tidak sah',
        ]);
    }

    public function test_verifikasi_bem_409_tidak_menambah_log(): void
    {
        Storage::fake('local');
        $admin = Pengguna::factory()->administrator()->create();
        $token = $admin->createToken('auth_token')->plainTextToken;

        $univ = Universitas::firstOrCreate(['nama_universitas' => 'Univ Selesai']);
        $pendaftar = Pengguna::factory()->akunOrganisasi()->create(['status_akun' => 'aktif']);
        $organisasi = Organisasi::create([
            'id_universitas'     => $univ->id_universitas,
            'id_akun_organisasi' => $pendaftar->id_pengguna,
            'nama_organisasi'    => 'BEM Sudah Selesai',
            'jenis_organisasi'   => 'bem',
            'nomor_telepon'      => '021222222',
            'status_organisasi'  => 'aktif',
        ]);
        $pendaftaran = PendaftaranOrganisasi::create([
            'id_organisasi'       => $organisasi->id_organisasi,
            'status_pendaftaran'  => 'disetujui',
            'tanggal_pendaftaran' => now(),
        ]);

        $this->postJson("/api/admin/registrations/bem/{$pendaftaran->id_pendaftaran_organisasi}/approve", [], [
            'Authorization' => 'Bearer ' . $token,
        ])->assertStatus(409);

        $this->assertSame(0, LogAktivitas::count());
    }

    // -------------------------------------------------------
    // KELOLA ADMINISTRATOR
    // -------------------------------------------------------

    public function test_tambah_administrator_mencatat_log_kelola_akun(): void
    {
        [$superAdmin, , $headers] = $this->tokenSuperAdmin();

        $response = $this->postJson('/api/superadmin/administrators', [
            'nama'     => 'Admin Baru',
            'username' => 'admin_baru_log',
            'password' => 'secret1234',
        ], $headers);

        $response->assertStatus(201);

        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna'     => $superAdmin->id_pengguna,
            'jenis_aktivitas' => 'kelola_akun',
            'keterangan'      => 'Membuat akun Administrator baru: Admin Baru (admin_baru_log)',
        ]);
    }

    public function test_nonaktifkan_administrator_mencatat_log_kelola_akun(): void
    {
        [$superAdmin, , $headers] = $this->tokenSuperAdmin();
        $admin = Pengguna::factory()->administrator()->create(['nama' => 'Admin Aktif']);

        $response = $this->patchJson("/api/superadmin/administrators/{$admin->id_pengguna}/nonaktif", [], $headers);

        $response->assertStatus(200);

        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna'     => $superAdmin->id_pengguna,
            'jenis_aktivitas' => 'kelola_akun',
            'keterangan'      => 'Menonaktifkan akun Administrator: Admin Aktif',
        ]);
    }

    public function test_nonaktifkan_administrator_409_tidak_menambah_log(): void
    {
        [$superAdmin, , $headers] = $this->tokenSuperAdmin();
        $admin = Pengguna::factory()->administrator()->nonaktif()->create();

        $this->patchJson("/api/superadmin/administrators/{$admin->id_pengguna}/nonaktif", [], $headers)
            ->assertStatus(409);

        $this->assertSame(0, LogAktivitas::count());
    }

    // -------------------------------------------------------
    // ROLE
    // -------------------------------------------------------

    public function test_buat_ubah_hapus_role_mencatat_log_role(): void
    {
        $this->seed(RolePermissionSeeder::class);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        [$superAdmin, , $headers] = $this->tokenSuperAdmin();

        // 1. Buat role
        $createResponse = $this->postJson('/api/admin-panel/roles', [
            'name'        => 'auditor_keuangan',
            'permissions' => ['view roles'],
        ], $headers);

        $createResponse->assertStatus(201);
        $roleId = $createResponse->json('data.role.id');

        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna'     => $superAdmin->id_pengguna,
            'jenis_aktivitas' => 'role',
            'keterangan'      => 'Membuat role auditor_keuangan',
        ]);

        // 2. Ubah role
        $updateResponse = $this->putJson("/api/admin-panel/roles/{$roleId}", [
            'name'        => 'auditor_keuangan',
            'permissions' => ['view roles', 'view users'],
        ], $headers);

        $updateResponse->assertStatus(200);

        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna'     => $superAdmin->id_pengguna,
            'jenis_aktivitas' => 'role',
            'keterangan'      => 'Mengubah permission role auditor_keuangan',
        ]);

        // 3. Hapus role
        $deleteResponse = $this->deleteJson("/api/admin-panel/roles/{$roleId}", [], $headers);

        $deleteResponse->assertStatus(200);

        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna'     => $superAdmin->id_pengguna,
            'jenis_aktivitas' => 'role',
            'keterangan'      => 'Menghapus role auditor_keuangan',
        ]);
    }

    public function test_ubah_role_super_admin_403_tidak_mencatat_log(): void
    {
        $this->seed(RolePermissionSeeder::class);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        [$superAdmin, , $headers] = $this->tokenSuperAdmin();
        $saRole = Role::findByName('super_admin', 'web');

        $this->putJson("/api/admin-panel/roles/{$saRole->id}", [
            'name'        => 'super_admin',
            'permissions' => [],
        ], $headers)->assertStatus(403);

        $this->assertSame(0, LogAktivitas::count());
    }

    // -------------------------------------------------------
    // KEAMANAN: TIDAK MEMUAT PASSWORD, TOKEN, ATAU PATH FILE
    // -------------------------------------------------------

    public function test_keterangan_log_tidak_memuat_password_atau_token(): void
    {
        [$superAdmin, , $headers] = $this->tokenSuperAdmin();

        $this->postJson('/api/superadmin/administrators', [
            'nama'     => 'Admin Rahasia',
            'username' => 'admin_rahasia',
            'password' => 'SuperRahasia123',
        ], $headers)->assertStatus(201);

        foreach (LogAktivitas::all() as $log) {
            $this->assertStringNotContainsString('SuperRahasia123', $log->keterangan);
            $this->assertStringNotContainsString('password', strtolower($log->keterangan));
            $this->assertStringNotContainsString('token', strtolower($log->keterangan));
            $this->assertStringNotContainsString('pendaftaran/', $log->keterangan);
        }
    }
}
