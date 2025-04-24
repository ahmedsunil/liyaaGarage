<?php

namespace App\Filament\Resources\QoutationResource\Pages;

use App\Filament\Resources\QoutationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQoutations extends ListRecords
{
    protected static string $resource = QoutationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
