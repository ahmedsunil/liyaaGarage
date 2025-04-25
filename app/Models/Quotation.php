<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    protected $guarded = [];

    public function quotationItems(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }
}
