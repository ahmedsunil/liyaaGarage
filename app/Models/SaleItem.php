<?php

namespace App\Models;

use Exception;
use App\Support\Enums\StockStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $guarded = [];


    //version 1
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


//version 2
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

    protected static function boot()
    {
        parent::boot();

        static::created(function ($salesItem) {
            $stockItem = $salesItem->stockItem;
            if ($stockItem->is_service->value == 0) {
                if ($stockItem->quantity >= $salesItem->quantity) {
                    $stockItem->quantity -= $salesItem->quantity;
                    $stockItem->save();
                    $salesItem->updateStockStatus($stockItem); // Update stock status
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
                $salesItem->updateStockStatus($stockItem); // Update stock status
            }
        });

        static::deleted(function ($salesItem) {
            $stockItem = $salesItem->stockItem;
            if ($stockItem->is_service->value == 0) {
                $stockItem->quantity += $salesItem->quantity;
                $stockItem->save();
                $salesItem->updateStockStatus($stockItem); // Update stock status
            }
        });
    }

    public function updateStockStatus($stockItem)
    {
        if ($stockItem->is_service->value == 1) {
            $stockStatus = StockStatus::AVAILABLE->value;
        } elseif ($stockItem->quantity == 0) {
            $stockStatus = StockStatus::OUT_OF_STOCK->value;

        } elseif ($stockItem->quantity < $stockItem->stock_threshold) {
            $stockStatus = StockStatus::IN_STOCK->value;

        } else {
            $stockStatus = StockStatus::OUT_OF_STOCK->value;
        }

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
