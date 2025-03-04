<?php

namespace App\Filament\Resources\SaleResource\Pages;

use Exception;
use App\Filament\Resources\SaleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    /**
     * @throws Exception
     */
    protected function afterCreate(): void
    {
        $record = $this->getRecord();
        foreach ($record->items as $salesItem) {
            $stockItem = $salesItem->stockItem;
            if ($stockItem->quantity >= $salesItem->quantity) {
                $stockItem->quantity -= $salesItem->quantity;
                $stockItem->save();
            } else {
                // Handle insufficient stock
                throw new Exception('Insufficient stock for item '.$stockItem->id);
            }
        }
    }
}
