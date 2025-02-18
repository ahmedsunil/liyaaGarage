<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Components\Infolists\LogPropertyEntry;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use App\Filament\Resources\ActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\KeyValueEntry;

class ViewActivity extends ViewRecord
{
    protected static string $resource = ActivityResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()->schema([
                    TextEntry::make('Causer')
                             ->formatStateUsing(fn($record) => $record->causer?->formatted_name)
                             ->inlineLabel(),
                    TextEntry::make('Subject')
                             ->formatStateUsing(fn($record) => $record->subject?->formatted_name)
                             ->inlineLabel(),
                    TextEntry::make('event')
                             ->formatStateUsing(fn($record) => str($record->description)->title()->toString())
                             ->inlineLabel(),
                    TextEntry::make('description')
                             ->formatStateUsing(fn($record) => str($record->description)->title()->toString())
                             ->inlineLabel(),
                    TextEntry::make('created_at')
                             ->label('Logged At')
                             ->formatStateUsing(fn($record) => $record->created_at?->format('d F Y H:i:s'))
                             ->inlineLabel(),
                ])->columns(1),
                Grid::make()
                    ->schema([
                        LogPropertyEntry::make('properties')
                                        ->hiddenLabel()
                                        ->maxWidth('full'),
                    ])->columns(1),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_all')
            ->url(route('filament.admin.resources.activities.index'))
            ->label('View All')
        ];
    }
}
