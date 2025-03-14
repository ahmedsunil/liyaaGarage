<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vendor extends Model
{
    use hasFactory;
    use LogsActivity;

    protected $guarded = [];

    public function expense(): hasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function stockItems(): hasMany
    {
        return $this->hasMany(StockItem::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
                         ->logExcept($this->hidden)
                         ->logAll()
                         ->setDescriptionForEvent(function (string $eventName) {
                             return "This {$this->formattedName} has been {$eventName}";
                         });
    }

    public function formattedName(): Attribute
    {
        return Attribute::get(function () {
            return $this->name;
        });
    }

}
