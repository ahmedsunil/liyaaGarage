<?php

namespace App\Filament\Resources\StockItemResource\Pages;

use App\Models\StockItem;
use App\Support\Enums\StockStatus;
use App\Filament\Resources\StockItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockItem extends EditRecord
{
    protected static string $resource = StockItemResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
