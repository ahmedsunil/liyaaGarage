<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->getPermissions() as $model => $permissions) {
            foreach ($permissions as $permission) {
                Permission::updateOrCreate([
                    'name' => $permission,
                ], [
                    'model'      => $model,
                    'guard_name' => 'web',
                ]);
            }
        }
    }

    public function getPermissions(): array
    {
        return  [
            'user' => [
                'view users',
                'view any user',
                'edit users',
                'edit any user',
                'edit user role',
                'delete users',
                'delete any user',
                'force delete any user',
                'approve any user',
            ],
            'role' => [
                'view roles',
                'view any role',
                'edit role',
                'delete role',
                'delete any role',
            ],
            'logs' => [
                'view any log',
                'view logs',
            ],
            'settings' => [
                'view settings',
                'edit settings',
            ]
        ];
    }
}
