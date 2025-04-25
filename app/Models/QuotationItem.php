<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    protected $guarded = [];

    public function quotation()
    {
        return $this->belongsTo(QuotationItem::class);
    }
}
