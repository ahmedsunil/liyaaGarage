<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $guarded = [];

    public function vendors(): belongsTo
    {
        return $this->belongsTo(Vendor::class);
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
            return $this->expense_type;
        });
    }
}
