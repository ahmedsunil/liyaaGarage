<?php

namespace App\Filament\Resources\QuotationResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\QuotationResource;
use Filament\Actions\CreateAction;

class ListQuotations extends ListRecords
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Quotation'),
        ];
    }

    protected function getActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Quotation'),
        ];
    }
}
