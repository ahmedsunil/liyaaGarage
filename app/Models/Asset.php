<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use App\Support\Enums\AssetStatuses;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Asset extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'status'         => AssetStatuses::class,
        'purchased_date' => 'date',
    ];


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
