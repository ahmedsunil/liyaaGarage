<?php

namespace App\Models;

use DB;
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
            // Delete each sale item individually to trigger the deleted event
            DB::transaction(function () use ($sale) {
                // Delete each sale item individually to trigger the deleted event
                foreach ($sale->items as $item) {
                    $item->delete();
                }
            });
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

//    public function isInvalid(): bool
//    {
//        return $this->relationLoaded('items') ? $this->items->isEmpty() : $this->items()->count() === 0;
//    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
