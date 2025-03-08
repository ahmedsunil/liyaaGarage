<?php

namespace Database\Seeders;

use App\Models\Vendor;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Vendor::create([
            'name'    => 'Auto Parts Galore',
            'address' => 'G. Mauzoom Sabudheriyaa Magu Male, 20052',
            'phone'   => '3324157',
            'email'   => 'shop@autoparts.mv',
        ]);
    }
}
