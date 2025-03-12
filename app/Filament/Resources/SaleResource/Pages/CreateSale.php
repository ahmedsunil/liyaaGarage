<?php

namespace App\Filament\Resources\SaleResource\Pages;

use Exception;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use App\Filament\Resources\SaleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

//    protected function beforeSave(Sale $record): void
//    {
//        if (empty($record->items)) {
//            if ($record->exists) {
//                // Delete the sale if it already exists
//                $record->delete();
//            } else {
//                // Abort the save operation if it's a new sale
//                abort(422, 'Cannot save a sale without items.');
//            }
//        }
//    }

    protected function afterCreate(): RedirectResponse
    {
        if (empty($this->record->items)) {
            if ($this->record->exists) {
                // Delete the sale if it already exists
                $this->record->delete();
            } else {
                // Abort the save operation if it's a new sale
                abort(422, 'Cannot save a sale without items.');
            }
        }
    }

}
