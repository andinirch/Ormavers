<?php

namespace Database\Factories;

use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pengguna>
 */
class PenggunaFactory extends Factory
{
    protected $model = Pengguna::class;

    public function definition(): array
    {
        return [
            'nama'        => fake()->name(),
            'username'    => fake()->unique()->userName(),
            'kata_sandi'  => 'password',
            'peran'       => 'anggota',
            'status_akun' => 'aktif',
            'foto_profil' => null,
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(['peran' => 'super_admin']);
    }

    public function administrator(): static
    {
        return $this->state(['peran' => 'administrator']);
    }

    public function akunOrganisasi(): static
    {
        return $this->state(['peran' => 'akun_organisasi']);
    }

    public function menunggu(): static
    {
        return $this->state(['status_akun' => 'menunggu']);
    }

    public function nonaktif(): static
    {
        return $this->state(['status_akun' => 'nonaktif']);
    }
}
