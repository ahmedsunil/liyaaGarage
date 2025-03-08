<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vendor extends Model
{
    use hasFactory;

    protected $guarded = [];

    public function expense(): hasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function stockItems(): hasMany
    {
        return $this->hasMany(StockItem::class);
    }

}
