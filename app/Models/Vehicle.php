<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vehicle extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $guarded = [];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sales(): hasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function quotations(): hasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
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
            return $this->vehicle_number;
        });
    }
}
