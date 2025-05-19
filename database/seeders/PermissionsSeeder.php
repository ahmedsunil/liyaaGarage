<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->getPermissions() as $model => $permissions) {
            foreach ($permissions as $permission) {
                Permission::updateOrCreate([
                    'name' => $permission,
                ], [
                    'model' => $model,
                    'guard_name' => 'web',
                ]);
            }
        }
    }

    public function getPermissions(): array
    {
        return [
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
            ],
            'asset' => [
                'view assets',
                'view any asset',
                'create assets',
                'edit assets',
                'edit any asset',
                'delete assets',
                'delete any asset',
            ],
            'customer' => [
                'view customers',
                'view any customer',
                'create customers',
                'edit customers',
                'edit any customer',
                'delete customers',
                'delete any customer',
            ],
            'expense' => [
                'view expenses',
                'view any expense',
                'create expenses',
                'edit expenses',
                'edit any expense',
                'delete expenses',
                'delete any expense',
            ],
            'sale' => [
                'view sales',
                'view any sale',
                'create sales',
                'edit sales',
                'edit any sale',
                'delete sales',
                'delete any sale',
            ],
            'sale-item' => [
                'view sale items',
                'view any sale item',
                'create sale items',
                'edit sale items',
                'edit any sale item',
                'delete sale items',
                'delete any sale item',
            ],
            'stock-item' => [
                'view stock items',
                'view any stock item',
                'create stock items',
                'edit stock items',
                'edit any stock item',
                'delete stock items',
                'delete any stock item',
            ],
            'vehicle' => [
                'view vehicles',
                'view any vehicle',
                'create vehicles',
                'edit vehicles',
                'edit any vehicle',
                'delete vehicles',
                'delete any vehicle',
            ],
            'vendor' => [
                'view vendors',
                'view any vendor',
                'create vendors',
                'edit vendors',
                'edit any vendor',
                'delete vendors',
                'delete any vendor',
            ],
            'business' => [
                'view businesses',
                'view any business',
                'edit businesses',
                'edit any business',
                'delete businesses',
                'delete any business',
                'force delete any business',
                'approve any business',
            ],
        ];
    }
}
