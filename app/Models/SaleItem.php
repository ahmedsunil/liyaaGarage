<?php

namespace App\Models;

use DB;
use Exception;
use App\Support\Enums\StockStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $guarded = [];

    // with stock status update,
    protected static function boot(): void
    {
        parent::boot();

        static::created(function ($salesItem) {
            DB::transaction(function () use ($salesItem) {
                $stockItem = $salesItem->stockItem->lockForUpdate()->first();

                //                $stockItem = $salesItem->stockItem;
                if ($stockItem->is_service->value == 0) {
                    if ($stockItem->quantity >= $salesItem->quantity) {
                        $stockItem->quantity -= $salesItem->quantity;
                        $stockItem->save();
                        $salesItem->updateStockStatus(); // Update stock status
                    } else {
                        throw new Exception('Insufficient stock for item '.$stockItem->id);
                    }
                }
            });
        });


        static::updated(function ($salesItem) {
            DB::transaction(function () use ($salesItem) {
                $stockItem = $salesItem->stockItem->lockForUpdate()->first();

//                $stockItem = $salesItem->stockItem;
                if ($stockItem->is_service->value == 0) {
                    $adjustment = $salesItem->getOriginal('quantity') - $salesItem->quantity;

                    if ($adjustment > 0) {
                        // Restore stock
                        $stockItem->quantity += $adjustment;
                    } elseif ($adjustment < 0) {
                        // Deduct more stock
                        if ($stockItem->quantity >= abs($adjustment)) {
                            $stockItem->quantity -= abs($adjustment);
                        } else {
                            throw new Exception('Insufficient stock for item '.$stockItem->id);
                        }
                    }

                    $stockItem->save();
                    $salesItem->updateStockStatus(); // Update stock status
                }
            });
        });

        static::deleted(function ($salesItem) {
            DB::transaction(function () use ($salesItem) {
                $stockItem = $salesItem->stockItem;
                if ($stockItem->is_service->value == 0) {
                    $stockItem->quantity += $salesItem->quantity;
                    $stockItem->save();
                    $salesItem->updateStockStatus(); // Update stock status
                }
            });
        });
    }

    // with status update and db transaction

    /**
     * @throws Exception
     */
    public function updateStockStatus(): void
    {
        $stockItem = $this->stockItem;

        if (! $stockItem) {
            throw new Exception('Stock item not found for sales item '.$this->id);
        }

        // Determine the stock status
        if ($stockItem->is_service->value == 1) {
            $stockStatus = StockStatus::AVAILABLE->value;
        } elseif ($stockItem->quantity === 0) {
            $stockStatus = StockStatus::OUT_OF_STOCK->value;
        } elseif ($stockItem->quantity <= $stockItem->quantity_threshold) {
            $stockStatus = StockStatus::LOW_STOCK->value;
        } else {
            $stockStatus = StockStatus::IN_STOCK->value;
        }

        // Update the stock status
        $stockItem->stock_status = $stockStatus;
        $stockItem->save();
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
