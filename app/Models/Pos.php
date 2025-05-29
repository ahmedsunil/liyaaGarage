<?php

namespace App\Models;

use DB;
use Spatie\Activitylog\LogOptions;
use App\Support\Enums\StockStatus;
use App\Support\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;

class Pos extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'transaction_type' => TransactionType::class,
        'sale_items' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($pos) {
            // Validate stock quantities before saving
            if (!empty($pos->sale_items)) {
                try {
                    foreach ($pos->sale_items as $item) {
                        $stockItem = StockItem::find($item['stock_item_id']);

                        if (!$stockItem) {
                            throw ValidationException::withMessages([
                                'stock' => "Stock item not found for item {$item['stock_item_id']}",
                            ]);
                        }

                        if ($stockItem->is_service->value == 0 && $stockItem->quantity < $item['quantity']) {
                            throw ValidationException::withMessages([
                                'stock' => "Only {$stockItem->quantity} units available for product \"{$stockItem->product_name}\". Requested: {$item['quantity']}",
                            ]);
                        }
                    }
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

                    // Re-throw to prevent creation
                    throw $e;
                }
            }
        });

        static::created(function ($pos) {
            // Process sale items from JSON and update stock quantities
            if (!empty($pos->sale_items)) {
                try {
                    DB::transaction(function () use ($pos) {
                        foreach ($pos->sale_items as $item) {
                            $stockItem = StockItem::find($item['stock_item_id']);

                            if (!$stockItem) {
                                throw ValidationException::withMessages([
                                    'stock' => "Stock item not found for item {$item['stock_item_id']}",
                                ]);
                            }

                            if ($stockItem->is_service->value == 0) {
                                if ($stockItem->quantity >= $item['quantity']) {
                                    $stockItem->quantity -= $item['quantity'];
                                    $stockItem->save();
                                    self::updateStockStatus($stockItem);
                                } else {
                                    throw ValidationException::withMessages([
                                        'stock' => "Only {$stockItem->quantity} units available for product \"{$stockItem->product_name}\". Requested: {$item['quantity']}",
                                    ]);
                                }
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

                    // Prevent the creation and rollback transaction
                    if (DB::transactionLevel() > 0) {
                        DB::rollBack();
                    }

                    // Re-throw to prevent creation
                    throw $e;
                }
            }
        });

        static::updating(function ($pos) {
            // Validate stock quantities before updating
            if ($pos->isDirty('sale_items')) {
                try {
                    $oldItems = json_decode(json_encode($pos->getOriginal('sale_items') ?? []), true);
                    $newItems = $pos->sale_items ?? [];

                    // Create lookup arrays for easier comparison
                    $oldItemsMap = [];
                    foreach ($oldItems as $item) {
                        $key = $item['stock_item_id'];
                        $oldItemsMap[$key] = $item;
                    }

                    // Validate new quantities
                    foreach ($newItems as $item) {
                        $stockItemId = $item['stock_item_id'];
                        $stockItem = StockItem::find($stockItemId);

                        if (!$stockItem) {
                            throw ValidationException::withMessages([
                                'stock' => "Stock item not found for item {$stockItemId}",
                            ]);
                        }

                        if ($stockItem->is_service->value == 0) {
                            $oldItem = $oldItemsMap[$stockItemId] ?? null;
                            $oldQty = $oldItem ? $oldItem['quantity'] : 0;
                            $newQty = $item['quantity'];
                            $qtyDifference = $newQty - $oldQty;

                            // Only check if we're increasing quantity
                            if ($qtyDifference > 0 && $stockItem->quantity < $qtyDifference) {
                                throw ValidationException::withMessages([
                                    'stock' => "Only {$stockItem->quantity} additional units available for product \"{$stockItem->product_name}\". Requested increase: {$qtyDifference}",
                                ]);
                            }
                        }
                    }
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
            }
        });

        static::updated(function ($pos) {
            // Handle stock updates if sale_items have changed
            if ($pos->isDirty('sale_items')) {
                $oldItems = json_decode(json_encode($pos->getOriginal('sale_items') ?? []), true);
                $newItems = $pos->sale_items ?? [];

                try {
                    DB::transaction(function () use ($oldItems, $newItems) {
                        // Create lookup arrays for easier comparison
                        $oldItemsMap = [];
                        foreach ($oldItems as $item) {
                            $key = $item['stock_item_id'];
                            $oldItemsMap[$key] = $item;
                        }

                        $newItemsMap = [];
                        foreach ($newItems as $item) {
                            $key = $item['stock_item_id'];
                            $newItemsMap[$key] = $item;
                        }

                        // Process items that were removed or had quantity reduced
                        foreach ($oldItemsMap as $stockItemId => $oldItem) {
                            $newItem = $newItemsMap[$stockItemId] ?? null;
                            $qtyDifference = 0;

                            if (!$newItem) {
                                // Item was removed, restore full quantity
                                $qtyDifference = $oldItem['quantity'];
                            } else {
                                // Item quantity was changed
                                $qtyDifference = $oldItem['quantity'] - $newItem['quantity'];
                            }

                            if ($qtyDifference > 0) {
                                $stockItem = StockItem::find($stockItemId);
                                if ($stockItem && $stockItem->is_service->value == 0) {
                                    $stockItem->quantity += $qtyDifference;
                                    $stockItem->save();
                                    self::updateStockStatus($stockItem);
                                }
                            }
                        }

                        // Process items that were added or had quantity increased
                        foreach ($newItemsMap as $stockItemId => $newItem) {
                            $oldItem = $oldItemsMap[$stockItemId] ?? null;
                            $qtyDifference = 0;

                            if (!$oldItem) {
                                // New item added
                                $qtyDifference = $newItem['quantity'];
                            } else {
                                // Item quantity was changed
                                $qtyDifference = $newItem['quantity'] - $oldItem['quantity'];
                            }

                            if ($qtyDifference > 0) {
                                $stockItem = StockItem::find($stockItemId);

                                if (!$stockItem) {
                                    throw ValidationException::withMessages([
                                        'stock' => "Stock item not found for item {$stockItemId}",
                                    ]);
                                }

                                if ($stockItem->is_service->value == 0) {
                                    if ($stockItem->quantity >= $qtyDifference) {
                                        $stockItem->quantity -= $qtyDifference;
                                        $stockItem->save();
                                        self::updateStockStatus($stockItem);
                                    } else {
                                        throw ValidationException::withMessages([
                                            'stock' => "Only {$stockItem->quantity} units available for product \"{$stockItem->product_name}\". Requested additional: {$qtyDifference}",
                                        ]);
                                    }
                                }
                            }
                        }
                    });
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

                    // Prevent the update and rollback transaction
                    if (DB::transactionLevel() > 0) {
                        DB::rollBack();
                    }

                    // Re-throw to prevent update
                    throw $e;
                }
            }
        });

        static::deleting(function ($pos) {
            // Restore stock quantities when a POS record is deleted
            if (!empty($pos->sale_items)) {
                DB::transaction(function () use ($pos) {
                    foreach ($pos->sale_items as $item) {
                        $stockItem = StockItem::find($item['stock_item_id']);

                        if ($stockItem && $stockItem->is_service->value == 0) {
                            $stockItem->quantity += $item['quantity'];
                            $stockItem->save();
                            self::updateStockStatus($stockItem);
                        }
                    }
                });
            }
        });
    }

    public static function updateStockStatus($stockItem): void
    {
        if (! $stockItem) {
            return;
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

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logExcept($this->hidden)
            ->logAll()
            ->setDescriptionForEvent(function (string $eventName) {
                return "This POS record {$this->id} has been {$eventName}";
            });
    }

    public function formattedName(): Attribute
    {
        return Attribute::get(function () {
            return $this->id;
        });
    }
}
