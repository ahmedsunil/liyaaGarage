<?php

namespace Database\Seeders;

use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Business;
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
            Business::class,
        ]);
    }
}
