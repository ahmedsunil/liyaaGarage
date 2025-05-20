<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = Permission::all()->pluck('name')->toArray();
        $role = Role::where('name', 'admin')->first();

        if (! $role) {
            $role = new Role;
        }
        $role->name = 'admin';
        $role->guard_name = 'web';
        $role->description = 'System Administrator';
        $role->syncPermissions($permissions);
        $role->save();

        $user = User::where('email', 'admin@example.com')->first();
        if ($user) {
            $user->roles()->sync([$role->id]);
        } else {
            // Log a message if the command property is available
            if (isset($this->command)) {
                $this->command->info('Admin user does not exist. Please run the DefaultUserSeeder first.');
            } else {
                // Otherwise, just log to the application log
                Log::warning('Admin user does not exist. Please run the DefaultUserSeeder first.');
            }
        }

        $guest_user = Role::updateOrCreate(
            ['name' => 'guest'],
            [
                'guard_name' => 'web',
                'description' => 'Guest User',
            ]
        );
    }
}
