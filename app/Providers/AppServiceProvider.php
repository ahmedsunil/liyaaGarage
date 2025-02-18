<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Policies\UserPolicy;
use App\Policies\RolePolicy;
use Filament\Support\Assets\Js;
use App\Policies\ActivityPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\AlpineComponent;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{

    protected array $policies = [
        Activity::class => ActivityPolicy::class,
        User::class     => UserPolicy::class,
        Role::class     => RolePolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $js_path = __DIR__ . '/../../resources/js/dist/components/permissions-selector.js';
        FilamentAsset::register([
            AlpineComponent::make('permissions-selector', $js_path),
        ]);

        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // enforce morph map
        Relation::enforceMorphMap([
            'user'       => User::class,
            'role'       => Role::class,
            'permission' => Permission::class,
            'activity'   => Activity::class,
        ]);
    }
}
