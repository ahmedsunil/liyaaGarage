<?php

namespace App\Models;

use DB;
use Exception;
use App\Support\Enums\StockStatus;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SaleItem extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $guarded = [];


    // with stock status update,
    //    protected static function boot(): void
    //    {
    //        parent::boot();
    //
    //        static::created(function ($salesItem) {
    //            DB::transaction(function () use ($salesItem) {
    //                $stockItem = $salesItem->stockItem->lockForUpdate()->first();
    //
    //                // $stockItem = $salesItem->stockItem;
    //                if ($stockItem->is_service->value == 0) {
    //                    if ($stockItem->quantity >= $salesItem->quantity) {
    //                        $stockItem->quantity -= $salesItem->quantity;
    //                        $stockItem->save();
    //                        $salesItem->updateStockStatus(); // Update stock status
    //                    } else {
    //                        throw new Exception('Insufficient stock for item '.$stockItem->id);
    //                    }
    //                }
    //            });
    //        });
    //
    //
    //        static::updated(function ($salesItem) {
    //            DB::transaction(function () use ($salesItem) {
    //                $stockItem = $salesItem->stockItem->lockForUpdate()->first();
    //
    //                //                $stockItem = $salesItem->stockItem;
    //                if ($stockItem->is_service->value == 0) {
    //                    $adjustment = $salesItem->getOriginal('quantity') - $salesItem->quantity;
    //
    //                    if ($adjustment > 0) {
    //                        // Restore stock
    //                        $stockItem->quantity += $adjustment;
    //                    } elseif ($adjustment < 0) {
    //                        // Deduct more stock
    //                        if ($stockItem->quantity >= abs($adjustment)) {
    //                            $stockItem->quantity -= abs($adjustment);
    //                        } else {
    //                            throw new Exception('Insufficient stock for item '.$stockItem->id);
    //                        }
    //                    }
    //
    //                    $stockItem->save();
    //                    $salesItem->updateStockStatus(); // Update stock status
    //                }
    //            });
    //        });
    //
    //        static::deleted(function ($salesItem) {
    //            DB::transaction(function () use ($salesItem) {
    //                $stockItem = $salesItem->stockItem;
    //                if ($stockItem->is_service->value == 0) {
    //                    $stockItem->quantity += $salesItem->quantity;
    //                    $stockItem->save();
    //                    $salesItem->updateStockStatus(); // Update stock status
    //                }
    //            });
    //        });
    //    }

    // second version
    protected static function boot(): void
    {
        parent::boot();


        static::creating(function ($salesItem) {
            try {
                DB::transaction(function () use ($salesItem) {
                    $stockItem = $salesItem->stockItem()->lockForUpdate()->first();

                    if (!$stockItem) {
                        throw ValidationException::withMessages([
                            'stock' => "Stock item not found for sales item",
                        ]);
                    }

                    if ($stockItem->is_service->value == 0) {
                        if ($stockItem->quantity >= $salesItem->quantity) {
                            $stockItem->quantity -= $salesItem->quantity;
                            $stockItem->save();
                            $salesItem->updateStockStatus(); // Update stock status
                        } else {
                            // Improved error message with more details
                            throw ValidationException::withMessages([
                                'stock' => "Only {$stockItem->quantity} units available for product \"{$stockItem->product_name}\". Requested: {$salesItem->quantity}",
                            ]);
                        }
                    }
                });
            } catch (ValidationException $e) {
                // Get error message
                $errorMessage = collect($e->errors())->flatten()->first();

                // Send notification
                Notification::make()
                            ->title('Insufficient Stock')
                            ->body($errorMessage)
                            ->danger()
                            ->persistent()
                            ->send();

                // Prevent the creation of the sale item and its parent sale
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }

                // Re-throw to prevent creation
                throw $e;
            }
        });


        static::updating(function ($salesItem) {
            try {
                DB::transaction(function () use ($salesItem) {
                    $stockItem = $salesItem->stockItem()->lockForUpdate()->first();

                    if (!$stockItem) {
                        throw ValidationException::withMessages([
                            'stock' => "Stock item not found for sales item",
                        ]);
                    }

                    if ($stockItem->is_service->value == 0) {
                        // Calculate the change in quantity
                        $originalQty = $salesItem->getOriginal('quantity');
                        $newQty = $salesItem->quantity;
                        $qtyDifference = $newQty - $originalQty;

                        // If increasing quantity
                        if ($qtyDifference > 0) {
                            // Check if we have enough additional stock
                            if ($stockItem->quantity >= $qtyDifference) {
                                // Sufficient stock for the increase
                                $stockItem->quantity -= $qtyDifference;
                                $stockItem->save();
                                $salesItem->updateStockStatus();
                            } else {
                                // Not enough stock for the full increase
                                $maxPossibleQty = $originalQty + $stockItem->quantity;

                                if ($stockItem->quantity > 0) {
                                    // Auto-adjust to maximum possible
                                    $salesItem->quantity = $maxPossibleQty;

                                    // Update stock (will become zero)
                                    $stockItem->quantity = 0;
                                    $stockItem->save();
                                    $salesItem->updateStockStatus();
                                } else {
                                    // Can't increase at all
                                    throw ValidationException::withMessages([
                                        'stock' => "Only {$stockItem->quantity} additional units available for product \"{$stockItem->product_name}\". ".
                                            "Maximum possible quantity: {$maxPossibleQty} items (currently {$originalQty}).",
                                    ]);
                                }
                            }
                        } // If decreasing quantity
                        elseif ($qtyDifference < 0) {
                            // Return the excess to stock
                            $stockItem->quantity += abs($qtyDifference);
                            $stockItem->save();
                            $salesItem->updateStockStatus();
                        }
                        // If no change in quantity, do nothing
                    }
                });

                return true; // Continue with update
            } catch (ValidationException $e) {
                // Get error message
                $errorMessage = collect($e->errors())->flatten()->first();

                // Send notification
                Notification::make()
                            ->title('Stock Limitation')
                            ->body($errorMessage)
                            ->danger()
                            ->persistent()
                            ->send();

                // Re-throw to prevent update
                throw $e;
            }
        });

        static::deleted(function ($salesItem) {
            DB::transaction(function () use ($salesItem) {
                $stockItem = $salesItem->stockItem()->first();

                if (!$stockItem) {
                    // If stock item doesn't exist, nothing to update
                    return;
                }

                if ($stockItem->is_service->value == 0) {
                    $stockItem->quantity += $salesItem->quantity;
                    $stockItem->save();
                    $salesItem->updateStockStatus(); // Update stock status
                }
            });
        });
    }

    public function updateStockStatus(): void
    {
        $stockItem = $this->stockItem()->first();

        if (! $stockItem) {
            throw ValidationException::withMessages([
                'stock' => "Stock item not found for sales item ".$this->id,
            ]);
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
                         ->logExcept($this->hidden)
                         ->logAll()
                         ->setDescriptionForEvent(function (string $eventName) {
                             return "This {$this->formattedName} has been {$eventName}";
                         });
    }

    public function formattedName(): Attribute
    {
        return Attribute::get(function () {
            return $this->name;
        });
    }
}
