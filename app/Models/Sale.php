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
