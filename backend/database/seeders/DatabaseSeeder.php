<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UniversitasSeeder::class,
            BidangOrganisasiSeeder::class,
            SuperAdminSeeder::class,
            RolePermissionSeeder::class,
        ]);
    }
}