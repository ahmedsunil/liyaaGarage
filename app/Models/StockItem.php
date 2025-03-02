<?php

namespace App\Models;

use App\Support\Enums\StockStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItem extends Model
{
    protected $guarded = [];


    protected $casts = [
        'stock_status' => StockStatus::class,
    ];

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    // Calculate available quantity based on product type
    public function getAvailableQuantityAttribute()
    {
        if ($this->is_service) {
            return null;
        }

        if ($this->product_type === 'liquid') {
            return $this->remaining_volume;
        }

        return $this->quantity;
    }

    public function reduceStock($volume)
    {
        if ($this->is_service) {
            return true;
        }

        if ($this->product_type === 'liquid') {
            if ($this->remaining_volume >= $volume) {
                $this->remaining_volume -= $volume;
                if ($this->remaining_volume < 0.01) { // To handle floating point precision issues
                    $this->remaining_volume = 0;
                    $this->quantity = max(0, $this->quantity - 1);
                }
                $this->save();

                return true;
            }
        } else {
            $quantity = ceil($volume); // For discrete items, round up to nearest whole number
            if ($this->quantity >= $quantity) {
                $this->quantity -= $quantity;
                $this->save();

                return true;
            }
        }

        return false;
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
