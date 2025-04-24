<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DefaultUserSeeder::class,
            PermissionsSeeder::class,
            RolesSeeder::class,
            CustomerSeeder::class,
            VehicleSeeder::class,
            VendorSeeder::class,
            BusinessSeeder::class,
        ]);
    }
}
