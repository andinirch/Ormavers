<?php

namespace Database\Factories;

use App\Models\Organisasi;
use App\Models\PendaftaranOrganisasi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PendaftaranOrganisasi>
 */
class PendaftaranOrganisasiFactory extends Factory
{
    protected $model = PendaftaranOrganisasi::class;

    public function definition(): array
    {
        return [
            'id_organisasi'            => Organisasi::factory(),
            'status_pendaftaran'       => 'menunggu',
            'alasan_penolakan'         => null,
            'id_pengguna_verifikator'  => null,
            'tanggal_pendaftaran'      => now(),
            'tanggal_verifikasi'       => null,
        ];
    }

    public function disetujui(): static
    {
        return $this->state([
            'status_pendaftaran' => 'disetujui',
            'tanggal_verifikasi' => now(),
        ]);
    }

    public function ditolak(): static
    {
        return $this->state([
            'status_pendaftaran' => 'ditolak',
            'alasan_penolakan'   => 'Dokumen tidak lengkap',
            'tanggal_verifikasi' => now(),
        ]);
    }
}
