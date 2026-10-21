<?php

namespace Tests\Feature;

use App\Models\LogAktivitas;
use App\Models\Organisasi;
use App\Models\Pengguna;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminPanelUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function tokenSuperAdmin(): array
    {
        $sa = Pengguna::factory()->superAdmin()->create(['username' => 'super_boss']);
        $token = $sa->createToken('auth_token')->plainTextToken;

        return [$sa, $token, ['Authorization' => "Bearer {$token}"]];
    }

    private function tokenPengguna(string $peran = 'anggota'): array
    {
        $state = match ($peran) {
            'administrator'   => 'administrator',
            'akun_organisasi' => 'akunOrganisasi',
            default           => null,
        };

        $user = $state
            ? Pengguna::factory()->{$state}()->create()
            : Pengguna::factory()->create(['peran' => $peran, 'status_akun' => 'aktif']);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [$user, $token, ['Authorization' => "Bearer {$token}"]];
    }

    // ──── B1: GET /api/superadmin/users ─────────────────────────────

    public function test_super_admin_melihat_daftar_pengguna_dengan_filter_dan_paginasi(): void
    {
        [, , $headers] = $this->tokenSuperAdmin();

        $admin = Pengguna::factory()->administrator()->create([
            'nama'     => 'Budi Administrator',
            'username' => 'budi_admin',
        ]);

        $organisasiUser = Pengguna::factory()->akunOrganisasi()->create([
            'nama'     => 'Ketua BEM',
            'username' => 'ketua_bem',
        ]);

        $univ = \App\Models\Universitas::create([
            'nama_universitas' => 'Universitas Contoh',
        ]);

        Organisasi::create([
            'id_universitas'     => $univ->id_universitas,
            'id_akun_organisasi' => $organisasiUser->id_pengguna,
            'nama_organisasi'    => 'BEM Fakultas Teknik',
            'jenis_organisasi'   => 'bem',
            'nomor_telepon'      => '081234567890',
            'status_organisasi'  => 'aktif',
        ]);

        // Catat login untuk admin
        LogAktivitas::create([
            'id_pengguna'     => $admin->id_pengguna,
            'jenis_aktivitas' => 'login',
            'keterangan'      => 'Login sistem',
            'waktu_aktivitas' => now()->subHour(),
        ]);

        auth()->forgetGuards();

        $response = $this->getJson('/api/superadmin/users', $headers);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'users' => [
                        '*' => [
                            'id_pengguna',
                            'nama',
                            'username',
                            'role',
                            'peran',
                            'organisasi',
                            'status_akun',
                            'login_terakhir',
                        ],
                    ],
                    'meta' => ['total', 'per_page', 'current_page', 'last_page'],
                    'ringkasan' => [
                        'total_terdaftar',
                        'akun_aktif',
                        'verifikasi_tertunda',
                        'ditangguhkan',
                        'nonaktif_ditolak',
                    ],
                ],
            ]);

        $users = collect($response->json('data.users'));
        $bemUser = $users->firstWhere('username', 'ketua_bem');
        $this->assertSame('BEM Fakultas Teknik', $bemUser['organisasi']);

        $adminItem = $users->firstWhere('username', 'budi_admin');
        $this->assertNotNull($adminItem['login_terakhir']);
    }

    public function test_filter_q_mencocokkan_nama_dan_username(): void
    {
        [, , $headers] = $this->tokenSuperAdmin();

        Pengguna::factory()->create(['nama' => 'Joko Susanto', 'username' => 'jokosus']);
        Pengguna::factory()->create(['nama' => 'Susanti Dewi', 'username' => 'dewi99']);
        Pengguna::factory()->create(['nama' => 'Ahmad Dani', 'username' => 'ahmaddani']);

        auth()->forgetGuards();

        $response = $this->getJson('/api/superadmin/users?q=susan', $headers);
        $response->assertOk();

        $users = $response->json('data.users');
        $this->assertCount(2, $users);
    }

    public function test_filter_role_dan_status(): void
    {
        [, , $headers] = $this->tokenSuperAdmin();

        Pengguna::factory()->administrator()->create(['status_akun' => 'aktif']);
        Pengguna::factory()->administrator()->create(['status_akun' => 'nonaktif']);
        Pengguna::factory()->create(['peran' => 'anggota', 'status_akun' => 'aktif']);

        auth()->forgetGuards();

        $response = $this->getJson('/api/superadmin/users?role=administrator&status=aktif', $headers);
        $response->assertOk();

        $users = $response->json('data.users');
        $this->assertCount(1, $users);
        $this->assertSame('administrator', $users[0]['role']);
        $this->assertSame('aktif', $users[0]['status_akun']);
    }

    public function test_ringkasan_data_benar(): void
    {
        [, , $headers] = $this->tokenSuperAdmin();

        Pengguna::factory()->create(['status_akun' => 'aktif']);
        Pengguna::factory()->create(['status_akun' => 'menunggu']);
        Pengguna::factory()->create(['status_akun' => 'ditangguhkan']);
        Pengguna::factory()->create(['status_akun' => 'nonaktif']);
        Pengguna::factory()->create(['status_akun' => 'ditolak']);

        auth()->forgetGuards();

        $response = $this->getJson('/api/superadmin/users', $headers);
        $response->assertOk();

        $ringkasan = $response->json('data.ringkasan');
        $this->assertSame(Pengguna::count(), $ringkasan['total_terdaftar']);
        $this->assertSame(2, $ringkasan['akun_aktif']); // Super admin + 1 aktif
        $this->assertSame(1, $ringkasan['verifikasi_tertunda']);
        $this->assertSame(1, $ringkasan['ditangguhkan']);
        $this->assertSame(2, $ringkasan['nonaktif_ditolak']);
    }

    // ──── B2: POST /api/superadmin/users ────────────────────────────

    public function test_super_admin_membuat_akun_administrator(): void
    {
        [$sa, , $headers] = $this->tokenSuperAdmin();

        auth()->forgetGuards();

        $payload = [
            'nama'     => 'Admin Satu',
            'username' => 'admin_satu',
            'password' => 'Password123',
        ];

        $response = $this->postJson('/api/superadmin/users', $payload, $headers);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Akun Administrator Berhasil Ditambahkan',
                'data' => [
                    'user' => [
                        'nama'        => 'Admin Satu',
                        'username'    => 'admin_satu',
                        'role'        => 'administrator',
                        'peran'       => 'administrator',
                        'status_akun' => 'aktif',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('pengguna', [
            'username' => 'admin_satu',
            'peran'    => 'administrator',
        ]);

        $newUser = Pengguna::where('username', 'admin_satu')->first();
        $this->assertTrue($newUser->hasRole('administrator'));
        $this->assertTrue(Hash::check('Password123', $newUser->kata_sandi));

        // Cek log aktivitas
        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna'     => $sa->id_pengguna,
            'jenis_aktivitas' => 'kelola_akun',
        ]);

        $this->assertArrayNotHasKey('kata_sandi', $response->json('data.user'));
    }

    public function test_role_selain_administrator_ditolak_422(): void
    {
        [, , $headers] = $this->tokenSuperAdmin();

        auth()->forgetGuards();

        $response = $this->postJson('/api/superadmin/users', [
            'nama'     => 'Coba Anggota',
            'username' => 'coba_anggota',
            'password' => 'Password123',
            'role'     => 'anggota',
        ], $headers);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('role');
    }

    public function test_password_lemah_pada_tambah_user_ditolak_422(): void
    {
        [, , $headers] = $this->tokenSuperAdmin();

        auth()->forgetGuards();

        // Tanpa angka
        $res1 = $this->postJson('/api/superadmin/users', [
            'nama'     => 'Admin Lemah',
            'username' => 'admin_lemah1',
            'password' => 'passwordhanya',
        ], $headers);
        $res1->assertStatus(422)->assertJsonValidationErrors('password');

        auth()->forgetGuards();

        // Kurang dari 8
        $res2 = $this->postJson('/api/superadmin/users', [
            'nama'     => 'Admin Lemah',
            'username' => 'admin_lemah2',
            'password' => 'pass1',
        ], $headers);
        $res2->assertStatus(422)->assertJsonValidationErrors('password');
    }

    // ──── B3: PUT /api/superadmin/users/{id} ────────────────────────

    public function test_super_admin_menonaktifkan_akun_administrator_dan_token_tercabut(): void
    {
        [$sa, , $headers] = $this->tokenSuperAdmin();
        $admin = Pengguna::factory()->administrator()->create();
        $adminToken = $admin->createToken('admin_token')->plainTextToken;

        auth()->forgetGuards();

        // Pastikan token admin bisa dipakai
        $this->getJson('/api/auth/me', ['Authorization' => "Bearer {$adminToken}"])
            ->assertOk();

        auth()->forgetGuards();

        $response = $this->putJson("/api/superadmin/users/{$admin->id_pengguna}", [
            'status_akun' => 'nonaktif',
        ], $headers);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id_pengguna' => $admin->id_pengguna,
                        'status_akun' => 'nonaktif',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('pengguna', [
            'id_pengguna' => $admin->id_pengguna,
            'status_akun' => 'nonaktif',
        ]);

        // Cek log aktivitas
        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna'     => $sa->id_pengguna,
            'jenis_aktivitas' => 'kelola_akun',
            'keterangan'      => "Menonaktifkan akun Administrator: {$admin->nama}",
        ]);

        auth()->forgetGuards();

        // Token lama admin ditolak 401
        $this->getJson('/api/auth/me', ['Authorization' => "Bearer {$adminToken}"])
            ->assertStatus(401);
    }

    public function test_ubah_status_selain_nonaktif_ditolak_422(): void
    {
        [, , $headers] = $this->tokenSuperAdmin();
        $admin = Pengguna::factory()->administrator()->create();

        auth()->forgetGuards();

        $statuses = ['aktif', 'ditangguhkan', 'menunggu', 'ditolak'];
        foreach ($statuses as $st) {
            $response = $this->putJson("/api/superadmin/users/{$admin->id_pengguna}", [
                'status_akun' => $st,
            ], $headers);

            $response->assertStatus(422)
                ->assertJsonValidationErrors('status_akun');
        }
    }

    public function test_ubah_super_admin_atau_diri_sendiri_ditolak_403(): void
    {
        [$sa, , $headers] = $this->tokenSuperAdmin();

        auth()->forgetGuards();

        // Diri sendiri
        $response1 = $this->putJson("/api/superadmin/users/{$sa->id_pengguna}", [
            'status_akun' => 'nonaktif',
        ], $headers);

        $response1->assertStatus(403)
            ->assertJson(['success' => false, 'message' => 'Hanya akun Administrator yang dikelola Super Admin']);

        // Super Admin lain
        $saLain = Pengguna::factory()->superAdmin()->create();
        auth()->forgetGuards();

        $response2 = $this->putJson("/api/superadmin/users/{$saLain->id_pengguna}", [
            'status_akun' => 'nonaktif',
        ], $headers);

        $response2->assertStatus(403)
            ->assertJson(['success' => false, 'message' => 'Hanya akun Administrator yang dikelola Super Admin']);
    }

    public function test_ubah_pengguna_non_administrator_ditolak_403(): void
    {
        [, , $headers] = $this->tokenSuperAdmin();
        $anggota = Pengguna::factory()->create(['peran' => 'anggota']);

        auth()->forgetGuards();

        $response = $this->putJson("/api/superadmin/users/{$anggota->id_pengguna}", [
            'status_akun' => 'nonaktif',
        ], $headers);

        $response->assertStatus(403)
            ->assertJson(['success' => false, 'message' => 'Hanya akun Administrator yang dikelola Super Admin']);
    }

    public function test_ubah_pengguna_status_menunggu_ditolak_409(): void
    {
        [, , $headers] = $this->tokenSuperAdmin();
        $adminMenunggu = Pengguna::factory()->administrator()->menunggu()->create();

        auth()->forgetGuards();

        $response = $this->putJson("/api/superadmin/users/{$adminMenunggu->id_pengguna}", [
            'status_akun' => 'nonaktif',
        ], $headers);

        $response->assertStatus(409)
            ->assertJson(['success' => false]);
    }

    public function test_id_tidak_ada_atau_bukan_angka_404(): void
    {
        [, , $headers] = $this->tokenSuperAdmin();

        auth()->forgetGuards();
        $this->putJson('/api/superadmin/users/99999', ['status_akun' => 'nonaktif'], $headers)
            ->assertStatus(404);

        auth()->forgetGuards();
        $this->putJson('/api/superadmin/users/abc', ['status_akun' => 'nonaktif'], $headers)
            ->assertStatus(404);
    }

    public function test_akses_tanpa_token_401_dan_non_super_admin_403(): void
    {
        $endpoints = [
            ['GET', '/api/superadmin/users'],
            ['POST', '/api/superadmin/users'],
            ['PUT', '/api/superadmin/users/1'],
        ];

        foreach ($endpoints as [$method, $url]) {
            auth()->forgetGuards();
            $this->json($method, $url)->assertStatus(401);

            [, , $adminHeaders] = $this->tokenPengguna('administrator');
            auth()->forgetGuards();
            $this->json($method, $url, [], $adminHeaders)->assertStatus(403);
        }
    }
}
