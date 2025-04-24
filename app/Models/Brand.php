<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $guarded = [];

    public function vehicles(): hasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
