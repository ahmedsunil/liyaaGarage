<?php

namespace App\Providers;

use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Models\Asset;
use App\Models\Brand;
use App\Models\Vendor;
use App\Models\Expense;
use App\Models\Vehicle;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Report;
use App\Models\SaleItem;
use App\Models\Quotation;
use App\Models\StockItem;
use App\Models\Permission;
use App\Policies\RolePolicy;
use App\Policies\SalePolicy;
use App\Policies\UserPolicy;
use App\Models\QuotationItem;
use App\Policies\AssetPolicy;
use App\Policies\BrandPolicy;
use App\Policies\VendorPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\VehiclePolicy;
use App\Policies\ActivityPolicy;
use App\Policies\BusinessPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\ReportPolicy;
use App\Policies\SaleItemPolicy;
use App\Policies\QuotationPolicy;
use App\Policies\StockItemPolicy;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Gate;
use App\Policies\QuotationItemPolicy;
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
        Customer::class => CustomerPolicy::class,
        StockItem::class => StockItemPolicy::class,
        Asset::class => AssetPolicy::class,
        Expense::class => ExpensePolicy::class,
        Sale::class => SalePolicy::class,
        SaleItem::class => SaleItemPolicy::class,
        Vehicle::class => VehiclePolicy::class,
        Vendor::class => VendorPolicy::class,
        Brand::class => BrandPolicy::class,
        Quotation::class => QuotationPolicy::class,
        QuotationItem::class => QuotationItemPolicy::class,
        Report::class => ReportPolicy::class,
        Business::class => BusinessPolicy::class,
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
            'brand' => Brand::class,
            'quotation' => Quotation::class,
            'quotation_item' => QuotationItem::class,
            'report' => Report::class,
            'business' => Business::class,
        ]);
    }

    public function register(): void
    {
        //
    }
}
