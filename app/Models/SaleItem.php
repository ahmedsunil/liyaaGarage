<?php

namespace App\Models;

use Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'unit_price'       => 'decimal:2',
        'total_price'      => 'decimal:2',
        'includes_service' => 'boolean',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::created(function (SaleItem $model) {
            DB::transaction(function () use ($model) {
                $stock = $model->stockItem()->lockForUpdate()->first();
                if ($stock && ! $stock->is_service) {
                    $stock->quantity -= $model->quantity;
                    $stock->save();
                }
            });
        });


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
