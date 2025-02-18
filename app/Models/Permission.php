<?php

namespace App\Models;


use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{

    public static function getPermissionModels()
    {
        $query = self::query()->get()->groupBy('model')->sort()->map(function ($permissions, $model) {
            return [
                'model' => str($model)->title()->toString(),
                'permissions' => $permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => str($permission->name)->ucfirst()->toString(),
                    ];
                })->toArray(),
            ];
        })->values();

        return $query;
    }

}
