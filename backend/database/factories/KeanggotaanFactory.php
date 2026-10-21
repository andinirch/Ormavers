<?php

namespace Database\Factories;

use App\Models\Keanggotaan;
use App\Models\Organisasi;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Keanggotaan>
 */
class KeanggotaanFactory extends Factory
{
    protected $model = Keanggotaan::class;

    public function definition(): array
    {
        return [
            'id_pengguna'             => Pengguna::factory(),
            'id_organisasi'           => Organisasi::factory(),
            'status_keanggotaan'      => 'menunggu',
            'alasan_penolakan'        => null,
            'id_pengguna_verifikator' => null,
            'tanggal_pendaftaran'     => now(),
            'tanggal_verifikasi'      => null,
        ];
    }

    public function disetujui(): static
    {
        return $this->state([
            'status_keanggotaan' => 'disetujui',
            'tanggal_verifikasi' => now(),
        ]);
    }

    public function ditolak(): static
    {
        return $this->state([
            'status_keanggotaan' => 'ditolak',
            'alasan_penolakan'   => 'Tidak memenuhi kualifikasi',
            'tanggal_verifikasi' => now(),
        ]);
    }
}
