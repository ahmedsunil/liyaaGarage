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

    public static function afterCreate(Model $record): void
    {
        foreach ($record->items as $item) {
            $stockItem = StockItem::find($item->stock_item_id);
            if (! $stockItem) {
                continue;
            }

            if ($stockItem->is_liquid) {
                $stockItem->remaining_volume = max(0, $stockItem->remaining_volume - $item->quantity);
                $stockItem->quantity = ceil($stockItem->remaining_volume / $stockItem->volume_per_unit);
            } else {
                $stockItem->quantity = max(0, $stockItem->quantity - $item->quantity);
            }

            $stockItem->inventory_value = $stockItem->quantity * $stockItem->total;
            $stockItem->save();
        }
    }

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
                Tables\Columns\TextColumn::make('payment_status')->label('Payment Status')->badge(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->money('mvr')
                    ->sortable(),

                Tables\Columns\TextColumn::make('transaction_type')
                    ->sortable(),
            ])
            ->filters([
                //                Tables\Filters\SelectFilter::make('payment_type')
                //                                           ->options(PaymentType::class),
                //                Tables\Filters\SelectFilter::make('payment_status')
                //                                           ->options(PaymentStatus::class),

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
                                Forms\Components\Repeater::make('items')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\Select::make('stock_item_id')
                                            ->label('Product')
                                            ->options(StockItem::query()->pluck('product_name',
                                                'id'))
                                            ->searchable()
                                            ->required()
                                            ->reactive()
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

                                                        // Check if it's a service item and set quantity to 1
                                                        if ($stockItem->is_service) {
                                                            $set('quantity',
                                                                1);
                                                        } else {
                                                            $set('quantity',
                                                                1);
                                                        }

                                                        static::updateItemTotal($get,
                                                            $set);
                                                    }
                                                }
                                                static::updateFormTotals($get,
                                                    $set);
                                            }),

                                        // 2. Modify the quantity field to be conditionally disabled
                                        Forms\Components\TextInput::make('quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->reactive()
                                            ->disabled(fn (
                                                Forms\Get $get
                                            ): bool => $get('is_service') === true)
                                            ->afterStateUpdated(function (
                                                Forms\Get $get,
                                                Forms\Set $set
                                            ) {
                                                static::updateItemTotal($get,
                                                    $set);
                                                static::updateFormTotals($get,
                                                    $set);
                                            }),

                                        // Rest of your fields remain the same
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
                                    // 3. To ensure calculations are triggered when form loads, add this
                                    ->afterStateHydrated(function (
                                        Forms\Get $get,
                                        Forms\Set $set
                                    ) {
                                        $items = $get('items') ?? [];

                                        if (count($items) > 0) {
                                            // Instead of trying to pass closures to updateItemTotal,
                                            // directly calculate and set the values for each item
                                            foreach ($items as $itemKey => $item) {
                                                $quantity = floatval($get("items.{$itemKey}.quantity") ?? 1);
                                                $unitPrice = floatval($get("items.{$itemKey}.unit_price") ?? 0);
                                                $totalPrice = round($quantity * $unitPrice,
                                                    2);
                                                $set("items.{$itemKey}.total_price",
                                                    $totalPrice);
                                            }

                                            // Then update the form totals
                                            static::updateFormTotals($get, $set);
                                        }
                                    })
                                    ->columns(4)
                                    ->defaultItems(0)
                                    ->addActionLabel('Add Item')
                                    ->reorderable(false)
                                    ->cloneable(false)
                                    ->deletable(true)
                                    ->reactive()
                                    ->afterStateUpdated(function (
                                        Forms\Get $get,
                                        Forms\Set $set
                                    ) {
                                        static::updateFormTotals($get,
                                            $set);
                                    }),
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
                                Forms\Components\Select::make('payment_status')->label('Payment Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'paid' => 'Paid',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (
                                        Forms\Get $get,
                                        Forms\Set $set
                                    ) {
                                        static::updateFormTotals($get,
                                            $set);
                                    }),
                                Forms\Components\Select::make('transaction_type')
                                    ->options([
                                        'cash' => 'Cash',
                                        'bank_transfer' => 'Bank Transfer',
                                        'card' => 'Card',
                                    ])
                                    ->visible(fn (Forms\Get $get
                                    ) => $get('payment_status') === 'paid'),
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
                                    ->readOnly(),
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
            ]);
    }

    protected static function updateItemTotal(Forms\Get $get, Forms\Set $set): void
    {
        $quantity = floatval($get('quantity') ?? 1);
        $unitPrice = floatval($get('unit_price') ?? 0);
        $totalPrice = round($quantity * $unitPrice, 2);
        $set('total_price', $totalPrice);
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

        if ($get('payment_status') === 'pending') {
            $set('transaction_type', 'none');
        }
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

    protected static function updateItemTotalByKey(Forms\Get $get, Forms\Set $set, string $itemKey): void
    {
        $quantity = floatval($get("items.{$itemKey}.quantity") ?? 1);
        $unitPrice = floatval($get("items.{$itemKey}.unit_price") ?? 0);
        $totalPrice = round($quantity * $unitPrice, 2);
        $set("items.{$itemKey}.total_price", $totalPrice);
    }
}
