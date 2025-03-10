<?php

namespace App\Models;

use App\Support\Enums\ItemType;
use App\Support\Enums\StockStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'stock_status' => StockStatus::class,
        'is_service' => ItemType::class,
    ];

    //    protected static function boot(): void
    //    {
    //        parent::boot();
    //
    //
    //
    //        static::updating(function ($stockItem) {
    //            $stockItem->updateStockStatus();
    //            dd($stockItem->quantity);
    //
    //        });
    //    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    // Calculate available quantity based on product type

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
