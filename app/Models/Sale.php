<?php

namespace App\Models;

use Log;
use Illuminate\Support\Facades\DB;
use App\Support\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    //    public $_tempItems;

    protected $guarded = [];

    protected $casts = [
        'transaction_type' => TransactionType::class,
    ];

    //    protected static function booted(): void
    //    {
    //        parent::booted();
    //
    //        static::updating(function (Sale $sale) {
    //            $sale->_tempItems = $sale->items()->with('stockItem')->get();
    //        });
    //
    //        static::updated(function (Sale $sale) {
    //            if (! isset($sale->_tempItems)) {
    //                return;
    //            }
    //
    //            DB::transaction(function () use ($sale) {
    //                $sale->load('items.stockItem');
    //
    //                // Handle removed items
    //                foreach ($sale->_tempItems as $originalItem) {
    //                    if (! $sale->items->contains('id', $originalItem->id)) {
    //                        $stockItem = StockItem::lockForUpdate()->find($originalItem->stock_item_id);
    //                        if ($stockItem && ! $stockItem->is_service) {
    //                            if ($stockItem->is_liquid) {
    //                                $stockItem->remaining_volume += $originalItem->quantity;
    //                                $stockItem->quantity = ceil($stockItem->remaining_volume / $stockItem->volume_per_unit);
    //                            } else {
    //                                $stockItem->quantity += $originalItem->quantity;
    //                            }
    //                            $stockItem->save();
    //
    //                            Log::info('Restored stock for removed item', [
    //                                'stock_item_id' => $stockItem->id,
    //                                'quantity' => $originalItem->quantity,
    //                                'new_quantity' => $stockItem->quantity,
    //                            ]);
    //                        }
    //                    }
    //                }
    //            });
    //
    //            unset($sale->_tempItems);
    //        });
    //
    //
    //        static::deleting(function (Sale $sale) {
    //            $sale->load('items.stockItem');
    //            foreach ($sale->items as $item) {
    //                $stockItem = StockItem::lockForUpdate()->find($item->stock_item_id);
    //                if ($stockItem && ! $stockItem->is_service) {
    //                    if ($stockItem->is_liquid) {
    //                        $stockItem->remaining_volume += $item->quantity;
    //                        $stockItem->quantity = ceil($stockItem->remaining_volume / $stockItem->volume_per_unit);
    //                    } else {
    //                        $stockItem->quantity += $item->quantity;
    //                    }
    //                    $stockItem->save();
    //                }
    //            }
    //        });
    //
    //
    //        // restore deleted stock
    //        static::deleting(function (Sale $sale) {
    //            $sale->load('items.stockItem');  // Eager load relationships
    //
    //            DB::transaction(function () use ($sale) {
    //                foreach ($sale->items as $item) {
    //                    $stockItem = StockItem::lockForUpdate()->find($item->stock_item_id);
    //                    if (! $stockItem || $stockItem->is_service) {
    //                        continue;
    //                    }
    //
    //                    if ($stockItem->is_liquid) {
    //                        $stockItem->remaining_volume += $item->quantity;
    //                        $stockItem->quantity = ceil($stockItem->remaining_volume / $stockItem->volume_per_unit);
    //                    } else {
    //                        $stockItem->quantity += $item->quantity;
    //                    }
    //
    //                    $stockItem->inventory_value = $stockItem->quantity * $stockItem->total;
    //                    $stockItem->save();
    //                }
    //            });
    //        });
    //    }

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function ($sale) {
            // Restore stock here.
            foreach ($sale->items as $salesItem) {
                $stockItem = $salesItem->stockItem;
                $stockItem->quantity += $salesItem->quantity;
                $stockItem->save();
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    //    public function customer()
    //    {
    //        return $this->vehicle->customer();
    //    }

}
