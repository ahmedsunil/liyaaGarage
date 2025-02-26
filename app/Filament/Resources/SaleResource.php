<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Sale;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\StockItem;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\SaleResource\Pages;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(12)
                    ->schema([
                        Forms\Components\Section::make('Sale Items')
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\Select::make('stock_item_id')
                                            ->label('Product')
                                            ->options(StockItem::query()->pluck('product_name',
                                                'id'))
                                            ->searchable()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (
                                                Forms\Get $get,
                                                Forms\Set $set,
                                                $state
                                            ) {
                                                if ($state) {
                                                    $stockItem = StockItem::find($state);
                                                    if ($stockItem) {
                                                        $set('unit_price',
                                                            $stockItem->total);
                                                        $set('quantity',
                                                            1);
                                                        $set('total_price',
                                                            $stockItem->total);
                                                    }
                                                }
                                                static::updateFormTotals($get,
                                                    $set);
                                            }),

                                        Forms\Components\TextInput::make('quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (
                                                Forms\Get $get,
                                                Forms\Set $set,
                                                $state
                                            ) {
                                                $unitPrice = floatval($get('unit_price'));
                                                $quantity = floatval($state);
                                                $set('total_price',
                                                    round($unitPrice * $quantity,
                                                        2));
                                                static::updateFormTotals($get,
                                                    $set);
                                            }),

                                        Forms\Components\TextInput::make('unit_price')
                                            ->label('Unit Price')
                                            ->numeric()
                                            ->prefix('MVR')
                                            ->disabled()
                                            ->dehydrated(true),

                                        Forms\Components\TextInput::make('total_price')
                                            ->label('Total Price')
                                            ->numeric()
                                            ->prefix('MVR')
                                            ->disabled()
                                            ->dehydrated(true),
                                    ])
                                    ->columns(4)
                                    ->defaultItems(0)
                                    ->addActionLabel('Add Item')
                                    ->reorderable(false)
                                    ->cloneable(false)
                                    ->deletable(true),
                            ])
                            ->columnSpan(9),

                        Forms\Components\Section::make('Sale Details')
                            ->schema([
                                Forms\Components\DatePicker::make('date')
                                    ->required()
                                    ->default(now()),

                                Forms\Components\Select::make('vehicle_id')
                                    ->label('Vehicle')
                                    ->relationship('vehicle',
                                        'vehicle_number')
                                    ->searchable()
                                    ->required(),

                                Forms\Components\Select::make('transaction_type')
                                    ->options([
                                        'cash' => 'Cash',
                                        'bank_transfer' => 'Bank Transfer',
                                        'card' => 'Card',
                                    ])
                                    ->required(),

                                Forms\Components\TextInput::make('subtotal_amount')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('MVR')
                                    ->disabled()
                                    ->dehydrated(true),

                                Forms\Components\TextInput::make('discount_percentage')
                                    ->label('Discount %')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('%')
                                    ->live()
                                    ->afterStateUpdated(function (
                                        Forms\Get $get,
                                        Forms\Set $set
                                    ) {
                                        static::updateFormTotals($get,
                                            $set);
                                    }),

                                Forms\Components\TextInput::make('discount_amount')
                                    ->label('Discount Amount')
                                    ->numeric()
                                    ->prefix('MVR')
                                    ->disabled()
                                    ->dehydrated(true),

                                Forms\Components\TextInput::make('total_amount')
                                    ->label('Total Amount')
                                    ->numeric()
                                    ->prefix('MVR')
                                    ->disabled()
                                    ->dehydrated(true),

                                Forms\Components\Textarea::make('remarks')
                                    ->rows(3),
                            ])
                            ->columnSpan(3),
                    ]),
            ]);
    }

    protected static function updateFormTotals(Forms\Get $get, Forms\Set $set): void
    {
        $items = $get('items') ?? [];
        $subtotal = array_sum(array_column($items, 'total_price'));
        $set('subtotal_amount', round($subtotal, 2));

        $discountPercentage = floatval($get('discount_percentage') ?? 0);
        $discountAmount = ($subtotal * $discountPercentage) / 100;
        $set('discount_amount', round($discountAmount, 2));

        $totalAmount = $subtotal - $discountAmount;
        $set('total_amount', round($totalAmount, 2));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }

    public static function afterCreate(Model $record): void
    {
        foreach ($record->items as $item) {
            $stockItem = StockItem::find($item->stock_item_id);
            if ($stockItem) {
                $stockItem->quantity -= $item->quantity;
                $stockItem->save();
            }
        }
    }
}
