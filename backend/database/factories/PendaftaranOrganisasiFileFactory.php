<?php

namespace Database\Factories;

use App\Models\PendaftaranOrganisasi;
use App\Models\PendaftaranOrganisasiFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PendaftaranOrganisasiFile>
 */
class PendaftaranOrganisasiFileFactory extends Factory
{
    protected $model = PendaftaranOrganisasiFile::class;

    public function definition(): array
    {
        return [
            'id_pendaftaran_organisasi' => PendaftaranOrganisasi::factory(),
            'jenis_dokumen'             => 'sk_organisasi',
            'nama_file_asli'            => 'sk_organisasi.pdf',
            'path_file'                 => 'pendaftaran/sk_organisasi/' . fake()->uuid() . '.pdf',
            'ukuran_kb'                 => 100,
            'diunggah_pada'             => now(),
        ];
    }

    public function skKepengurusan(): static
    {
        return $this->state([
            'jenis_dokumen'  => 'sk_kepengurusan',
            'nama_file_asli' => 'sk_kepengurusan.pdf',
            'path_file'      => 'pendaftaran/sk_kepengurusan/' . fake()->uuid() . '.pdf',
        ]);
    }
}
