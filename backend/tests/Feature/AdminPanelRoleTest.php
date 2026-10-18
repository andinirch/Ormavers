<?php

namespace Tests\Feature;

use App\Models\LogAktivitas;
use App\Models\Pengguna;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminPanelRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    // ──── Helper ────────────────────────────────────────────────────

    private function tokenSuperAdmin(): string
    {
        $sa = Pengguna::factory()->superAdmin()->create();

        return $sa->createToken('auth_token')->plainTextToken;
    }

    private function tokenPengguna(string $peran = 'anggota'): string
    {
        $state = match ($peran) {
            'administrator'   => 'administrator',
            'akun_organisasi' => 'akunOrganisasi',
            default           => null,
        };

        $pengguna = $state
            ? Pengguna::factory()->{$state}()->create()
            : Pengguna::factory()->create(['peran' => $peran, 'status_akun' => 'aktif']);

        return $pengguna->createToken('auth_token')->plainTextToken;
    }

    private function header(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    // ──── Seeder Tests ──────────────────────────────────────────────

    public function test_seeder_membuat_14_permission_dan_5_role(): void
    {
        $this->assertSame(14, Permission::count());
        $this->assertSame(5, Role::count());

        $expectedPermissions = [
            'view roles', 'create roles', 'edit roles', 'delete roles',
            'view users', 'create users', 'edit users', 'delete users',
            'verify bem', 'verify hmj/ukm', 'verify members',
            'view members', 'view profile', 'view registration status',
        ];

        foreach ($expectedPermissions as $name) {
            $this->assertTrue(Permission::where('name', $name)->exists(), "Permission '{$name}' tidak ada");
        }

        $expectedRoles = ['super_admin', 'administrator', 'akun_bem', 'akun_hmj_ukm', 'anggota'];

        foreach ($expectedRoles as $name) {
            $this->assertTrue(Role::where('name', $name)->exists(), "Role '{$name}' tidak ada");
        }
    }

    public function test_seeder_idempoten_tanpa_duplikat(): void
    {
        $this->seed(RolePermissionSeeder::class);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->assertSame(14, Permission::count());
        $this->assertSame(5, Role::count());
    }

    public function test_pengguna_lama_mendapat_role_sesuai_pemetaan(): void
    {
        $sa   = Pengguna::factory()->superAdmin()->create();
        $adm  = Pengguna::factory()->administrator()->create();
        $org  = Pengguna::factory()->akunOrganisasi()->create();
        $angg = Pengguna::factory()->create(['peran' => 'anggota', 'status_akun' => 'aktif']);

        $this->seed(RolePermissionSeeder::class);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->assertTrue($sa->fresh()->hasRole('super_admin'));
        $this->assertTrue($adm->fresh()->hasRole('administrator'));
        $this->assertTrue($org->fresh()->hasRole('akun_bem'));
        $this->assertTrue($angg->fresh()->hasRole('anggota'));
    }

    // ──── Auth Integration Tests ────────────────────────────────────

    public function test_login_memuat_roles_dan_permissions(): void
    {
        Pengguna::factory()->superAdmin()->create([
            'username'   => 'sa_login_test',
            'kata_sandi' => 'password',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'sa_login_test',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'pengguna' => ['id_pengguna', 'nama', 'username', 'peran', 'status_akun'],
                    'roles',
                    'permissions',
                ],
            ]);

        $data = $response->json('data');
        $this->assertContains('super_admin', $data['roles']);
        $this->assertContains('view roles', $data['permissions']);
    }

    public function test_me_memuat_roles_dan_permissions(): void
    {
        $token = $this->tokenSuperAdmin();

        auth()->forgetGuards();

        $response = $this->getJson('/api/auth/me', $this->header($token));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'pengguna' => ['id_pengguna', 'nama', 'username', 'peran', 'status_akun'],
                    'roles',
                    'permissions',
                ],
            ]);

        $data = $response->json('data');
        $this->assertContains('super_admin', $data['roles']);
        $this->assertContains('view roles', $data['permissions']);
    }

    public function test_response_login_dan_me_tidak_memuat_kata_sandi(): void
    {
        Pengguna::factory()->superAdmin()->create([
            'username'   => 'sa_pass_test',
            'kata_sandi' => 'password',
        ]);

        $login = $this->postJson('/api/auth/login', [
            'username' => 'sa_pass_test',
            'password' => 'password',
        ]);

        $login->assertOk();
        $this->assertArrayNotHasKey('kata_sandi', $login->json('data.pengguna'));

        auth()->forgetGuards();

        $me = $this->getJson('/api/auth/me', $this->header($login->json('data.token')));
        $me->assertOk();
        $this->assertArrayNotHasKey('kata_sandi', $me->json('data.pengguna'));
    }

    // ──── Permission Endpoint ───────────────────────────────────────

    public function test_super_admin_melihat_daftar_permission_dengan_modul_dan_roles(): void
    {
        $token = $this->tokenSuperAdmin();

        auth()->forgetGuards();

        $response = $this->getJson('/api/admin-panel/permissions', $this->header($token));

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(14, 'data.permissions')
            ->assertJsonStructure([
                'data' => [
                    'permissions' => [
                        '*' => ['id', 'name', 'modul', 'roles'],
                    ],
                ],
            ]);

        $perms = collect($response->json('data.permissions'));
        $viewRoles = $perms->firstWhere('name', 'view roles');
        $this->assertSame('Pengguna & Role', $viewRoles['modul']);
        $this->assertContains('super_admin', $viewRoles['roles']);

        $verifyBem = $perms->firstWhere('name', 'verify bem');
        $this->assertSame('Verifikasi', $verifyBem['modul']);
        $this->assertContains('administrator', $verifyBem['roles']);

        $viewProfile = $perms->firstWhere('name', 'view profile');
        $this->assertSame('Profil & Keanggotaan', $viewProfile['modul']);
    }

    // ──── Role Flags & CRUD — Happy Path ────────────────────────────

    public function test_flags_role_benar_untuk_semua_role(): void
    {
        $token = $this->tokenSuperAdmin();

        auth()->forgetGuards();

        $response = $this->getJson('/api/admin-panel/roles', $this->header($token));

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(5, 'data.roles');

        $roles = collect($response->json('data.roles'));

        foreach ($roles as $r) {
            $this->assertArrayHasKey('id', $r);
            $this->assertArrayHasKey('name', $r);
            $this->assertArrayHasKey('permissions', $r);
            $this->assertArrayHasKey('jumlah_pengguna', $r);
            $this->assertArrayHasKey('bawaan', $r);
            $this->assertArrayHasKey('terkunci', $r);
            $this->assertArrayHasKey('akses_penuh', $r);
            $this->assertArrayHasKey('dibuat_pada', $r);

            // akses_penuh = false untuk SEMUA role
            $this->assertFalse($r['akses_penuh']);

            // bawaan = true untuk 5 role bawaan
            $this->assertTrue($r['bawaan']);

            // terkunci = true HANYA untuk super_admin
            if ($r['name'] === 'super_admin') {
                $this->assertTrue($r['terkunci']);
            } else {
                $this->assertFalse($r['terkunci']);
            }
        }

        // permissions terisi untuk super_admin dan non-super_admin
        $saRole = $roles->firstWhere('name', 'super_admin');
        $this->assertNotEmpty($saRole['permissions']);

        $adminRole = $roles->firstWhere('name', 'administrator');
        $this->assertNotEmpty($adminRole['permissions']);
    }

    public function test_super_admin_melihat_detail_role(): void
    {
        $token = $this->tokenSuperAdmin();
        $role  = Role::findByName('administrator', 'web');

        auth()->forgetGuards();

        $response = $this->getJson("/api/admin-panel/roles/{$role->id}", $this->header($token));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'role' => [
                        'id'          => $role->id,
                        'name'        => 'administrator',
                        'bawaan'      => true,
                        'terkunci'    => false,
                        'akses_penuh' => false,
                    ],
                ],
            ]);
    }

    public function test_super_admin_membuat_role_baru_dengan_karakter_garis_miring(): void
    {
        $token = $this->tokenSuperAdmin();

        auth()->forgetGuards();

        $response = $this->postJson('/api/admin-panel/roles', [
            'name'        => 'auditor/keuangan_1',
            'permissions' => ['view roles', 'view users'],
        ], $this->header($token));

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'role' => [
                        'name'        => 'auditor/keuangan_1',
                        'permissions' => ['view roles', 'view users'],
                        'bawaan'      => false,
                        'terkunci'    => false,
                        'akses_penuh' => false,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('roles', ['name' => 'auditor/keuangan_1']);
    }

    public function test_super_admin_mengubah_nama_dan_permission_role_bawaan_non_super_admin(): void
    {
        $token = $this->tokenSuperAdmin();
        $role  = Role::findByName('akun_hmj_ukm', 'web');

        auth()->forgetGuards();

        // Mengubah nama dan permission role bawaan non-super_admin diperbolehkan
        $response = $this->putJson("/api/admin-panel/roles/{$role->id}", [
            'name'        => 'akun_organisasi_kampus',
            'permissions' => ['view profile'],
        ], $this->header($token));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'role' => [
                        'name'        => 'akun_organisasi_kampus',
                        'permissions' => ['view profile'],
                    ],
                ],
            ]);

        $role->refresh();
        $this->assertSame('akun_organisasi_kampus', $role->name);
        $this->assertCount(1, $role->permissions);
    }

    public function test_super_admin_menghapus_role_bawaan_non_super_admin_jika_tidak_dipakai(): void
    {
        $token = $this->tokenSuperAdmin();
        // akun_hmj_ukm belum dipakai pengguna mana pun di DB
        $role = Role::findByName('akun_hmj_ukm', 'web');

        auth()->forgetGuards();

        $response = $this->deleteJson("/api/admin-panel/roles/{$role->id}", [], $this->header($token));

        $response->assertOk()
            ->assertJson(['success' => true, 'message' => 'Role berhasil dihapus']);

        $this->assertDatabaseMissing('roles', ['name' => 'akun_hmj_ukm']);
    }

    public function test_super_admin_menghapus_role_bawaan_yang_dipakai_gagal_409(): void
    {
        $token = $this->tokenSuperAdmin();
        $role = Role::findByName('administrator', 'web');

        // Buat administrator yang memakai role ini
        Pengguna::factory()->administrator()->create();

        auth()->forgetGuards();

        $response = $this->deleteJson("/api/admin-panel/roles/{$role->id}", [], $this->header($token));

        $response->assertStatus(409)
            ->assertJson(['success' => false]);

        $this->assertStringContainsString('1 pengguna', $response->json('message'));
        $this->assertDatabaseHas('roles', ['name' => 'administrator']);
    }

    public function test_super_admin_mengubah_permission_ke_daftar_kosong(): void
    {
        $token = $this->tokenSuperAdmin();

        $role = Role::create(['name' => 'test_kosong', 'guard_name' => 'web']);
        $role->syncPermissions(['view roles']);

        auth()->forgetGuards();

        $response = $this->putJson("/api/admin-panel/roles/{$role->id}", [
            'name'        => 'test_kosong',
            'permissions' => [],
        ], $this->header($token));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'role' => [
                        'permissions' => [],
                    ],
                ],
            ]);
    }

    public function test_super_admin_menghapus_role_custom_tidak_dipakai(): void
    {
        $token = $this->tokenSuperAdmin();

        $role = Role::create(['name' => 'hapus_saya', 'guard_name' => 'web']);

        auth()->forgetGuards();

        $response = $this->deleteJson("/api/admin-panel/roles/{$role->id}", [], $this->header($token));

        $response->assertOk()
            ->assertJson(['success' => true, 'message' => 'Role berhasil dihapus']);

        $this->assertDatabaseMissing('roles', ['name' => 'hapus_saya']);
    }

    // ──── Access Control — Gagal ────────────────────────────────────

    public function test_tanpa_token_401_di_semua_endpoint(): void
    {
        $endpoints = [
            ['GET', '/api/admin-panel/permissions'],
            ['GET', '/api/admin-panel/roles'],
            ['GET', '/api/admin-panel/roles/1'],
            ['POST', '/api/admin-panel/roles'],
            ['PUT', '/api/admin-panel/roles/1'],
            ['DELETE', '/api/admin-panel/roles/1'],
            ['GET', '/api/admin-panel/ringkasan'],
        ];

        foreach ($endpoints as [$method, $url]) {
            $response = $this->json($method, $url);
            $response->assertStatus(401, "Endpoint {$method} {$url} seharusnya 401");
        }
    }

    public function test_anggota_403_di_semua_endpoint_admin_panel(): void
    {
        $token = $this->tokenPengguna('anggota');

        $endpoints = [
            ['GET', '/api/admin-panel/permissions'],
            ['GET', '/api/admin-panel/roles'],
            ['GET', '/api/admin-panel/roles/1'],
            ['POST', '/api/admin-panel/roles'],
            ['PUT', '/api/admin-panel/roles/1'],
            ['DELETE', '/api/admin-panel/roles/1'],
            ['GET', '/api/admin-panel/ringkasan'],
        ];

        foreach ($endpoints as [$method, $url]) {
            auth()->forgetGuards();

            $response = $this->json($method, $url, [], $this->header($token));
            $response->assertStatus(403, "Endpoint {$method} {$url} seharusnya 403 untuk anggota");
            $response->assertExactJson([
                'success' => false,
                'message' => 'Akses ditolak',
            ]);
        }
    }

    public function test_administrator_403_di_endpoint_create_edit_delete_roles_dan_ringkasan(): void
    {
        $token = $this->tokenPengguna('administrator');

        $endpoints = [
            ['POST', '/api/admin-panel/roles'],
            ['PUT', '/api/admin-panel/roles/1'],
            ['DELETE', '/api/admin-panel/roles/1'],
            ['GET', '/api/admin-panel/ringkasan'],
        ];

        foreach ($endpoints as [$method, $url]) {
            auth()->forgetGuards();

            $response = $this->json($method, $url, [], $this->header($token));
            $response->assertStatus(403, "Endpoint {$method} {$url} seharusnya 403 untuk administrator");
            $response->assertExactJson([
                'success' => false,
                'message' => 'Akses ditolak',
            ]);
        }
    }

    // ──── Validation — Gagal ────────────────────────────────────────

    public function test_nama_kosong_422(): void
    {
        $token = $this->tokenSuperAdmin();

        auth()->forgetGuards();

        $response = $this->postJson('/api/admin-panel/roles', [
            'name'        => '',
            'permissions' => [],
        ], $this->header($token));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('name');
    }

    public function test_nama_terlalu_pendek_422(): void
    {
        $token = $this->tokenSuperAdmin();

        auth()->forgetGuards();

        $response = $this->postJson('/api/admin-panel/roles', [
            'name'        => 'ab',
            'permissions' => [],
        ], $this->header($token));

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_nama_duplikat_422(): void
    {
        $token = $this->tokenSuperAdmin();

        auth()->forgetGuards();

        $response = $this->postJson('/api/admin-panel/roles', [
            'name'        => 'super_admin',
            'permissions' => [],
        ], $this->header($token));

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_nama_berformat_salah_422(): void
    {
        $token = $this->tokenSuperAdmin();

        $namaInvalid = ['Role Baru', 'RoleBaru', 'role-baru', 'role@baru'];

        foreach ($namaInvalid as $nama) {
            auth()->forgetGuards();

            $response = $this->postJson('/api/admin-panel/roles', [
                'name'        => $nama,
                'permissions' => [],
            ], $this->header($token));

            $response->assertStatus(422, "Nama '{$nama}' seharusnya ditolak")
                ->assertJsonValidationErrors('name');
        }
    }

    public function test_permission_tidak_ada_422(): void
    {
        $token = $this->tokenSuperAdmin();

        auth()->forgetGuards();

        $response = $this->postJson('/api/admin-panel/roles', [
            'name'        => 'role_test',
            'permissions' => ['permission_tidak_ada'],
        ], $this->header($token));

        $response->assertStatus(422)
            ->assertJsonValidationErrors('permissions.0');
    }

    public function test_tanpa_field_permissions_422(): void
    {
        $token = $this->tokenSuperAdmin();

        auth()->forgetGuards();

        $response = $this->postJson('/api/admin-panel/roles', [
            'name' => 'role_tanpa_perm',
        ], $this->header($token));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('permissions');
    }

    public function test_permissions_array_kosong_berhasil(): void
    {
        $token = $this->tokenSuperAdmin();

        auth()->forgetGuards();

        $response = $this->postJson('/api/admin-panel/roles', [
            'name'        => 'role_kosong',
            'permissions' => [],
        ], $this->header($token));

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'role' => [
                        'name'        => 'role_kosong',
                        'permissions' => [],
                    ],
                ],
            ]);
    }

    // ──── Business Rules — Super Admin Terkunci ────────────────────

    public function test_ubah_role_super_admin_403(): void
    {
        $token = $this->tokenSuperAdmin();
        $role  = Role::findByName('super_admin', 'web');

        auth()->forgetGuards();

        $response = $this->putJson("/api/admin-panel/roles/{$role->id}", [
            'name'        => 'super_admin_baru',
            'permissions' => ['view roles'],
        ], $this->header($token));

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_hapus_role_super_admin_403(): void
    {
        $token = $this->tokenSuperAdmin();
        $role  = Role::findByName('super_admin', 'web');

        auth()->forgetGuards();

        $response = $this->deleteJson("/api/admin-panel/roles/{$role->id}", [], $this->header($token));

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_id_tidak_ada_404(): void
    {
        $token = $this->tokenSuperAdmin();

        auth()->forgetGuards();

        $response = $this->getJson('/api/admin-panel/roles/99999', $this->header($token));

        $response->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    public function test_id_bukan_angka_404(): void
    {
        $token = $this->tokenSuperAdmin();

        auth()->forgetGuards();

        $response = $this->getJson('/api/admin-panel/roles/abc', $this->header($token));

        $response->assertStatus(404);
    }

    // ──── Ringkasan Dashboard Tests (A4) ────────────────────────────

    public function test_super_admin_melihat_ringkasan_dashboard(): void
    {
        $token = $this->tokenSuperAdmin();

        // Buat beberapa log aktivitas
        $sa = Pengguna::where('peran', 'super_admin')->first();
        LogAktivitas::create([
            'id_pengguna'     => $sa->id_pengguna,
            'jenis_aktivitas' => 'login',
            'keterangan'      => 'Login sistem',
            'waktu_aktivitas' => now()->subMinutes(10),
        ]);
        LogAktivitas::create([
            'id_pengguna'     => $sa->id_pengguna,
            'jenis_aktivitas' => 'role',
            'keterangan'      => 'Membuat role test',
            'waktu_aktivitas' => now()->subMinutes(5),
        ]);

        auth()->forgetGuards();

        $response = $this->getJson('/api/admin-panel/ringkasan', $this->header($token));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Ringkasan dashboard berhasil diambil',
            ])
            ->assertJsonStructure([
                'data' => [
                    'total_pengguna',
                    'administrator_aktif',
                    'role_terdefinisi',
                    'permission',
                    'aktivitas_terbaru' => [
                        '*' => [
                            'nama_pengguna',
                            'jenis_aktivitas',
                            'keterangan',
                            'waktu_aktivitas',
                        ],
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertSame(Pengguna::count(), $data['total_pengguna']);
        $this->assertSame(Role::count(), $data['role_terdefinisi']);
        $this->assertSame(Permission::count(), $data['permission']);
        $this->assertCount(2, $data['aktivitas_terbaru']);
        // Terurut terbaru dulu
        $this->assertSame('role', $data['aktivitas_terbaru'][0]['jenis_aktivitas']);
    }
}
