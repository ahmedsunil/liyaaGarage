<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function afterCreate(): void
    {
        // Check if the sale has no items (is invalid)
        if ($this->record->isInvalid()) {
            // Delete the sale as it's invalid (has no items)
            $this->record->delete();

            // This notification will be shown if the sale was created but has no items
            // This is an additional safeguard in case the transaction rollback in SaleItem fails
            \Filament\Notifications\Notification::make()
                ->title('Sale Not Created')
                ->body('The sale could not be created because it has no items. This may be due to insufficient stock.')
                ->danger()
                ->persistent()
                ->send();
        }
    }
}
