<?php

namespace Database\Factories;

use App\Models\Pengguna;
use App\Models\ProfilAnggota;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfilAnggota>
 */
class ProfilAnggotaFactory extends Factory
{
    protected $model = ProfilAnggota::class;

    public function definition(): array
    {
        return [
            'id_pengguna'    => Pengguna::factory(),
            'nim'            => fake()->numerify('##########'),
            'nomor_whatsapp' => fake()->numerify('08##########'),
            'foto_ktm'       => null,
            'foto_profil'    => null,
            'id_universitas' => null,
        ];
    }
}
