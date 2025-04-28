<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use App\Support\Enums\UserStatuses;

class DefaultUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'status' => UserStatuses::Active,
            ]
        );

        // Create/update a guest user and assign a guest role
        $guestUser = User::updateOrCreate(
            ['email' => 'zahir@garage.mv'],
            [
                'name' => 'Ali Zahir',
                'email_verified_at' => now(),
                'password' => bcrypt('Root@1234!'),
                'status' => UserStatuses::Active,
            ]
        );

        // Assign guest role (assuming you're using Spatie Laravel Permissions or similar)
        $guestUser->assignRole('guest');
    }
}
