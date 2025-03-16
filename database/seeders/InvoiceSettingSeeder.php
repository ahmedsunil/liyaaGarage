<?php

namespace Database\Seeders;

use App\Models\InvoiceSetting;
use Illuminate\Database\Seeder;

class InvoiceSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        InvoiceSetting::create([
            'name' => 'Your Company Name',
            'contact' => '1234567890',
            'street_address' => '123 Main St',
            'email' => 'info@example.com',
            'account_number' => '1234567890',
            'invoice_number_prefix' => 'INV',
            'footer_text' => 'Thank you for your business.',
            'copyright' => 'Copyright 2023 Your Company',
        ]);
    }
}
