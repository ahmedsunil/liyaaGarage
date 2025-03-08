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

    protected static function boot(): void
    {
        parent::boot();
        //
        //
        //
        //        static::updating(function ($stockItem) {
        //            $stockItem->updateStockStatus();
        //            dd($stockItem->quantity);
        //
        //        });
    }

    public function updateStockStatus(): void
    {
        if ($this->is_service->value == '1') {
            $stockStatus = StockStatus::AVAILABLE->value;
        } elseif ($this->quantity === 0) {
            $stockStatus = StockStatus::OUT_OF_STOCK->value;
        } elseif ($this->quantity <= $this->stock_threshold) {
            $stockStatus = StockStatus::LOW_STOCK->value;
        } else {
            $stockStatus = StockStatus::IN_STOCK->value;
        }

        $this->stock_status = $stockStatus;
        $this->saveQuietly(); // Use saveQuietly to avoid infinite loop
    }

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
