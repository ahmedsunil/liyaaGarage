<?php

namespace App\Filament\Resources\PosResource\Pages;

use Filament\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists\Components\Grid;
use App\Filament\Resources\PosResource;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Split;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\IconPosition;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;

class ViewPos extends ViewRecord
{
    protected static string $resource = PosResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()
                    ->schema([
                        Split::make([
                            Group::make([
                                TextEntry::make('id')
                                    ->label('POS #')
                                    ->formatStateUsing(fn ($state) => str_pad($state, 6, '0', STR_PAD_LEFT))
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntry\TextEntrySize::Large),
                                TextEntry::make('date')
                                    ->date('F j, Y'),
                                TextEntry::make('transaction_type')
                                    ->badge(),
                            ]),
                            Group::make([
                                TextEntry::make('vehicle.vehicle_number')
                                    ->label('Vehicle')
                                    ->icon('heroicon-o-truck')
                                    ->iconPosition(IconPosition::Before),
                                TextEntry::make('customer.name')
                                    ->label('Customer')
                                    ->icon('heroicon-o-user')
                                    ->iconPosition(IconPosition::Before),
                            ])->extraAttributes(['class' => 'text-end']),
                        ])->from('md'),
                    ]),

                Section::make('Items')
                    ->schema([
                        RepeatableEntry::make('sale_items')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('stock_item_id')
                                            ->label('Product')
                                            ->formatStateUsing(fn ($state) => \App\Models\StockItem::find($state)?->product_name ?? 'Unknown Product')
                                            ->weight(FontWeight::Medium)
                                            ->columnSpan(1),

                                        Group::make([
                                            TextEntry::make('quantity'),
                                        ])->columnSpan(1),

                                        Group::make([
                                            TextEntry::make('unit_price')
                                                ->label('Unit Price')
                                                ->money('mvr')
                                                ->extraAttributes(['class' => 'text-end']),

                                            TextEntry::make('total_price')
                                                ->label('Total')
                                                ->money('mvr')
                                                ->weight(FontWeight::Bold)
                                                ->extraAttributes(['class' => 'text-end']),
                                        ])->columnSpan(1),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Summary')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Split::make([
                                    TextEntry::make('')
                                        ->state(''),
                                    Group::make([
                                        TextEntry::make('subtotal_amount')
                                            ->label('Subtotal')
                                            ->money('mvr')
                                            ->extraAttributes(['class' => 'text-end']),

                                        TextEntry::make('discount_percentage')
                                            ->label('Discount')
                                            ->formatStateUsing(fn (
                                                $state,
                                                $record
                                            ) => "{$state}% (MVR ".number_format($record->discount_amount,
                                                2).')')
                                            ->extraAttributes(['class' => 'text-end'])
                                            ->visible(fn ($record) => $record->discount_percentage > 0),

                                        TextEntry::make('total_amount')
                                            ->label('Total')
                                            ->money('mvr')
                                            ->size(TextEntry\TextEntrySize::Large)
                                            ->weight(FontWeight::Bold)
                                            ->color('primary')
                                            ->extraAttributes(['class' => 'text-end']),
                                    ]),
                                ])->from('md'),
                            ]),
                    ]),

                Section::make('Notes')
                    ->schema([
                        TextEntry::make('remarks')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(fn ($record) => empty($record->remarks)),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->url(fn () => route('pos.invoice.pdf', ['pos' => $this->record])),
        ];
    }
}
