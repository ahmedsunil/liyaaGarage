<?php

namespace App\Models;

use Exception;
use App\Support\Enums\PaymentStatus;
use App\Support\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date'                => 'date',
        'subtotal_amount'     => 'decimal:2',
        'discount_amount'     => 'decimal:2',
        'total_amount'        => 'decimal:2',
        'payment_status'      => PaymentStatus::class,
        'discount_percentage' => 'decimal:2',
        'transaction_type'    => TransactionType::class,
    ];

    protected static function booted(): void
    {
        static::created(function ($sale) {
            $sale->updateInventory();
        });

        static::updated(function ($sale) {
            $sale->updateInventoryOnUpdate();
        });

        static::deleted(function ($sale) {
            $sale->restoreInventory();
        });
    }

    public function updateInventory(): void
    {
        foreach ($this->items as $item) {
            $this->updateStockItem($item);
        }
    }

    private function updateStockItem($item)
    {
        $stockItem = StockItem::find($item->stock_item_id);
        if (! $stockItem || $stockItem->is_service) {
            return;
        }

        try {
            if ($stockItem->is_liquid) {
                $stockItem->remaining_volume = max(0, $stockItem->remaining_volume - $item->quantity);
                $stockItem->quantity = ceil($stockItem->remaining_volume / $stockItem->volume_per_unit);
            } else {
                $stockItem->quantity = max(0, $stockItem->quantity - $item->quantity);
            }

            $stockItem->inventory_value = $stockItem->quantity * $stockItem->total;
            $stockItem->save();

            Log::info("Inventory updated for stock item {$stockItem->id}. New quantity: {$stockItem->quantity}");
        } catch (Exception $e) {
            Log::error("Error updating inventory for stock item {$stockItem->id}: ".$e->getMessage());
        }
    }

    public function updateInventoryOnUpdate(): void
    {
        $originalItems = $this->getOriginal('items') ?? [];
        $currentItems = $this->items;

        // Restore inventory for removed or modified items
        foreach ($originalItems as $originalItem) {
            $currentItem = $currentItems->firstWhere('id', $originalItem->id);
            if (! $currentItem || $currentItem->quantity != $originalItem->quantity) {
                $this->restoreStockItem($originalItem);
            }
        }

        // Update inventory for new or modified items
        foreach ($currentItems as $currentItem) {
            $originalItem = collect($originalItems)->firstWhere('id', $currentItem->id);
            if (! $originalItem || $currentItem->quantity != $originalItem->quantity) {
                $this->updateStockItem($currentItem);
            }
        }
    }

    private function restoreStockItem($item): void
    {
        $stockItem = StockItem::find($item->stock_item_id);
        if (! $stockItem || $stockItem->is_service) {
            return;
        }

        try {
            if ($stockItem->is_liquid) {
                $stockItem->remaining_volume += $item->quantity;
                $stockItem->quantity = ceil($stockItem->remaining_volume / $stockItem->volume_per_unit);
            } else {
                $stockItem->quantity += $item->quantity;
            }

            $stockItem->inventory_value = $stockItem->quantity * $stockItem->total;
            $stockItem->save();

            Log::info("Inventory restored for stock item {$stockItem->id}. New quantity: {$stockItem->quantity}");
        } catch (Exception $e) {
            Log::error("Error restoring inventory for stock item {$stockItem->id}: ".$e->getMessage());
        }
    }

    public function restoreInventory(): void
    {
        foreach ($this->items as $item) {
            $this->restoreStockItem($item);
        }
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
}

// TODO
// The stock quantity should deduct when add sales
// Sales should not show the stock items when it is lower than 1,
// Show Stock Low on StockResource Table if the quantity is passed threshold
