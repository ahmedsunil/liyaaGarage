<?php

namespace App\Filament\Resources\StockItemResource\Pages;

use App\Models\StockItem;
use App\Support\Enums\StockStatus;
use App\Filament\Resources\StockItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStockItem extends CreateRecord
{
    protected static string $resource = StockItemResource::class;


}
