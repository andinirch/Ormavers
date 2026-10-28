<?php

namespace Database\Seeders;

use App\Models\BidangOrganisasi;
use Illuminate\Database\Seeder;

class BidangOrganisasiSeeder extends Seeder
{
    public function run(): void
    {
        $bidangList = [
            // A. Organisasi eksekutif dan himpunan
            'Badan Eksekutif Mahasiswa (BEM)',
            'Himpunan Mahasiswa (HMJ)',

            // B. Bidang UKM Minat dan Bakat
            'Olahraga',
            'Seni dan Budaya',
            'Bela Diri',
            'Pecinta Alam dan Lingkungan',

            // C. Bidang keilmuan dan pengembangan diri
            'Penalaran dan Penelitian',
            'Teknologi dan Digital',
            'Kewirausahaan',
            'Bahasa dan Komunikasi',
            'Jurnalistik dan Media',
            'Desain dan Multimedia',
            'Pengembangan Karier',
            'Debat dan Diplomasi',

            // D. Bidang sosial dan kemanusiaan
            'Sosial dan Pengabdian Masyarakat',
            'Kesehatan dan Kemanusiaan',
            'Konseling dan Kesejahteraan Mahasiswa',
            'Kebencanaan',
            'Kepemimpinan dan Kedisiplinan',
            'Kepanduan',

            // E. Bidang keagamaan dan kaderisasi
            'Kerohanian Islam',
            'Al-Islam dan Kemuhammadiyahan',
            'Tilawah dan Seni Islami',
            'Organisasi Otonom Muhammadiyah',
            'Organisasi Otonom ‘Aisyiyah',
        ];

        foreach ($bidangList as $nama) {
            BidangOrganisasi::firstOrCreate(['nama_bidang' => $nama]);
        }
    }
}
