<?php

namespace App\Filament\Resources\SaleResource\Pages;

use DB;
use Exception;
use Throwable;
use Filament\Actions;
use App\Filament\Resources\SaleResource;
use Filament\Resources\Pages\EditRecord;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
