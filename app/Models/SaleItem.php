<?php

namespace App\Models;

use Log;
use Exception;
use App\Support\Enums\StockStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $guarded = [];


    // version 1
    //    protected static function boot()
    //    {
    //        parent::boot();
    //
    //        static::updated(function ($salesItem) {
    //            $adjustment = $salesItem->getOriginal('quantity') - $salesItem->quantity;
    //
    //            $stockItem = $salesItem->stockItem;
    //            if ($adjustment > 0) {
    //                // Restore stock
    //                $stockItem->quantity += $adjustment;
    //            } elseif ($adjustment < 0) {
    //                // Deduct more stock
    //                if ($stockItem->quantity >= abs($adjustment)) {
    //                    $stockItem->quantity -= abs($adjustment);
    //                } else {
    //                    // Handle insufficient stock
    //                    throw new Exception('Insufficient stock for item '.$stockItem->id);
    //                }
    //            }
    //
    //            $stockItem->save();
    //        });
    //    }

    // In SaleItem Model
    //    protected static function boot()
    //    {
    //        parent::boot();
    //
    //        static::created(function ($salesItem) {
    //            $stockItem = $salesItem->stockItem;
    //            if ($stockItem->quantity >= $salesItem->quantity) {
    //                $stockItem->quantity -= $salesItem->quantity;
    //                $stockItem->save();
    //            } else {
    //                throw new Exception('Insufficient stock for item '.$stockItem->id);
    //            }
    //        });
    //
    //        static::updated(function ($salesItem) {
    //            $adjustment = $salesItem->getOriginal('quantity') - $salesItem->quantity;
    //
    //            $stockItem = $salesItem->stockItem;
    //            if ($adjustment > 0) {
    //                // Restore stock
    //                $stockItem->quantity += $adjustment;
    //            } elseif ($adjustment < 0) {
    //                // Deduct more stock
    //                if ($stockItem->quantity >= abs($adjustment)) {
    //                    $stockItem->quantity -= abs($adjustment);
    //                } else {
    //                    throw new Exception('Insufficient stock for item '.$stockItem->id);
    //                }
    //            }
    //
    //            $stockItem->save();
    //        });
    //
    //        static::deleted(function ($salesItem) {
    //            $stockItem = $salesItem->stockItem;
    //            $stockItem->quantity += $salesItem->quantity;
    //            $stockItem->save();
    //        });
    //    }

    // version 2
    //    protected static function boot()
    //    {
    //        parent::boot();
    //
    //        static::created(function ($salesItem) {
    //            if ($salesItem->stockItem->is_service->value == 0) {
    //                $stockItem = $salesItem->stockItem;
    //                if ($stockItem->quantity >= $salesItem->quantity) {
    //                    $stockItem->quantity -= $salesItem->quantity;
    //                    $stockItem->save();
    //                } else {
    //                    throw new Exception('Insufficient stock for item '.$stockItem->id);
    //                }
    //            }
    //        });
    //
    //
    //        static::updated(function ($salesItem) {
    //            if ($salesItem->stockItem->is_service->value == 0) {
    //                $adjustment = $salesItem->getOriginal('quantity') - $salesItem->quantity;
    //
    //                $stockItem = $salesItem->stockItem;
    //                if ($adjustment > 0) {
    //                    // Restore stock
    //                    $stockItem->quantity += $adjustment;
    //                } elseif ($adjustment < 0) {
    //                    // Deduct more stock
    //                    if ($stockItem->quantity >= abs($adjustment)) {
    //                        $stockItem->quantity -= abs($adjustment);
    //                    } else {
    //                        throw new Exception('Insufficient stock for item '.$stockItem->id);
    //                    }
    //                }
    //
    //                $stockItem->save();
    //            }
    //        });
    //
    //        static::deleted(function ($salesItem) {
    //            if ($salesItem->stockItem->is_service->value == 0) {
    //                $stockItem = $salesItem->stockItem;
    //                $stockItem->quantity += $salesItem->quantity;
    //                $stockItem->save();
    //            }
    //        });
    //    }

    // with stock status update
    protected static function boot(): void
    {
        parent::boot();

        static::created(function ($salesItem) {
            $stockItem = $salesItem->stockItem;
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

        static::updated(function ($salesItem) {
            $stockItem = $salesItem->stockItem;
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

        static::deleted(function ($salesItem) {
            $stockItem = $salesItem->stockItem;
            if ($stockItem->is_service->value == 0) {
                $stockItem->quantity += $salesItem->quantity;
                $stockItem->save();
                $salesItem->updateStockStatus(); // Update stock status
            }
        });
    }

    /**
     * @throws Exception
     */
    public function updateStockStatus(): void
    {
        $stockItem = $this->stockItem;

        if (! $stockItem) {
            throw new Exception('Stock item not found for sales item '.$this->id);
        }

        // Debugging: Log the current stock item details
//        Log::info('Updating stock status for stock item:', [
//            'id'                 => $stockItem->id,
//            'quantity'           => $stockItem->quantity,
//            'is_service'         => $stockItem->is_service,
//            'quantity_threshold' => $stockItem->quantity_threshold,
//        ]);

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

        // Debugging: Log the new stock status
//        Log::info('New stock status:', ['status' => $stockStatus]);

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
