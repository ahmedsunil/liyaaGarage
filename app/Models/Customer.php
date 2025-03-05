<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Customer extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }


    public function sales(): hasManyThrough
    {
        return $this->hasManyThrough(Sale::class, Vehicle::class);
    }

}
