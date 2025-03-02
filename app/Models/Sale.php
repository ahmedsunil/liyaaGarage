<?php

namespace App\Models;

use App\Support\Enums\PaymentStatus;
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
    ];

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

//TODO
//The stock quantity should deduct when add sales
//Sales should not show the stock items when it is lower than 1,
//Show Stock Low on StockResource Table if the quantity is passed threshold

