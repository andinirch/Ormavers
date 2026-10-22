<?php

namespace Database\Seeders;

use App\Models\Universitas;
use Illuminate\Database\Seeder;

class UniversitasSeeder extends Seeder
{
    public function run(): void
    {
        $daftarUniversitas = [
            'Universitas Muhammadiyah Malang',
            'Universitas Muhammadiyah Surabaya',
            'Universitas Muhammadiyah Gresik',
            'Universitas Muhammadiyah Sidoarjo',
            'Universitas Muhammadiyah Jember',
        ];

        foreach ($daftarUniversitas as $nama) {
            Universitas::firstOrCreate(
                ['nama_universitas' => $nama],
                ['logo' => null]
            );
        }
    }
}
