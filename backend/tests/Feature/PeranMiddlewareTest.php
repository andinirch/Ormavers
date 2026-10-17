<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PeranMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Rute dummy untuk tes middleware peran
        Route::middleware(['auth:sanctum', 'akun.aktif', 'peran:administrator,super_admin'])
            ->get('/api/test-peran', function () {
                return response()->json(['success' => true, 'message' => 'OK']);
            });
    }

    private function buatDanLogin(array $overrides = []): array
    {
        $pengguna = Pengguna::factory()->create($overrides);

        $response = $this->postJson('/api/auth/login', [
            'username' => $pengguna->username,
            'password' => 'password',
        ]);

        return [$pengguna, $response->json('data.token')];
    }

    // -------------------------------------------------------
    // 1. Administrator akses rute peran:administrator → 200
    // -------------------------------------------------------

    public function test_administrator_akses_rute_yang_diizinkan(): void
    {
        [$pengguna, $token] = $this->buatDanLogin(['peran' => 'administrator']);

        $this->getJson('/api/test-peran', [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(200)
          ->assertJson(['success' => true]);
    }

    // -------------------------------------------------------
    // 2. Anggota akses rute peran:administrator → 403
    // -------------------------------------------------------

    public function test_anggota_ditolak_akses_rute_administrator(): void
    {
        [$pengguna, $token] = $this->buatDanLogin(['peran' => 'anggota']);

        $this->getJson('/api/test-peran', [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(403)
          ->assertJson([
              'success' => false,
              'message' => 'Akses ditolak',
          ]);
    }

    // -------------------------------------------------------
    // 3. Tanpa token → 401
    // -------------------------------------------------------

    public function test_tanpa_token_ditolak(): void
    {
        $this->getJson('/api/test-peran')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated',
            ]);
    }

    // -------------------------------------------------------
    // 4. Token valid tapi akun dinonaktifkan setelah login → 403
    // -------------------------------------------------------

    public function test_akun_dinonaktifkan_setelah_login(): void
    {
        [$pengguna, $token] = $this->buatDanLogin(['peran' => 'administrator']);

        // Simulasi admin menonaktifkan akun setelah user sudah login
        $pengguna->update(['status_akun' => 'nonaktif']);

        $this->getJson('/api/test-peran', [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(403)
          ->assertJson([
              'success' => false,
              'message' => 'Akun dinonaktifkan',
          ]);
    }
}
