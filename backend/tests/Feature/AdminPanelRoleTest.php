<?php

namespace Tests\Feature;

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
        // Seeder sudah dijalankan di setUp(), jalankan sekali lagi
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

        // Re-run seeder agar sinkronisasi pengguna yang baru dibuat berjalan
        $this->seed(RolePermissionSeeder::class);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Pengguna baru sudah mendapat role via model event `saved`
        $this->assertTrue($sa->fresh()->hasRole('super_admin'));
        $this->assertTrue($adm->fresh()->hasRole('administrator'));
        $this->assertTrue($org->fresh()->hasRole('akun_bem'));
        $this->assertTrue($angg->fresh()->hasRole('anggota'));
    }

    // ──── Auth Integration Tests ────────────────────────────────────

    public function test_login_memuat_roles_dan_permissions(): void
    {
        Pengguna::factory()->superAdmin()->create([
            'username' => 'sa_login_test',
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
            'username' => 'sa_pass_test',
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

    public function test_super_admin_melihat_daftar_permission(): void
    {
        $token = $this->tokenSuperAdmin();

        auth()->forgetGuards();

        $response = $this->getJson('/api/admin-panel/permissions', $this->header($token));

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(14, 'data.permissions');
    }

    // ──── Role CRUD — Happy Path ────────────────────────────────────

    public function test_super_admin_melihat_daftar_role(): void
    {
        $token = $this->tokenSuperAdmin();

        auth()->forgetGuards();

        $response = $this->getJson('/api/admin-panel/roles', $this->header($token));

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(5, 'data.roles');

        $roles = collect($response->json('data.roles'));
        $saRole = $roles->firstWhere('name', 'super_admin');

        $this->assertArrayHasKey('jumlah_pengguna', $saRole);
        $this->assertArrayHasKey('dibuat_pada', $saRole);
        $this->assertArrayHasKey('permissions', $saRole);
        $this->assertGreaterThanOrEqual(1, $saRole['jumlah_pengguna']);
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
                        'id'   => $role->id,
                        'name' => 'administrator',
                    ],
                ],
            ]);
    }

    public function test_super_admin_membuat_role_baru(): void
    {
        $token = $this->tokenSuperAdmin();

        auth()->forgetGuards();

        $response = $this->postJson('/api/admin-panel/roles', [
            'name'        => 'editor_konten',
            'permissions' => ['view roles', 'view users'],
        ], $this->header($token));

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'role' => [
                        'name'        => 'editor_konten',
                        'permissions' => ['view roles', 'view users'],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('roles', ['name' => 'editor_konten']);
    }

    public function test_super_admin_mengubah_permission_role_bawaan_non_super_admin(): void
    {
        $token = $this->tokenSuperAdmin();
        $role  = Role::findByName('administrator', 'web');

        auth()->forgetGuards();

        $response = $this->putJson("/api/admin-panel/roles/{$role->id}", [
            'name'        => 'administrator',
            'permissions' => ['view roles'],
        ], $this->header($token));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'role' => [
                        'name'        => 'administrator',
                        'permissions' => ['view roles'],
                    ],
                ],
            ]);

        // Permission lama sudah terganti
        $role->refresh();
        $this->assertCount(1, $role->permissions);
    }

    public function test_super_admin_mengubah_permission_ke_daftar_kosong(): void
    {
        $token = $this->tokenSuperAdmin();

        // Buat role custom dulu
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

    public function test_super_admin_mengubah_nama_dan_permission_role_custom(): void
    {
        $token = $this->tokenSuperAdmin();

        $role = Role::create(['name' => 'role_lama', 'guard_name' => 'web']);
        $role->syncPermissions(['view roles']);

        auth()->forgetGuards();

        $response = $this->putJson("/api/admin-panel/roles/{$role->id}", [
            'name'        => 'role_baru',
            'permissions' => ['view users', 'create users'],
        ], $this->header($token));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'role' => [
                        'name'        => 'role_baru',
                        'permissions' => ['view users', 'create users'],
                    ],
                ],
            ]);
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

    public function test_administrator_403_di_endpoint_create_edit_delete_roles(): void
    {
        $token = $this->tokenPengguna('administrator');

        $endpoints = [
            ['POST', '/api/admin-panel/roles'],
            ['PUT', '/api/admin-panel/roles/1'],
            ['DELETE', '/api/admin-panel/roles/1'],
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

    // ──── Business Rules — Gagal ────────────────────────────────────

    public function test_ubah_nama_role_bawaan_403(): void
    {
        $token = $this->tokenSuperAdmin();
        $role  = Role::findByName('administrator', 'web');

        auth()->forgetGuards();

        $response = $this->putJson("/api/admin-panel/roles/{$role->id}", [
            'name'        => 'admin_baru',
            'permissions' => ['view users'],
        ], $this->header($token));

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_ubah_permission_super_admin_403(): void
    {
        $token = $this->tokenSuperAdmin();
        $role  = Role::findByName('super_admin', 'web');

        auth()->forgetGuards();

        $response = $this->putJson("/api/admin-panel/roles/{$role->id}", [
            'name'        => 'super_admin',
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

    public function test_hapus_role_administrator_403(): void
    {
        $token = $this->tokenSuperAdmin();
        $role  = Role::findByName('administrator', 'web');

        auth()->forgetGuards();

        $response = $this->deleteJson("/api/admin-panel/roles/{$role->id}", [], $this->header($token));

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_hapus_role_akun_bem_403(): void
    {
        $token = $this->tokenSuperAdmin();
        $role  = Role::findByName('akun_bem', 'web');

        auth()->forgetGuards();

        $response = $this->deleteJson("/api/admin-panel/roles/{$role->id}", [], $this->header($token));

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_hapus_role_akun_hmj_ukm_403(): void
    {
        $token = $this->tokenSuperAdmin();
        $role  = Role::findByName('akun_hmj_ukm', 'web');

        auth()->forgetGuards();

        $response = $this->deleteJson("/api/admin-panel/roles/{$role->id}", [], $this->header($token));

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_hapus_role_anggota_403(): void
    {
        $token = $this->tokenSuperAdmin();
        $role  = Role::findByName('anggota', 'web');

        auth()->forgetGuards();

        $response = $this->deleteJson("/api/admin-panel/roles/{$role->id}", [], $this->header($token));

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_hapus_role_custom_yang_masih_dipakai_409(): void
    {
        $token = $this->tokenSuperAdmin();

        $role = Role::create(['name' => 'role_dipakai', 'guard_name' => 'web']);
        $pengguna = Pengguna::factory()->create(['peran' => 'anggota', 'status_akun' => 'aktif']);
        $pengguna->assignRole($role);

        auth()->forgetGuards();

        $response = $this->deleteJson("/api/admin-panel/roles/{$role->id}", [], $this->header($token));

        $response->assertStatus(409)
            ->assertJson(['success' => false]);

        $this->assertStringContainsString('1', $response->json('message'));
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
}
