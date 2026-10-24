<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KelolaAdministratorTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    private function tokenPengguna(Pengguna $pengguna): string
    {
        return $pengguna->createToken('auth_token')->plainTextToken;
    }

    private function tokenSuperAdmin(): string
    {
        $superAdmin = Pengguna::factory()->superAdmin()->create();

        return $this->tokenPengguna($superAdmin);
    }

    // -------------------------------------------------------
    // Happy Path Tests
    // -------------------------------------------------------

    public function test_super_admin_dapat_melihat_daftar_administrator(): void
    {
        $token = $this->tokenSuperAdmin();

        // Buat beberapa pengguna dengan peran berbeda
        $admin1 = Pengguna::factory()->administrator()->create([
            'nama'        => 'Admin Pertama',
            'dibuat_pada' => now()->subMinute(),
        ]);
        $admin2 = Pengguna::factory()->administrator()->create([
            'nama'        => 'Admin Kedua',
            'dibuat_pada' => now(),
        ]);
        Pengguna::factory()->create(['nama' => 'Anggota Biasa']);

        $response = $this->getJson('/api/superadmin/administrators', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daftar Administrator berhasil diambil',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'administrators' => [
                        '*' => [
                            'id_pengguna',
                            'nama',
                            'username',
                            'peran',
                            'status_akun',
                            'foto_profil',
                        ],
                    ],
                ],
            ]);

        $administrators = $response->json('data.administrators');
        $this->assertCount(2, $administrators);
        $this->assertEquals($admin2->id_pengguna, $administrators[0]['id_pengguna']);
        $this->assertEquals($admin1->id_pengguna, $administrators[1]['id_pengguna']);
        foreach ($administrators as $admin) {
            $this->assertEquals('administrator', $admin['peran']);
        }
    }

    public function test_super_admin_dapat_menambah_administrator(): void
    {
        $token = $this->tokenSuperAdmin();

        $payload = [
            'nama'     => 'Budi Administrator',
            'username' => 'budi_admin',
            'password' => 'adminpassword123',
        ];

        $response = $this->postJson('/api/superadmin/administrators', $payload, [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Akun Administrator Berhasil Ditambahkan',
                'data'    => [
                    'administrator' => [
                        'nama'        => 'Budi Administrator',
                        'username'    => 'budi_admin',
                        'peran'       => 'administrator',
                        'status_akun' => 'aktif',
                        'foto_profil' => null,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('pengguna', [
            'username'    => 'budi_admin',
            'peran'       => 'administrator',
            'status_akun' => 'aktif',
        ]);

        $penggunaBaru = Pengguna::where('username', 'budi_admin')->first();
        $this->assertNotNull($penggunaBaru);
        $this->assertTrue(Hash::check('adminpassword123', $penggunaBaru->kata_sandi));
    }

    public function test_administrator_baru_dapat_login(): void
    {
        $token = $this->tokenSuperAdmin();

        $this->postJson('/api/superadmin/administrators', [
            'nama'     => 'Admin Baru',
            'username' => 'admin_baru',
            'password' => 'password1234',
        ], [
            'Authorization' => 'Bearer ' . $token,
        ])->assertStatus(201);

        $loginResponse = $this->postJson('/api/auth/login', [
            'username' => 'admin_baru',
            'password' => 'password1234',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login berhasil',
                'data'    => [
                    'pengguna' => [
                        'username' => 'admin_baru',
                        'peran'    => 'administrator',
                    ],
                ],
            ]);
    }

    public function test_super_admin_dapat_menonaktifkan_administrator_dan_token_lama_ditolak(): void
    {
        $superAdmin = Pengguna::factory()->superAdmin()->create();
        $tokenSuperAdmin = $this->tokenPengguna($superAdmin);

        $admin = Pengguna::factory()->administrator()->create();
        $tokenAdmin = $this->tokenPengguna($admin);

        // Pastikan token admin awalnya valid
        $this->getJson('/api/auth/me', [
            'Authorization' => 'Bearer ' . $tokenAdmin,
        ])->assertStatus(200);

        // Reset auth guard agar request berikutnya meng-autentikasi ulang dari token
        auth()->forgetGuards();

        // Nonaktifkan administrator
        $response = $this->patchJson("/api/superadmin/administrators/{$admin->id_pengguna}/nonaktif", [], [
            'Authorization' => 'Bearer ' . $tokenSuperAdmin,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Akun Administrator Berhasil Dinonaktifkan',
                'data'    => [
                    'administrator' => [
                        'id_pengguna' => $admin->id_pengguna,
                        'status_akun' => 'nonaktif',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('pengguna', [
            'id_pengguna' => $admin->id_pengguna,
            'status_akun' => 'nonaktif',
        ]);

        // Reset auth guard agar request berikutnya meng-autentikasi ulang dari token yang sudah dihapus
        auth()->forgetGuards();

        // Token lama admin harus ditolak (401 Unauthenticated karena sudah dihapus dari tabel token)
        $this->getJson('/api/auth/me', [
            'Authorization' => 'Bearer ' . $tokenAdmin,
        ])->assertStatus(401);
    }

    // -------------------------------------------------------
    // Negative Path & Security Tests
    // -------------------------------------------------------

    public function test_akses_tanpa_token_ditolak(): void
    {
        $this->getJson('/api/superadmin/administrators')
            ->assertStatus(401);

        $this->postJson('/api/superadmin/administrators', [])
            ->assertStatus(401);

        $this->patchJson('/api/superadmin/administrators/1/nonaktif')
            ->assertStatus(401);
    }

    public function test_peran_selain_super_admin_ditolak_403(): void
    {
        $peranList = ['administrator', 'anggota', 'akun_organisasi'];

        foreach ($peranList as $peran) {
            $user = Pengguna::factory()->create(['peran' => $peran, 'status_akun' => 'aktif']);
            $token = $this->tokenPengguna($user);
            $headers = ['Authorization' => 'Bearer ' . $token];

            auth()->forgetGuards();
            $this->getJson('/api/superadmin/administrators', $headers)
                ->assertStatus(403);

            auth()->forgetGuards();
            $this->postJson('/api/superadmin/administrators', [
                'nama'     => 'Test',
                'username' => 'test_' . $peran,
                'password' => 'password123',
            ], $headers)->assertStatus(403);

            auth()->forgetGuards();
            $this->patchJson('/api/superadmin/administrators/1/nonaktif', [], $headers)
                ->assertStatus(403);
        }
    }

    public function test_validasi_tambah_administrator_gagal(): void
    {
        $token = $this->tokenSuperAdmin();
        $headers = ['Authorization' => 'Bearer ' . $token];

        // Field kosong
        $response = $this->postJson('/api/superadmin/administrators', [], $headers);
        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid',
            ])
            ->assertJsonValidationErrors(['nama', 'username', 'password']);

        // Username sudah digunakan
        Pengguna::factory()->create(['username' => 'admin_eksis']);
        $responseDuplicate = $this->postJson('/api/superadmin/administrators', [
            'nama'     => 'Nama Baru',
            'username' => 'admin_eksis',
            'password' => 'password123',
        ], $headers);
        $responseDuplicate->assertStatus(422)
            ->assertJsonValidationErrors(['username']);

        // Password kurang dari 8 karakter
        $responsePassword = $this->postJson('/api/superadmin/administrators', [
            'nama'     => 'Nama Baru',
            'username' => 'admin_unik',
            'password' => 'pendek',
        ], $headers);
        $responsePassword->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_field_peran_dan_status_akun_pada_request_tambah_diabaikan(): void
    {
        $token = $this->tokenSuperAdmin();

        $payload = [
            'nama'        => 'Penyusup Super',
            'username'    => 'penyusup_super',
            'password'    => 'password123',
            'peran'       => 'super_admin',
            'status_akun' => 'nonaktif',
        ];

        $response = $this->postJson('/api/superadmin/administrators', $payload, [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'administrator' => [
                        'peran'       => 'administrator',
                        'status_akun' => 'aktif',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('pengguna', [
            'username'    => 'penyusup_super',
            'peran'       => 'administrator',
            'status_akun' => 'aktif',
        ]);
    }

    public function test_administrator_nonaktif_tidak_bisa_login(): void
    {
        $admin = Pengguna::factory()->administrator()->nonaktif()->create();

        $response = $this->postJson('/api/auth/login', [
            'username' => $admin->username,
            'password' => 'password',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akun dinonaktifkan',
            ]);
    }

    public function test_nonaktifkan_id_tidak_ada_mengembalikan_404(): void
    {
        $token = $this->tokenSuperAdmin();

        $response = $this->patchJson('/api/superadmin/administrators/99999/nonaktif', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Administrator tidak ditemukan',
            ]);
    }

    public function test_nonaktifkan_id_bukan_administrator_mengembalikan_404(): void
    {
        $token = $this->tokenSuperAdmin();

        $superAdmin = Pengguna::factory()->superAdmin()->create();
        $anggota = Pengguna::factory()->create(['peran' => 'anggota']);

        $this->patchJson("/api/superadmin/administrators/{$superAdmin->id_pengguna}/nonaktif", [], [
            'Authorization' => 'Bearer ' . $token,
        ])->assertStatus(404);

        $this->patchJson("/api/superadmin/administrators/{$anggota->id_pengguna}/nonaktif", [], [
            'Authorization' => 'Bearer ' . $token,
        ])->assertStatus(404);
    }

    public function test_nonaktifkan_yang_sudah_nonaktif_mengembalikan_409(): void
    {
        $token = $this->tokenSuperAdmin();

        $admin = Pengguna::factory()->administrator()->nonaktif()->create();

        $response = $this->patchJson("/api/superadmin/administrators/{$admin->id_pengguna}/nonaktif", [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Akun Administrator sudah dinonaktifkan',
            ]);
    }

    public function test_nonaktifkan_dengan_id_non_angka_mengembalikan_404(): void
    {
        $token = $this->tokenSuperAdmin();

        $response = $this->patchJson('/api/superadmin/administrators/abc/nonaktif', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(404);
    }

    public function test_response_tidak_memuat_kata_sandi(): void
    {
        $token = $this->tokenSuperAdmin();
        $headers = ['Authorization' => 'Bearer ' . $token];

        // Test pada POST
        $createResponse = $this->postJson('/api/superadmin/administrators', [
            'nama'     => 'Cek Password',
            'username' => 'cek_password',
            'password' => 'secretpassword123',
        ], $headers);

        $createResponse->assertStatus(201);
        $this->assertArrayNotHasKey('kata_sandi', $createResponse->json('data.administrator'));
        $this->assertStringNotContainsString('secretpassword123', $createResponse->getContent());

        $id = $createResponse->json('data.administrator.id_pengguna');

        // Test pada GET
        $getResponse = $this->getJson('/api/superadmin/administrators', $headers);
        $getResponse->assertStatus(200);
        foreach ($getResponse->json('data.administrators') as $item) {
            $this->assertArrayNotHasKey('kata_sandi', $item);
        }

        // Test pada PATCH
        $patchResponse = $this->patchJson("/api/superadmin/administrators/{$id}/nonaktif", [], $headers);
        $patchResponse->assertStatus(200);
        $this->assertArrayNotHasKey('kata_sandi', $patchResponse->json('data.administrator'));
    }
    
public function test_super_admin_dapat_mengubah_status_administrator_menjadi_nonaktif_dan_token_dicabut(): void
{
    $tokenSuperAdmin = $this->tokenSuperAdmin();

    $admin = Pengguna::factory()->administrator()->create();
    $tokenAdmin = $this->tokenPengguna($admin);

    $response = $this->patchJson(
        "/api/superadmin/administrators/{$admin->id_pengguna}/status",
        ['status_akun' => 'nonaktif'],
        ['Authorization' => 'Bearer ' . $tokenSuperAdmin]
    );

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Akun Administrator Berhasil Dinonaktifkan',
            'data' => [
                'administrator' => [
                    'id_pengguna' => $admin->id_pengguna,
                    'status_akun' => 'nonaktif',
                ],
            ],
        ]);

    $this->assertDatabaseHas('pengguna', [
        'id_pengguna' => $admin->id_pengguna,
        'status_akun' => 'nonaktif',
    ]);

    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id' => $admin->id_pengguna,
        'token' => hash('sha256', $tokenAdmin),
    ]);

    auth()->forgetGuards();

    $this->getJson('/api/auth/me', [
        'Authorization' => 'Bearer ' . $tokenAdmin,
    ])->assertStatus(401);
}

public function test_super_admin_dapat_mengaktifkan_kembali_administrator(): void
{
    $tokenSuperAdmin = $this->tokenSuperAdmin();

    $admin = Pengguna::factory()->administrator()->nonaktif()->create();

    $response = $this->patchJson(
        "/api/superadmin/administrators/{$admin->id_pengguna}/status",
        ['status_akun' => 'aktif'],
        ['Authorization' => 'Bearer ' . $tokenSuperAdmin]
    );

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Akun Administrator Berhasil Diaktifkan',
            'data' => [
                'administrator' => [
                    'id_pengguna' => $admin->id_pengguna,
                    'status_akun' => 'aktif',
                ],
            ],
        ]);

    $this->assertDatabaseHas('pengguna', [
        'id_pengguna' => $admin->id_pengguna,
        'status_akun' => 'aktif',
    ]);
}

public function test_ubah_status_administrator_menolak_status_tidak_valid(): void
{
    $tokenSuperAdmin = $this->tokenSuperAdmin();

    $admin = Pengguna::factory()->administrator()->create();

    $response = $this->patchJson(
        "/api/superadmin/administrators/{$admin->id_pengguna}/status",
        ['status_akun' => 'ditangguhkan'],
        ['Authorization' => 'Bearer ' . $tokenSuperAdmin]
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status_akun']);

    $this->assertDatabaseHas('pengguna', [
        'id_pengguna' => $admin->id_pengguna,
        'status_akun' => 'aktif',
    ]);
}

public function test_ubah_status_administrator_yang_tidak_ditemukan_mengembalikan_404(): void
{
    $tokenSuperAdmin = $this->tokenSuperAdmin();

    $response = $this->patchJson(
        '/api/superadmin/administrators/99999/status',
        ['status_akun' => 'aktif'],
        ['Authorization' => 'Bearer ' . $tokenSuperAdmin]
    );

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Administrator tidak ditemukan',
        ]);
}

}
