<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionsSeeder::class,
            DefaultUserSeeder::class,
            RolesSeeder::class,
            CustomerSeeder::class,
            VendorSeeder::class,
            BusinessSeeder::class,
        ]);
    }
}
