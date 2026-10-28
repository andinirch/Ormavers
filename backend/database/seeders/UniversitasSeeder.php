<?php

namespace Database\Seeders;

use App\Models\Universitas;
use Illuminate\Database\Seeder;

class UniversitasSeeder extends Seeder
{
    public function run(): void
    {
        $daftarUniversitas = [
            // A. Universitas
            'Universitas Muhammadiyah Gresik (UMG)',
            'Universitas Muhammadiyah Malang (UMM)',
            'Universitas Muhammadiyah Surabaya',
            'Universitas Muhammadiyah Sidoarjo (UMSIDA)',
            'Universitas Muhammadiyah Jember',
            'Universitas Muhammadiyah Lamongan (UMLA)',
            'Universitas Muhammadiyah Ponorogo',
            'Universitas Muhammadiyah Madiun',

            // B. Institut
            'Institut Ahmad Dahlan Probolinggo',
            'Institut Studi Islam Muhammadiyah Pacitan',
            'Institut Teknologi Bisnis dan Kesehatan Muhammadiyah Tulungagung',
            'Institut Teknologi dan Bisnis Ahmad Dahlan Lamongan',

            // C. Sekolah Tinggi
            'Sekolah Tinggi Agama Islam Muhammadiyah Tulungagung',
            'Sekolah Tinggi Agama Islam Muhammadiyah Paciran',
            'Sekolah Tinggi Ilmu Ekonomi Muhammadiyah Tuban',
            'Sekolah Tinggi Ilmu Kesehatan Muhammadiyah Bojonegoro',
            'Sekolah Tinggi Keguruan dan Ilmu Pendidikan Muhammadiyah Lumajang',
            'Sekolah Tinggi Ilmu Tarbiyah Muhammadiyah Bangil',
            'Sekolah Tinggi Ilmu Tarbiyah Muhammadiyah Bojonegoro',
            'Sekolah Tinggi Ilmu Tarbiyah Muhammadiyah Kediri',
            'Sekolah Tinggi Ilmu Tarbiyah Muhammadiyah Lumajang',
            'Sekolah Tinggi Ilmu Tarbiyah Muhammadiyah Ngawi',
            'Sekolah Tinggi Ilmu Tarbiyah Muhammadiyah Paciran',
            'Sekolah Tinggi Teknologi Muhammadiyah AR. Fachrudin Bojonegoro',
        ];

        foreach ($daftarUniversitas as $nama) {
            Universitas::firstOrCreate(
                ['nama_universitas' => $nama],
                ['logo' => null]
            );
        }
    }
}
