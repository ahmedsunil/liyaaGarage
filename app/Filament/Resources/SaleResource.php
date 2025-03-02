<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Sale;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\StockItem;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Support\Enums\TransactionType;
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
                                                                                                                             $state,
                                                                                                                             Forms\Set $set,
                                                                                                                             Forms\Get $get
                                                                                                                         ) {
                                                                                                                             if ($state) {
                                                                                                                                 $stockItem = StockItem::find($state);
                                                                                                                                 if ($stockItem) {
                                                                                                                                     $set('unit_price',
                                                                                                                                         $stockItem->total);

                                                                                                                                     // Handle different types of items
                                                                                                                                     if ($stockItem->is_service) {
                                                                                                                                         // For services, fix quantity to 1 and set flag
                                                                                                                                         $set('is_service',
                                                                                                                                             true);
                                                                                                                                         $set('is_liquid',
                                                                                                                                             false);
                                                                                                                                         $set('quantity',
                                                                                                                                             1);
                                                                                                                                         $set('quantity',
                                                                                                                                             null); // No inventory tracking for services
                                                                                                                                         $set('available_volume',
                                                                                                                                             null);
                                                                                                                                     } elseif ($stockItem->is_liquid) {
                                                                                                                                         // For liquid items, track available volume
                                                                                                                                         $set('is_service',
                                                                                                                                             false);
                                                                                                                                         $set('is_liquid',
                                                                                                                                             true);
                                                                                                                                         $set('quantity',
                                                                                                                                             1);
                                                                                                                                         $set('quantity',
                                                                                                                                             $stockItem->quantity);
                                                                                                                                         $set('available_volume',
                                                                                                                                             $stockItem->remaining_volume);
                                                                                                                                         $set('volume_per_unit',
                                                                                                                                             $stockItem->volume_per_unit);
                                                                                                                                     } else {
                                                                                                                                         // For discrete items, track available quantity
                                                                                                                                         $set('is_service',
                                                                                                                                             false);
                                                                                                                                         $set('is_liquid',
                                                                                                                                             false);
                                                                                                                                         $set('quantity',
                                                                                                                                             1);
                                                                                                                                         $set('quantity',
                                                                                                                                             $stockItem->quantity);
                                                                                                                                         $set('available_volume',
                                                                                                                                             null);
                                                                                                                                     }

                                                                                                                                     static::updateItemTotal($get,
                                                                                                                                         $set);
                                                                                                                                 }
                                                                                                                             }
                                                                                                                             static::updateFormTotals($get,
                                                                                                                                 $set);
                                                                                                                         }),

                                                                                                  // 2. Modify the quantity field to be conditionally disabled
                                                                                                  // Modify the quantity field for different product types
                                                                                                  Forms\Components\TextInput::make('quantity')
                                                                                                                            ->label('Quantity')
                                                                                                                            ->numeric()
                                                                                                                            ->default(1)
                                                                                                                            ->required()
                                                                                                                            ->reactive()
                                                                                                                            ->afterStateUpdated(function (
                                                                                                                                $state,
                                                                                                                                Forms\Set $set,
                                                                                                                                Forms\Get $get
                                                                                                                            ) {
                                                                                                                                $quantity = floatval($state ?? 1);
                                                                                                                                $unitPrice = floatval($get('unit_price') ?? 0);
                                                                                                                                $set('total_price',
                                                                                                                                    round($quantity * $unitPrice,
                                                                                                                                        2));
                                                                                                                            }),

                                                                                                  Forms\Components\Hidden::make('is_service')
                                                                                                                         ->default(false)
                                                                                                                         ->dehydrated(false),

                                                                                                  Forms\Components\Hidden::make('is_liquid')
                                                                                                                         ->default(false)
                                                                                                                         ->dehydrated(false),

                                                                                                  Forms\Components\Hidden::make('quantity')
                                                                                                                         ->dehydrated(false),

                                                                                                  Forms\Components\Hidden::make('available_volume')
                                                                                                                         ->dehydrated(false),

                                                                                                  Forms\Components\Hidden::make('volume_per_unit')
                                                                                                                         ->dehydrated(false),

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
                                                                                              ->afterStateHydrated(function (
                                                                                                  Forms\Get $get,
                                                                                                  Forms\Set $set
                                                                                              ) {
                                                                                                  $items = $get('items') ?? [];

                                                                                                  if (count($items) > 0) {
                                                                                                      foreach ($items as $itemKey => $item) {
                                                                                                          $quantity = floatval($get("items.{$itemKey}.quantity") ?? 1);
                                                                                                          $unitPrice = floatval($get("items.{$itemKey}.unit_price") ?? 0);
                                                                                                          $totalPrice = round($quantity * $unitPrice,
                                                                                                              2);
                                                                                                          $set("items.{$itemKey}.total_price",
                                                                                                              $totalPrice);
                                                                                                      }

                                                                                                      // Then update the form totals
                                                                                                      static::updateFormTotals($get,
                                                                                                          $set);
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
                                                                 ->mutateRelationshipDataBeforeCreateUsing(function (
                                                                     array $data
                                                                 ): array {
                                                                     // Ensure total_price is calculated before creating
                                                                     $data['total_price'] = round(($data['quantity'] ?? 1) * ($data['unit_price'] ?? 0),
                                                                         2);

                                                                     return $data;
                                                                 })
                                                                 ->mutateRelationshipDataBeforeSaveUsing(function (
                                                                     array $data
                                                                 ): array {
                                                                     $data['total_price'] = round(($data['quantity'] ?? 1) * ($data['unit_price'] ?? 0),
                                                                         2);

                                                                     return $data;
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
                                                                                                Forms\Get $get,
                                                                                                Forms\Set $set
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
            $set('transaction_type', TransactionType::PENDING);
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
            'index'  => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit'   => Pages\EditSale::route('/{record}/edit'),
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
