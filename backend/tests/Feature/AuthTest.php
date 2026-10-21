<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    private function buatPengguna(array $overrides = []): Pengguna
    {
        return Pengguna::factory()->create($overrides);
    }

    private function loginSebagai(Pengguna $pengguna): string
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => $pengguna->username,
            'password' => 'password',
        ]);

        return $response->json('data.token');
    }

    // -------------------------------------------------------
    // 1. Login super_admin sukses → 200, ada token & data
    // -------------------------------------------------------

    public function test_login_super_admin_sukses(): void
    {
        $pengguna = $this->buatPengguna([
            'peran'       => 'super_admin',
            'status_akun' => 'aktif',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => $pengguna->username,
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success', 'message',
                'data' => ['token', 'token_type', 'pengguna' => [
                    'id_pengguna', 'nama', 'username', 'peran', 'status_akun', 'foto_profil',
                ]],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Login berhasil',
                'data'    => [
                    'token_type' => 'Bearer',
                    'pengguna'   => [
                        'peran' => 'super_admin',
                    ],
                ],
            ]);
    }

    // -------------------------------------------------------
    // 2. GET /me dengan token → 200
    // -------------------------------------------------------

    public function test_me_dengan_token(): void
    {
        $pengguna = $this->buatPengguna(['peran' => 'super_admin']);
        $token    = $this->loginSebagai($pengguna);

        $response = $this->getJson('/api/auth/me', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'pengguna' => [
                        'username' => $pengguna->username,
                    ],
                ],
            ]);
    }

    // -------------------------------------------------------
    // 3. Logout → 200, token bekas ditolak 401
    // -------------------------------------------------------

    public function test_logout_lalu_token_ditolak(): void
    {
        $pengguna = $this->buatPengguna();
        $token    = $this->loginSebagai($pengguna);

        $this->postJson('/api/auth/logout', [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(200)
          ->assertJson(['success' => true, 'message' => 'Logout berhasil']);

        // Pastikan token benar-benar terhapus dari database
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Reset auth guard agar request berikutnya meng-autentikasi ulang dari token
        auth()->forgetGuards();

        $this->getJson('/api/auth/me', [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(401);
    }

    // -------------------------------------------------------
    // 4. Password salah → 401
    // -------------------------------------------------------

    public function test_password_salah(): void
    {
        $pengguna = $this->buatPengguna();

        $response = $this->postJson('/api/auth/login', [
            'username' => $pengguna->username,
            'password' => 'salah_sekali',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Username atau password salah',
            ]);
    }

    // -------------------------------------------------------
    // 5. Username tidak ada → 401 dengan pesan sama
    // -------------------------------------------------------

    public function test_username_tidak_ada(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => 'tidakada',
            'password' => 'password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Username atau password salah',
            ]);
    }

    // -------------------------------------------------------
    // 6. Username kosong → 422
    // -------------------------------------------------------

    public function test_username_kosong(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data tidak valid',
            ])
            ->assertJsonValidationErrors(['username']);
    }

    // -------------------------------------------------------
    // 7. Status menunggu dan ditolak → Login Terbatas 200
    // -------------------------------------------------------

    public function test_status_menunggu(): void
    {
        $pengguna = $this->buatPengguna(['status_akun' => 'menunggu']);

        $response = $this->postJson('/api/auth/login', [
            'username' => $pengguna->username,
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login terbatas: hanya dapat melihat status registrasi',
                'data'    => [
                    'akses_terbatas' => true,
                    'permissions'    => [],
                ],
            ]);

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);

        // Token terbatas ditolak di rute biasa
        auth()->forgetGuards();
        $this->getJson('/api/auth/me', [
            'Authorization' => 'Bearer ' . $token,
        ])->assertStatus(403)
          ->assertJson([
              'success' => false,
              'message' => 'Silakan login ulang',
          ]);
    }

    public function test_status_ditolak(): void
    {
        $pengguna = $this->buatPengguna(['status_akun' => 'ditolak']);

        $response = $this->postJson('/api/auth/login', [
            'username' => $pengguna->username,
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login terbatas: hanya dapat melihat status registrasi',
                'data'    => [
                    'akses_terbatas' => true,
                    'permissions'    => [],
                ],
            ]);

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);

        // Token terbatas ditolak di rute biasa
        auth()->forgetGuards();
        $this->getJson('/api/auth/me', [
            'Authorization' => 'Bearer ' . $token,
        ])->assertStatus(403)
          ->assertJson([
              'success' => false,
              'message' => 'Silakan login ulang',
          ]);
    }

    // -------------------------------------------------------
    // 8. Status nonaktif → 403
    // -------------------------------------------------------

    public function test_status_nonaktif(): void
    {
        $pengguna = $this->buatPengguna(['status_akun' => 'nonaktif']);

        $response = $this->postJson('/api/auth/login', [
            'username' => $pengguna->username,
            'password' => 'password',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akun dinonaktifkan',
            ]);
    }

    public function test_status_ditangguhkan(): void
    {
        $pengguna = $this->buatPengguna(['status_akun' => 'ditangguhkan']);

        $response = $this->postJson('/api/auth/login', [
            'username' => $pengguna->username,
            'password' => 'password',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akun ditangguhkan',
            ]);
    }

    // -------------------------------------------------------
    // 9. Percobaan ke-6 → 429
    // -------------------------------------------------------

    public function test_rate_limit_login(): void
    {
        $pengguna = $this->buatPengguna();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'username' => $pengguna->username,
                'password' => 'salah',
            ]);
        }

        $response = $this->postJson('/api/auth/login', [
            'username' => $pengguna->username,
            'password' => 'salah',
        ]);

        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
                'message' => 'Terlalu banyak percobaan, coba lagi nanti',
            ]);
    }

    // -------------------------------------------------------
    // 10. GET /me tanpa token → 401
    // -------------------------------------------------------

    public function test_me_tanpa_token(): void
    {
        $this->getJson('/api/auth/me')
            ->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'Unauthenticated']);
    }

    // -------------------------------------------------------
    // 11. POST /logout tanpa token → 401
    // -------------------------------------------------------

    public function test_logout_tanpa_token(): void
    {
        $this->postJson('/api/auth/logout')
            ->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'Unauthenticated']);
    }

    // -------------------------------------------------------
    // 12. Response tidak memuat kata_sandi
    // -------------------------------------------------------

    public function test_response_tidak_memuat_kata_sandi(): void
    {
        $pengguna = $this->buatPengguna(['peran' => 'super_admin']);

        $response = $this->postJson('/api/auth/login', [
            'username' => $pengguna->username,
            'password' => 'password',
        ]);

        $response->assertStatus(200);

        $data = $response->json('data.pengguna');
        $this->assertArrayNotHasKey('kata_sandi', $data);
        $this->assertArrayNotHasKey('password', $data);
    }
}
