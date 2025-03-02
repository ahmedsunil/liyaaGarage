<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Sale;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use App\Models\StockItem;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use App\Support\Enums\TransactionType;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\SaleResource\Pages;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehicle.customer.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehicle.vehicle_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_type')->label('Transaction Type')->badge(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->money('mvr')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->options(TransactionType::class),

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(12)
                    ->schema([
                        Forms\Components\Section::make('Sale Items')
                            ->schema([
                                Repeater::make('items')
                                    ->relationship()
                                    ->schema([
                                        Select::make('stock_item_id')
                                            ->label('Product')
                                            ->options(StockItem::query()->pluck('product_name',
                                                'id'))
                                            ->searchable()
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function (
                                                $state,
                                                Set $set,
                                                Get $get
                                            ) {
                                                if ($state) {
                                                    $stockItem = StockItem::find($state);
                                                    if ($stockItem) {
                                                        $set('unit_price',
                                                            $stockItem->total);
                                                        $set('is_liquid',
                                                            $stockItem->is_liquid);
                                                        $set('volume_per_unit',
                                                            $stockItem->volume_per_unit);
                                                        $set('quantity', 1);
                                                        $set('volume',
                                                            $stockItem->is_liquid ? $stockItem->volume_per_unit : null);
                                                    }
                                                }
                                            }),

                                        TextInput::make('quantity')
                                            ->label(function (Get $get) {
                                                $stockItem = StockItem::find($get('stock_item_id'));
                                                if ($stockItem && $stockItem->product_type === 'liquid') {
                                                    return 'Volume (in ML)';
                                                }

                                                return 'Quantity';
                                            })
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->reactive()
                                            ->disabled(function (
                                                Forms\Get $get
                                            ) {
                                                return $get('is_service');
                                            })
                                            ->afterStateUpdated(function (
                                                $state,
                                                Set $set,
                                                Get $get
                                            ) {
                                                $stockItem = StockItem::find($get('stock_item_id'));
                                                $quantity = floatval($state ?? 1);
                                                $unitPrice = floatval($get('unit_price') ?? 0);

                                                if ($stockItem && $stockItem->is_liquid) {
                                                    // For liquid items, calculate total price based on required containers
                                                    $volumePerUnit = floatval($stockItem->volume_per_unit ?? 0);
                                                    $requiredContainers = ceil($quantity / $volumePerUnit);
                                                    $set('total_price',
                                                        round($requiredContainers * $unitPrice,
                                                            2));
                                                } else {
                                                    // For discrete items
                                                    $set('total_price',
                                                        round($quantity * $unitPrice,
                                                            2));
                                                }
                                            }),

                                        TextInput::make('volume')
                                            ->label('Volume (ml)')
                                            ->numeric()
                                            ->required()
                                            ->reactive()
                                            ->visible(fn (
                                                Get $get
                                            ) => $get('is_liquid') ?? false)
                                            ->afterStateUpdated(function (
                                                $state,
                                                Set $set,
                                                Get $get
                                            ) {
                                                $volume = floatval($state ?? 0);
                                                $volumePerUnit = floatval($get('volume_per_unit') ?? 1);
                                                $unitPrice = floatval($get('unit_price') ?? 0);

                                                $quantity = ceil($volume / $volumePerUnit);
                                                $set('quantity',
                                                    $quantity);
                                                $set('total_price',
                                                    round($quantity * $unitPrice,
                                                        2));
                                            }),

                                        TextInput::make('unit_price')
                                            ->label('Unit Price')
                                            ->prefix('MVR')
                                            ->numeric()
                                            ->required()
                                            ->reactive()
                                            ->disabled()
                                            ->dehydrated(true),

                                        TextInput::make('total_price')
                                            ->label('Total Price')
                                            ->prefix('MVR')
                                            ->numeric()
                                            ->required()
                                            ->disabled()
                                            ->dehydrated(true),

                                        Hidden::make('is_liquid'),
                                        Hidden::make('volume_per_unit'),
                                    ])
                                    ->columns(4)
                                    ->defaultItems(0)
                                    ->afterStateHydrated(function (
                                        Repeater $component,
                                        $state
                                    ) {
                                        foreach ($state as $key => $item) {
                                            if (isset($item['stock_item_id'])) {
                                                $stockItem = StockItem::find($item['stock_item_id']);
                                                if ($stockItem && $stockItem->is_liquid) {
                                                    $volume = $item['volume'] ?? ($item['quantity'] * $stockItem->volume_per_unit);
                                                    $component->getChildComponentContainer($key)->fill([
                                                        'volume' => $volume,
                                                        'is_liquid' => true,
                                                        'volume_per_unit' => $stockItem->volume_per_unit,
                                                    ]);
                                                }
                                            }
                                        }
                                    })
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

                                        Forms\Components\Select::make('transaction_type')->label('Transaction Type')
                                            ->options(TransactionType::class)
                                            ->default(TransactionType::PENDING)
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function (
                                                Get $get,
                                                Set $set
                                            ) {
                                                static::updateFormTotals($get,
                                                    $set);
                                            }),

                                        Forms\Components\TextInput::make('subtotal_amount')
                                            ->label('Subtotal')
                                            ->numeric()
                                            ->prefix('MVR')
                                            ->disabled(),
                                        Forms\Components\TextInput::make('discount_percentage')
                                            ->label('Discount %')
                                            ->numeric()
                                            ->default(0)
                                            ->suffix('%')
                                            ->reactive()
                                            ->afterStateUpdated(function (
                                                Get $get,
                                                Set $set
                                            ) {
                                                $subtotal = floatval($get('subtotal_amount') ?? 0);
                                                $discountPercentage = floatval($get('discount_percentage') ?? 0);
                                                $discountAmount = $subtotal * ($discountPercentage / 100);
                                                $totalAmount = $subtotal - $discountAmount;
                                                $set('discount_amount',
                                                    round($discountAmount,
                                                        2));
                                                $set('total_amount',
                                                    round($totalAmount,
                                                        2));
                                            }),

                                        Forms\Components\TextInput::make('discount_amount')
                                            ->label('Discount Amount')
                                            ->numeric()
                                            ->prefix('MVR')
                                            ->default(0)
                                            ->disabled()
                                            ->dehydrated(true),

                                        Forms\Components\TextInput::make('total_amount')
                                            ->label('Total Amount')
                                            ->numeric()
                                            ->prefix('MVR')
                                            ->readOnly(),

                                        Forms\Components\Textarea::make('remarks')
                                            ->rows(3),
                                    ])
                                    ->columnSpan(3),
                            ]),
                    ]),
            ]);
    }

    protected static function updateFormTotals(Get $get, Set $set): void
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

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        if ($data['payment_status'] !== 'paid') {
            $data['transaction_type'] = 'none';
        }

        return $data;
    }

    protected static function updateItemTotal(Get $get, Set $set): void
    {
        $quantity = floatval($get('quantity') ?? 1);
        $unitPrice = floatval($get('unit_price') ?? 0);
        $totalPrice = round($quantity * $unitPrice, 2);
        $set('total_price', $totalPrice);
    }

    protected static function updateItemTotalByKey(Get $get, Set $set, string $itemKey): void
    {
        $quantity = floatval($get("items.{$itemKey}.quantity") ?? 1);
        $unitPrice = floatval($get("items.{$itemKey}.unit_price") ?? 0);
        $totalPrice = round($quantity * $unitPrice, 2);
        $set("items.{$itemKey}.total_price", $totalPrice);
    }
}
