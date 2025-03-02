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
                                                                                       ->live()
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

                                                                                                   // Simple calculation for initial total price
                                                                                                   $set('total_price',
                                                                                                       $stockItem->total);

                                                                                                   // Update form totals
                                                                                                   static::updateFormTotals($get,
                                                                                                       $set);
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
                                                                                          ->live(onBlur: true)
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
                                                                                              $total = floatval($quantity) * floatval($unitPrice);
                                                                                              $set('total_price',
                                                                                                  $total);
                                                                                          }),

                                                                                 TextInput::make('unit_price')
                                                                                          ->label('Unit Price')
                                                                                          ->prefix('MVR')
                                                                                          ->numeric()
                                                                                          ->required()
                                                                                          ->reactive()
                                                                                          ->readOnly()
                                                                                          ->dehydrated(true)
                                                                                          ->afterStateUpdated(function (
                                                                                              Set $set,
                                                                                              Get $get
                                                                                          ) {
                                                                                              $qty = floatval($get['quantity'] ?? 0);
                                                                                              $unit_price = floatval($get['unit_price'] ?? 0);

                                                                                              $total_price = floatval($qty * $unit_price);
                                                                                              $set('total_price',
                                                                                                  $total_price);
                                                                                          }),

                                                                                 TextInput::make('total_price')
                                                                                          ->label('Total Price')
                                                                                          ->prefix('MVR')
                                                                                          ->numeric()
                                                                                          ->required()
                                                                                          ->reactive()
                                                                                          ->readonly(true)
                                                                                          ->dehydrated(true)
                                                                                          ->afterStateUpdated(function (
                                                                                              Set $set,
                                                                                              Get $get
                                                                                          ) {
                                                                                              $qty = floatval($get['quantity'] ?? 0);
                                                                                              $unit_price = floatval($get['unit_price'] ?? 0);

                                                                                              $total_price = floatval($qty * $unit_price);
                                                                                              $set('total_price',
                                                                                                  $total_price);
                                                                                          }),

                                                                                 Hidden::make('volume_per_unit'),
                                                                             ])
                                                                             ->columns(4)
                                                                             ->defaultItems(0),

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

                                                                     Forms\Components\Select::make('transaction_type')->label('Transaction Type')
                                                                                            ->options(TransactionType::class)
                                                                                            ->default(TransactionType::PENDING)
                                                                                            ->required()
                                                                                            ->reactive(),

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
            ]);
    }

    protected static function updateFormTotals(Get $get, Set $set): void
    {
        // Calculate subtotal from repeater items
        $items = $get('items') ?? [];
        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += floatval($item['total_price'] ?? 0);
        }

        $set('subtotal_amount', round($subtotal, 2));

        // Calculate discount
        $discountPercentage = floatval($get('discount_percentage') ?? 0);
        $discountAmount = ($subtotal * $discountPercentage) / 100;
        $set('discount_amount', round($discountAmount, 2));

        // Calculate final total
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
            'index'  => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit'   => Pages\EditSale::route('/{record}/edit'),
        ];
    }

    protected static function updateItemTotalByKey(Get $get, Set $set, string $itemKey): void
    {
        $quantity = floatval($get("items.{$itemKey}.quantity") ?? 1);
        $unitPrice = floatval($get("items.{$itemKey}.unit_price") ?? 0);
        $totalPrice = round($quantity * $unitPrice, 2);
        $set("items.{$itemKey}.total_price", $totalPrice);
    }
}
