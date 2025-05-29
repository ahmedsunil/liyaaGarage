<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $guarded = [];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function pos(): HasMany
    {
        return $this->hasMany(Pos::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
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
            return $this->phone;
        });
    }
}
