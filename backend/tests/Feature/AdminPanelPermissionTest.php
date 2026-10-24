<?php
namespace Tests\Feature;

use App\Models\Pengguna;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminPanelPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function tokenSuperAdmin(): string
    {
        $pengguna = Pengguna::factory()->superAdmin()->create();

        return $pengguna->createToken('auth_token')->plainTextToken;
    }

    private function header(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_super_admin_membuat_permission(): void
    {
        $token = $this->tokenSuperAdmin();
        auth()->forgetGuards();

        $response = $this->postJson(
            '/api/admin-panel/permissions',
            ['name' => 'export reports'],
            $this->header($token)
        );

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Permission berhasil dibuat',
                'data' => [
                    'permission' => [
                        'name' => 'export reports',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'export reports',
            'guard_name' => 'web',
        ]);
    }

    public function test_super_admin_mengubah_permission(): void
    {
        $token = $this->tokenSuperAdmin();

        $permission = Permission::create([
            'name' => 'export reports',
            'guard_name' => 'web',
        ]);

        auth()->forgetGuards();

        $response = $this->putJson(
            "/api/admin-panel/permissions/{$permission->id}",
            ['name' => 'download reports'],
            $this->header($token)
        );

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Permission berhasil diperbarui',
                'data' => [
                    'permission' => [
                        'id' => $permission->id,
                        'name' => 'download reports',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'name' => 'download reports',
        ]);

        $this->assertDatabaseMissing('permissions', [
            'id' => $permission->id,
            'name' => 'export reports',
        ]);
    }

    public function test_super_admin_menghapus_permission_yang_tidak_dipakai(): void
    {
        $token = $this->tokenSuperAdmin();

        $permission = Permission::create([
            'name' => 'temporary permission',
            'guard_name' => 'web',
        ]);

        auth()->forgetGuards();

        $response = $this->deleteJson(
            "/api/admin-panel/permissions/{$permission->id}",
            [],
            $this->header($token)
        );

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Permission berhasil dihapus',
            ]);

        $this->assertDatabaseMissing('permissions', [
            'id' => $permission->id,
        ]);
    }

    public function test_permission_yang_dipakai_role_tidak_bisa_dihapus(): void
    {
        $token = $this->tokenSuperAdmin();

        $permission = Permission::create([
            'name' => 'temporary permission',
            'guard_name' => 'web',
        ]);

        $role = Role::create([
            'name' => 'role_test_permission',
            'guard_name' => 'web',
        ]);

        $role->givePermissionTo($permission);

        auth()->forgetGuards();

        $response = $this->deleteJson(
            "/api/admin-panel/permissions/{$permission->id}",
            [],
            $this->header($token)
        );

        $response->assertStatus(409)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'name' => 'temporary permission',
        ]);
    }

    public function test_permission_terkunci_tidak_bisa_diubah_atau_dihapus(): void
    {
        $token = $this->tokenSuperAdmin();

        $permission = Permission::where('name', 'view roles')
            ->where('guard_name', 'web')
            ->firstOrFail();

        auth()->forgetGuards();

        $update = $this->putJson(
            "/api/admin-panel/permissions/{$permission->id}",
            ['name' => 'view role data'],
            $this->header($token)
        );

        $update->assertStatus(409)
            ->assertJson(['success' => false]);

        auth()->forgetGuards();

        $delete = $this->deleteJson(
            "/api/admin-panel/permissions/{$permission->id}",
            [],
            $this->header($token)
        );

        $delete->assertStatus(409)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'name' => 'view roles',
        ]);
    }

    public function test_permission_tidak_ditemukan_menghasilkan_404(): void
    {
        $token = $this->tokenSuperAdmin();
        auth()->forgetGuards();

        $update = $this->putJson(
            '/api/admin-panel/permissions/99999',
            ['name' => 'download reports'],
            $this->header($token)
        );

        $update->assertStatus(404)
            ->assertJson(['success' => false]);

        auth()->forgetGuards();

        $delete = $this->deleteJson(
            '/api/admin-panel/permissions/99999',
            [],
            $this->header($token)
        );

        $delete->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    public function test_permission_duplikat_ditolak(): void
    {
        $token = $this->tokenSuperAdmin();
        auth()->forgetGuards();

        $response = $this->postJson(
            '/api/admin-panel/permissions',
            ['name' => 'view roles'],
            $this->header($token)
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_tanpa_token_tidak_bisa_mengelola_permission(): void
    {
        $this->postJson('/api/admin-panel/permissions', [
            'name' => 'export reports',
        ])->assertUnauthorized();

        $this->putJson('/api/admin-panel/permissions/1', [
            'name' => 'download reports',
        ])->assertUnauthorized();

        $this->deleteJson('/api/admin-panel/permissions/1')
            ->assertUnauthorized();
    }
}
