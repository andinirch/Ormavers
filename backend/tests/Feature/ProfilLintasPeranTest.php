<?php

namespace Tests\Feature;

use App\Models\Organisasi;
use App\Models\Pengguna;
use App\Models\Universitas;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UniversitasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilLintasPeranTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UniversitasSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
    }

    private function jpegHeader(): string
    {
        return "\xFF\xD8\xFF\xE0" . str_repeat("\x00", 100);
    }

    private function buatUserPeran(string $roleName, string $statusAkun = 'aktif'): array
    {
        $univ = Universitas::first();
        $user = Pengguna::factory()->create([
            'status_akun' => $statusAkun,
            'kata_sandi'  => 'Password123',
            'peran'       => match ($roleName) {
                'super_admin'   => 'super_admin',
                'administrator' => 'administrator',
                'akun_bem', 'akun_hmj_ukm' => 'akun_organisasi',
                'anggota'       => 'anggota',
            },
        ]);

        if (in_array($roleName, ['akun_bem', 'akun_hmj_ukm'], true)) {
            $jenis = ($roleName === 'akun_bem') ? 'bem' : 'hmj';
            Organisasi::create([
                'id_universitas'     => $univ->id_universitas,
                'id_akun_organisasi' => $user->id_pengguna,
                'nama_organisasi'    => strtoupper($jenis) . ' ' . uniqid(),
                'jenis_organisasi'   => $jenis,
                'nomor_telepon'      => '08123456789',
                'status_organisasi'  => 'aktif',
            ]);
        }

        $user->syncRoles($roleName);

        // Jika status menunggu / ditolak, berikan ability terbatas
        $abilities = ($statusAkun === 'aktif') ? ['*'] : ['status-registrasi'];
        $token = $user->createToken('auth_token', $abilities)->plainTextToken;

        return [$user, $token];
    }

    public function test_profil_sukses_untuk_semua_peran_aktif()
    {
        $roles = ['super_admin', 'administrator', 'akun_bem', 'akun_hmj_ukm', 'anggota'];

        foreach ($roles as $role) {
            $this->app['auth']->forgetGuards();
            [$user, $token] = $this->buatUserPeran($role, 'aktif');

            // 1. GET /api/profil
            $getRes = $this->withHeader('Authorization', "Bearer {$token}")
                ->getJson('/api/profil');

            $getRes->assertStatus(200)
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.id_pengguna', $user->id_pengguna)
                ->assertJsonPath('data.role', $role);

            // 2. PUT /api/profil
            $putRes = $this->withHeader('Authorization', "Bearer {$token}")
                ->putJson('/api/profil', [
                    'nama'           => "Nama Baru {$role}",
                    'username'       => $user->username, // Menambahkan username agar lolos ProfilRequest
                    'nomor_whatsapp' => '081234567890',
                ]);

            $putRes->assertStatus(200)
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.nama', "Nama Baru {$role}");

            // 3. PUT /api/profil/kata-sandi
            $pwdRes = $this->withHeader('Authorization', "Bearer {$token}")
                ->putJson('/api/profil/kata-sandi', [
                    'password_lama'       => 'Password123',
                    'password_baru'       => 'NewPassword123',
                    'konfirmasi_password' => 'NewPassword123',
                ]);

            $pwdRes->assertStatus(200)
                ->assertJsonPath('success', true);

            // Token baru setelah ubah password
            $loginRes = $this->postJson('/api/auth/login', [
                'username' => $user->username,
                'password' => 'NewPassword123',
            ]);

            $newToken = $loginRes->json('data.token');

            // 4. POST /api/profil/foto
            $this->app['auth']->forgetGuards();
            $fotoRes = $this->withHeader('Authorization', "Bearer {$newToken}")
                ->postJson('/api/profil/foto', [
                    'foto' => UploadedFile::fake()->createWithContent('avatar.jpg', $this->jpegHeader()),
                ]);

            $fotoRes->assertStatus(200)
                ->assertJsonPath('success', true);
        }
    }

    public function test_profil_ditolak_403_untuk_token_terbatas_menunggu_atau_ditolak()
    {
        $statusList = ['menunggu', 'ditolak'];

        foreach ($statusList as $status) {
            $this->app['auth']->forgetGuards();
            [$user, $token] = $this->buatUserPeran('anggota', $status);

            // GET /api/profil -> 403 (karena middleware akun.aktif menolak token tanpa '*')
            $resGet = $this->withHeader('Authorization', "Bearer {$token}")
                ->getJson('/api/profil');
            $resGet->assertStatus(403);

            // PUT /api/profil -> 403
            $resPut = $this->withHeader('Authorization', "Bearer {$token}")
                ->putJson('/api/profil', ['nama' => 'Test']);
            $resPut->assertStatus(403);

            // PUT /api/profil/kata-sandi -> 403
            $resPwd = $this->withHeader('Authorization', "Bearer {$token}")
                ->putJson('/api/profil/kata-sandi', [
                    'kata_sandi_lama' => 'Password123',
                    'kata_sandi_baru' => 'NewPass123',
                ]);
            $resPwd->assertStatus(403);

            // POST /api/profil/foto -> 403
            $resFoto = $this->withHeader('Authorization', "Bearer {$token}")
                ->postJson('/api/profil/foto', [
                    'foto' => UploadedFile::fake()->createWithContent('avatar.jpg', $this->jpegHeader()),
                ]);
            $resFoto->assertStatus(403);
        }
    }
}