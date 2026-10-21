<?php

namespace Database\Factories;

use App\Models\Organisasi;
use App\Models\Pengguna;
use App\Models\Universitas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organisasi>
 */
class OrganisasiFactory extends Factory
{
    protected $model = Organisasi::class;

    public function definition(): array
    {
        return [
            'id_universitas'     => Universitas::factory(),
            'id_organisasi_induk'=> null,
            'id_akun_organisasi' => Pengguna::factory()->akunOrganisasi(),
            'id_bidang'          => null,
            'nama_organisasi'    => 'BEM ' . fake()->unique()->words(2, true),
            'jenis_organisasi'   => 'bem',
            'nomor_telepon'      => fake()->numerify('08##########'),
            'deskripsi'          => fake()->paragraph(),
            'alamat'             => fake()->address(),
            'logo'               => null,
            'tanggal_mulai_sk'   => null,
            'tanggal_berakhir_sk'=> null,
            'status_organisasi'  => 'menunggu',
        ];
    }

    public function aktif(): static
    {
        return $this->state(['status_organisasi' => 'aktif']);
    }

    public function ditangguhkan(): static
    {
        return $this->state(['status_organisasi' => 'ditangguhkan']);
    }

    public function nonaktif(): static
    {
        return $this->state(['status_organisasi' => 'nonaktif']);
    }
}
