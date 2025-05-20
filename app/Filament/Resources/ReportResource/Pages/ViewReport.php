<?php

namespace App\Filament\Resources\ReportResource\Pages;

use Filament\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Split;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\IconPosition;
use App\Filament\Resources\ReportResource;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;

class ViewReport extends ViewRecord
{
    protected static string $resource = ReportResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()
                    ->schema([
                        Split::make([
                            Group::make([
                                TextEntry::make('name')
                                    ->label('Report Name')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntry\TextEntrySize::Large),
                                TextEntry::make('created_at')
                                    ->label('Generated On')
                                    ->dateTime('F j, Y, g:i a'),
                            ]),
                            Group::make([
                                TextEntry::make('from_date')
                                    ->label('From Date')
                                    ->date('F j, Y')
                                    ->icon('heroicon-o-calendar')
                                    ->iconPosition(IconPosition::Before),
                                TextEntry::make('to_date')
                                    ->label('To Date')
                                    ->date('F j, Y')
                                    ->icon('heroicon-o-calendar')
                                    ->iconPosition(IconPosition::Before),
                            ])->extraAttributes(['class' => 'text-end']),
                        ])->from('md'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->url(fn () => route('reports.download', ['report' => $this->record])),
        ];
    }
}
