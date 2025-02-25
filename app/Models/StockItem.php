<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_service'       => 'boolean',
        'is_liquid'        => 'boolean',
        'sale_price'       => 'decimal:2',
        'service_price'    => 'decimal:2',
        'total_price'      => 'decimal:2',
        'volume_per_unit'  => 'decimal:2',
        'remaining_volume' => 'decimal:2',
    ];

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
