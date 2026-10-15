<?php

namespace Database\Seeders;

use App\Models\Pengguna;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('SUPERADMIN_PASSWORD');

        if (! $password) {
            throw new \RuntimeException('Isi SUPERADMIN_PASSWORD di file .env dulu.');
        }

        Pengguna::updateOrCreate(
            ['username' => 'superadmin'],
            [
                'nama' => 'Super Admin',
                'kata_sandi' => $password,
                'peran' => 'super_admin',
                'status_akun' => 'aktif',
            ]
        );
    }
}