<?php

namespace App\Models;

use App\Support\Enums\AssetStatuses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Asset extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'status' => AssetStatuses::class,
        'purchased_date' => 'date',
    ];
}
