<?php

namespace App\Providers;

use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Models\Asset;
use App\Models\Vendor;
use App\Models\Expense;
use App\Models\Vehicle;
use App\Models\Customer;
use App\Models\SaleItem;
use App\Models\StockItem;
use App\Models\Permission;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Policies\ActivityPolicy;
use Illuminate\Support\Facades\URL;
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
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
    ];

    public function boot(): void
    {
        $js_path = __DIR__.'/../../resources/js/dist/components/permissions-selector.js';
        FilamentAsset::register([
            AlpineComponent::make('permissions-selector', $js_path),
        ]);

        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // enforce morph map
        Relation::enforceMorphMap([
            'user' => User::class,
            'role' => Role::class,
            'permission' => Permission::class,
            'activity' => Activity::class,
            'stock_item' => StockItem::class,
            'asset' => Asset::class,
            'customer' => Customer::class,
            'expense' => Expense::class,
            'sale' => Sale::class,
            'sale_item' => SaleItem::class,
            'vehicle' => Vehicle::class,
            'vendor' => Vendor::class,
        ]);
    }

    public function register(): void
    {
        //
    }
}
