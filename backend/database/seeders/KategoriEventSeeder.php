<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KategoriEventSeeder extends Seeder
{
    public function run(): void
    {
        $kategoriList = [
            'Open Recruitment',
            'Kaderisasi',
            'Pelatihan Kepemimpinan',
            'Musyawarah dan Rapat Kerja',
            'Pelantikan dan Serah Terima Jabatan',
            'Seminar',
            'Workshop dan Pelatihan',
            'Kuliah Umum',
            'Diskusi dan Bedah Buku',
            'Lomba dan Kompetisi',
            'Kajian Keislaman',
            'Peringatan Hari Besar Islam',
            'Kegiatan Ramadan',
            'Bakti Sosial dan Pengabdian Masyarakat',
            'Kemanusiaan dan Tanggap Bencana',
            'Seni dan Budaya',
            'Olahraga',
            'Kewirausahaan',
            'Studi Banding dan Kunjungan',
            'Gathering dan Keakraban',
            'Milad dan Dies Natalis',
            'Musyawarah',
            'Forum',
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
