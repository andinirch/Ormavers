<?php

namespace Tests\Feature;

use App\Models\LogAktivitas;
use App\Models\Pengguna;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminPanelLogTest extends TestCase
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
        $sa = Pengguna::factory()->superAdmin()->create();
        $token = $sa->createToken('auth_token')->plainTextToken;

        return [$sa, $token, ['Authorization' => "Bearer {$token}"]];
    }

    private function tokenPengguna(string $peran = 'anggota'): array
    {
        $user = Pengguna::factory()->create(['peran' => $peran, 'status_akun' => 'aktif']);
        $token = $user->createToken('auth_token')->plainTextToken;

        return [$user, $token, ['Authorization' => "Bearer {$token}"]];
    }

    // ──── B4: GET /api/admin-panel/activities ────────────────────────

    public function test_daftar_aktivitas_terurut_terbaru_dulu(): void
    {
        [$sa, , $headers] = $this->tokenSuperAdmin();

        $log1 = LogAktivitas::create([
            'id_pengguna'     => $sa->id_pengguna,
            'jenis_aktivitas' => 'login',
            'keterangan'      => 'Log Pertama',
            'waktu_aktivitas' => now()->subMinutes(10),
        ]);

        $log2 = LogAktivitas::create([
            'id_pengguna'     => $sa->id_pengguna,
            'jenis_aktivitas' => 'kelola_akun',
            'keterangan'      => 'Log Kedua',
            'waktu_aktivitas' => now()->subMinutes(2),
        ]);

        auth()->forgetGuards();

        $response = $this->getJson('/api/admin-panel/activities', $headers);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'activities' => [
                        '*' => [
                            'id_log',
                            'pengguna' => ['id_pengguna', 'nama', 'role'],
                            'jenis_aktivitas',
                            'keterangan',
                            'waktu_aktivitas',
                        ],
                    ],
                    'meta' => ['total', 'per_page', 'current_page', 'last_page'],
                ],
            ]);

        $activities = $response->json('data.activities');
        $this->assertSame($log2->id_log, $activities[0]['id_log']);
        $this->assertSame($log1->id_log, $activities[1]['id_log']);
    }

    public function test_filter_id_pengguna_dan_jenis(): void
    {
        [$sa, , $headers] = $this->tokenSuperAdmin();
        $admin = Pengguna::factory()->administrator()->create();

        LogAktivitas::create([
            'id_pengguna'     => $sa->id_pengguna,
            'jenis_aktivitas' => 'login',
            'keterangan'      => 'SA login',
            'waktu_aktivitas' => now(),
        ]);

        LogAktivitas::create([
            'id_pengguna'     => $admin->id_pengguna,
            'jenis_aktivitas' => 'login',
            'keterangan'      => 'Admin login',
            'waktu_aktivitas' => now(),
        ]);

        LogAktivitas::create([
            'id_pengguna'     => $admin->id_pengguna,
            'jenis_aktivitas' => 'verifikasi',
            'keterangan'      => 'Admin verifikasi',
            'waktu_aktivitas' => now(),
        ]);

        auth()->forgetGuards();

        $response = $this->getJson("/api/admin-panel/activities?id_pengguna={$admin->id_pengguna}&jenis=verifikasi", $headers);
        $response->assertOk();

        $activities = $response->json('data.activities');
        $this->assertCount(1, $activities);
        $this->assertSame('Admin verifikasi', $activities[0]['keterangan']);
    }

    public function test_filter_rentang_tanggal_dari_dan_sampai(): void
    {
        [$sa, , $headers] = $this->tokenSuperAdmin();

        // 3 hari lalu
        LogAktivitas::create([
            'id_pengguna'     => $sa->id_pengguna,
            'jenis_aktivitas' => 'login',
            'keterangan'      => 'Log 3 hari lalu',
            'waktu_aktivitas' => now()->subDays(3),
        ]);

        // Kemarin
        LogAktivitas::create([
            'id_pengguna'     => $sa->id_pengguna,
            'jenis_aktivitas' => 'login',
            'keterangan'      => 'Log kemarin',
            'waktu_aktivitas' => now()->subDay(),
        ]);

        // Hari ini
        LogAktivitas::create([
            'id_pengguna'     => $sa->id_pengguna,
            'jenis_aktivitas' => 'login',
            'keterangan'      => 'Log hari ini',
            'waktu_aktivitas' => now(),
        ]);

        auth()->forgetGuards();

        $kemarinStr = now()->subDay()->format('Y-m-d');
        $hariIniStr = now()->format('Y-m-d');

        $response = $this->getJson("/api/admin-panel/activities?dari={$kemarinStr}&sampai={$hariIniStr}", $headers);
        $response->assertOk();

        $activities = $response->json('data.activities');
        $this->assertCount(2, $activities);
    }

    // ──── B5: GET /api/admin-panel/activities/filter-options ─────────

    public function test_filter_options_memuat_jenis_dan_daftar_pengguna(): void
    {
        [$sa, , $headers] = $this->tokenSuperAdmin();
        $admin = Pengguna::factory()->administrator()->create(['nama' => 'Admin Khusus']);

        LogAktivitas::create([
            'id_pengguna'     => $sa->id_pengguna,
            'jenis_aktivitas' => 'login',
            'keterangan'      => 'login sa',
            'waktu_aktivitas' => now(),
        ]);

        LogAktivitas::create([
            'id_pengguna'     => $admin->id_pengguna,
            'jenis_aktivitas' => 'verifikasi',
            'keterangan'      => 'verifikasi admin',
            'waktu_aktivitas' => now(),
        ]);

        auth()->forgetGuards();

        $response = $this->getJson('/api/admin-panel/activities/filter-options', $headers);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'jenis',
                    'pengguna' => [
                        '*' => ['id_pengguna', 'nama'],
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertContains('login', $data['jenis']);
        $this->assertContains('verifikasi', $data['jenis']);

        $userNames = collect($data['pengguna'])->pluck('nama');
        $this->assertContains('Admin Khusus', $userNames);
    }

    public function test_akses_tanpa_token_401_dan_non_super_admin_403(): void
    {
        $endpoints = [
            ['GET', '/api/admin-panel/activities'],
            ['GET', '/api/admin-panel/activities/filter-options'],
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
