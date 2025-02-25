<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Sale;
use Filament\Tables;
use App\Models\Vehicle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use App\Models\StockItem;
use Filament\Tables\Table;
use Filament\Resources\Resource;
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
//                    ->schema([
//                        Forms\Components\Section::make()
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
                                                Get $get,
                                                Set $set
                                            ) {
                                                $stockItem = StockItem::find($get('stock_item_id'));
                                                if ($stockItem) {
                                                    $set('unit_price',
                                                        $stockItem->sale_price);
                                                    $set('product_type',
                                                        $stockItem->product_type);
                                                    $set('unit_type',
                                                        $stockItem->unit_type);
                                                    $set('available_quantity',
                                                        $stockItem->available_quantity);
                                                    $set('remaining_volume',
                                                        $stockItem->remaining_volume);
                                                }
                                            }),

                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (
                                                Get $get,
                                                Set $set
                                            ) {
                                                self::calculateTotal($get,
                                                    $set);
                                                self::updateRemainingQuantity($get,
                                                    $set);
                                            }),

                                        Forms\Components\TextInput::make('volume')
                                            ->label('Volume')
                                            ->numeric()
                                            ->required()
                                            ->visible(fn (
                                                Get $get
                                            ) => $get('product_type') === 'liquid')
                                            ->live()
                                            ->afterStateUpdated(function (
                                                Get $get,
                                                Set $set
                                            ) {
                                                self::calculateTotal($get,
                                                    $set);
                                                self::updateRemainingVolume($get,
                                                    $set);
                                            }),

                                        Forms\Components\TextInput::make('unit_price')
                                            ->label('Unit Price')
                                            ->numeric()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(fn (
                                                Get $get,
                                                Set $set
                                            ) => self::calculateTotal($get,
                                                $set)),

                                        Forms\Components\TextInput::make('total_price')
                                            ->label('Total Price')
                                            ->numeric()
                                            ->disabled(),

                                        Forms\Components\Hidden::make('product_type'),
                                        Forms\Components\Hidden::make('unit_type'),
                                        Forms\Components\Hidden::make('available_quantity'),
                                        Forms\Components\Hidden::make('remaining_volume'),
                                    ])
                                    ->columns(4),
                            ])->columnSpan(9),

                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\DatePicker::make('date')
                                    ->required()
                                    ->default(now()),

                                Forms\Components\Select::make('vehicle_number')
                                    ->label('Vehicle')
                                    ->options(Vehicle::pluck(
                                        'vehicle_number', 'id'))
                                    ->searchable()
                                    ->required(),

                                Forms\Components\Select::make('transaction_type')
                                    ->options([
                                        'cash' => 'Cash',
                                        'bank_transfer' => 'Bank Transfer',
                                        'none' => 'None',
                                    ]),
                                Forms\Components\TextInput::make('subtotal_amount')
                                    ->numeric()
                                    ->prefix('MVR')
                                    ->disabled(),

                                Forms\Components\TextInput::make('discount_percentage')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(function (
                                        Get $get,
                                        Set $set
                                    ) {
                                        $discountAmount = $get('subtotal_amount') * ($get('discount_percentage') / 100);
                                        $set('discount_amount',
                                            $discountAmount);
                                        $set('total_amount',
                                            $get('subtotal_amount') - $discountAmount);
                                    }),

                                Forms\Components\TextInput::make('discount_amount')
                                    ->numeric()
                                    ->prefix('MVR')
                                    ->disabled(),

                                Forms\Components\TextInput::make('total_amount')
                                    ->numeric()
                                    ->prefix('MVR')
                                    ->disabled(),
                                Forms\Components\Textarea::make('remarks')
                                    ->maxLength(65535),
                            ])->columnSpan(3),
                    ]),
                //                    ]),
            ]);
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
}
