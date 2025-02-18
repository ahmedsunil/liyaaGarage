<?php

namespace Database\Seeders;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = Permission::all()->pluck('name')->toArray();
        $role = Role::where('name', 'admin')->first();

        if (! $role) {
            $role = new Role();
        }
        $role->name = 'admin';
        $role->guard_name = 'web';
        $role->description = 'System Administator';
        $role->syncPermissions($permissions);
        $role->save();

        $user = User::where('email', 'admin@example.com')->first();
        $user->roles()->sync([$role->id]);

        $guest_user = Role::updateOrCreate(
            ['name' => 'guest'],
            [
                'guard_name' => 'web',
                'description' => 'Guest User',
            ]
        );
    }
}
