<?php

namespace Database\Seeders;

use App\Models\BidangOrganisasi;
use Illuminate\Database\Seeder;

class BidangOrganisasiSeeder extends Seeder
{
    public function run(): void
    {
        $bidangList = [
            'Badan Eksekutif Mahasiswa (BEM)',
            'Legislatif Mahasiswa (MPM/Senat)',
            'Bidang Penalaran & Keilmuan',
            'Bidang Minat & Bakat (Olahraga)',
            'Bidang Seni & Budaya',
            'Bidang Kerohanian / Keagamaan',
            'Bidang Sosial, Pengabdian & Lingkungan',
            'Bidang Kewirausahaan & Ekonomi Kreatif',
        ];

        foreach ($bidangList as $nama) {
            BidangOrganisasi::firstOrCreate(['nama_bidang' => $nama]);
        }
    }
}
