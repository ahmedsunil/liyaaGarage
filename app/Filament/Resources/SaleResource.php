<?php

namespace App\Filament\Resources;

use Exception;
use Filament\Forms;
use App\Models\Sale;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use App\Models\StockItem;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use App\Support\Enums\TransactionType;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\SaleResource\Pages;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    /**
     * @throws Exception
     */
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
                                                                             ->reactive()
                                                                             ->live()
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
                                                                                                   $set('total_price',
                                                                                                       $stockItem->total);
                                                                                                   static::updateFormTotals($get,
                                                                                                       $set);
                                                                                               }
                                                                                           }
                                                                                       }),

                                                                                 TextInput::make('quantity')
                                                                                          ->label(fn(Get $get
                                                                                          ) => $get('is_liquid') ? 'Volume (in ML)' : 'Quantity')
                                                                                          ->numeric()
                                                                                          ->default(1)
                                                                                          ->required()
                                                                                          ->reactive()
                                                                                          ->live()
                                                                                          ->rules(['min:0'])
                                                                                          ->afterStateUpdated(function (
                                                                                              $state,
                                                                                              Set $set,
                                                                                              Get $get
                                                                                          ) {
                                                                                              $quantity = floatval($state ?? 1);
                                                                                              $unitPrice = floatval($get('unit_price') ?? 0);
                                                                                              $set('total_price',
                                                                                                  round($quantity * $unitPrice,
                                                                                                      2));

                                                                                              // The issue is here - we need to make sure we have a valid itemKey
                                                                                              $itemKey = $get('../../key');

                                                                                              if ($itemKey !== null) {
                                                                                                  // Only call the method if we have a valid key
                                                                                                  static::updateItemTotalByKey($get,
                                                                                                      $set, $itemKey);
                                                                                              } else {
                                                                                                  // Handle the calculation directly if key is missing
                                                                                                  $set('total_price',
                                                                                                      round($quantity * $unitPrice,
                                                                                                          2));
                                                                                                  static::updateFormTotals($get,
                                                                                                      $set);
                                                                                              }
                                                                                          }),

                                                                                 TextInput::make('unit_price')
                                                                                          ->label('Unit Price')
                                                                                          ->prefix('MVR')
                                                                                          ->numeric()
                                                                                          ->disabled()
                                                                                          ->dehydrated(true),

                                                                                 TextInput::make('total_price')
                                                                                          ->label('Total Price')
                                                                                          ->prefix('MVR')
                                                                                          ->numeric()
                                                                                          ->disabled()
                                                                                          ->dehydrated(true),
                                                                             ])
                                                                             ->afterStateUpdated(fn(
                                                                                 Get $get,
                                                                                 Set $set
                                                                             ) => static::updateFormTotals($get, $set))
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

                                                                     TextInput::make('subtotal_amount')
                                                                              ->label('Subtotal')
                                                                              ->prefix('MVR')
                                                                              ->disabled()
                                                                              ->reactive()
                                                                              ->live(),

                                                                     TextInput::make('discount_percentage')
                                                                              ->label('Discount %')
                                                                              ->suffix('%')
                                                                              ->default(0)
                                                                              ->reactive()
                                                                              ->live(),

                                                                     TextInput::make('discount_amount')
                                                                              ->label('Discount Amount')
                                                                              ->prefix('MVR')
                                                                              ->disabled()
                                                                              ->reactive()
                                                                              ->dehydrated(true),

                                                                     TextInput::make('total_amount')
                                                                              ->label('Total Amount')
                                                                              ->prefix('MVR')
                                                                              ->reactive()
                                                                              ->readOnly(),
                                                                 ]),
                                     ]),
            ]);
    }

    protected static function updateFormTotals(Forms\Get $get, Forms\Set $set): void
    {
        $items = $get('items') ?? [];
        $subtotal = array_sum(array_column($items, 'total_price'));
        $set('subtotal_amount', round($subtotal, 2));

        $discountPercentage = floatval($get('discount_percentage') ?? 0);
        $discountAmount = $subtotal * ($discountPercentage / 100);
        $set('discount_amount', round($discountAmount, 2));

        $totalAmount = $subtotal - $discountAmount;
        $set('total_amount', round($totalAmount, 2));
    }

    protected static function updateItemTotalByKey(Get $get, Set $set, ?string $itemKey): void
    {
        // Return early if no key is provided
        if ($itemKey === null) {
            return;
        }

        $quantity = floatval($get("items.{$itemKey}.quantity") ?? 1);
        $unitPrice = floatval($get("items.{$itemKey}.unit_price") ?? 0);
        $totalPrice = round($quantity * $unitPrice, 2);
        $set("items.{$itemKey}.total_price", $totalPrice);
    }



    //    protected static function updateFormTotals(Get $get, Set $set): void
    //    {
    //        // Get all items from the repeater
    //        $items = $get('items') ?? [];
    //
    //        // Calculate subtotal by summing all total_price values
    //        $subtotal = 0;
    //        foreach ($items as $item) {
    //            if (isset($item['total_price'])) {
    //                $subtotal += floatval($item['total_price']);
    //            }
    //        }
    //
    //        // Set the subtotal
    //        $set('subtotal_amount', round($subtotal, 2));
    //
    //        // Calculate discount
    //        $discountPercentage = floatval($get('discount_percentage') ?? 0);
    //        $discountAmount = ($subtotal * $discountPercentage) / 100;
    //        $set('discount_amount', round($discountAmount, 2));
    //
    //        // Calculate final total
    //        $totalAmount = $subtotal - $discountAmount;
    //        $set('total_amount', round($totalAmount, 2));
    //    }

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
}
