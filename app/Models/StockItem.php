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
        'is_service'   => ItemType::class,
    ];

    /**
     * Update stock status for all items
     */


    public static function updateAllStockStatuses(): void
    {
        StockItem::where('quantity', '<=', 0)->update(['stock_status' => 'out_of_stock']);
        StockItem::where('quantity', '>', 0)->where('quantity', '<', 10)->update(['stock_status' => 'low_stock']);
        StockItem::where('quantity', '>=', 10)->update(['stock_status' => 'in_stock']);
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
