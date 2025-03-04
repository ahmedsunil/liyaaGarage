<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'includes_service' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($salesItem) {
            $adjustment = $salesItem->getOriginal('quantity') - $salesItem->quantity;

            $stockItem = $salesItem->stockItem;
            if ($adjustment > 0) {
                // Restore stock
                $stockItem->quantity += $adjustment;
            } elseif ($adjustment < 0) {
                // Deduct more stock
                if ($stockItem->quantity >= abs($adjustment)) {
                    $stockItem->quantity -= abs($adjustment);
                } else {
                    // Handle insufficient stock
                    throw new Exception('Insufficient stock for item '.$stockItem->id);
                }
            }

            $stockItem->save();
        });
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class, 'stock_item_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
