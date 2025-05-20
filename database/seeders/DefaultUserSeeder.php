<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use App\Support\Enums\UserStatuses;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;

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

        // Assign guest role (using Spatie Laravel Permissions)
        // Check if the role exists before assigning it
        if (Role::where('name', 'guest')->exists()) {
            $guestUser->assignRole('guest');
        } else {
            // Log a message if the command property is available
            if (isset($this->command)) {
                $this->command->info('Guest role does not exist. Please run the RolesSeeder first.');
            } else {
                // Otherwise, just log to the application log
                Log::warning('Guest role does not exist. Please run the RolesSeeder first.');
            }
        }
    }
}
