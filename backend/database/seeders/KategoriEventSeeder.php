<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KategoriEventSeeder extends Seeder
{
    public function run(): void
    {
        $kategoriList = [
            'Seminar',
            'Workshop',
            'Lomba',
            'Pelatihan',
            'Webinar',
            'Konferensi',
            'Bakti Sosial',
            'Penggalangan Dana',
            'Musyawarah',
            'Kajian',
            'Olahraga',
            'Seni dan Budaya',
            'Networking',
            'Lainnya',
        ];

        foreach ($kategoriList as $nama) {
            DB::table('kategori_event')->updateOrInsert(
                ['nama_kategori' => $nama],
                [
                    'nama_kategori'  => $nama,
                    'dibuat_pada'    => now(),
                    'diperbarui_pada' => now(),
                ],
            );
        }
    }
}
