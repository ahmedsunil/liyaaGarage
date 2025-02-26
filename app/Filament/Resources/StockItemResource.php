<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Vendor;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use App\Models\StockItem;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\ToggleButtons;
use App\Filament\Resources\StockItemResource\Pages;

class StockItemResource extends Resource
{
    protected static ?string $model = StockItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $label = 'Product / Service';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                                        ->schema([
                                            Forms\Components\ToggleButtons::make('is_service')
                                                                          ->label('Item Type')
                                                                          ->inline()
                                                                          ->live()
                                                                          ->required()
                                                                          ->options([
                                                                              0 => 'Product',
                                                                              1 => 'Service',
                                                                          ])
                                                                          ->colors([
                                                                              0 => 'primary',
                                                                              1 => 'warning',
                                                                          ])
                                                                          ->icons([
                                                                              0 => 'heroicon-m-shopping-bag',
                                                                              1 => 'heroicon-m-wrench-screwdriver',
                                                                          ])
                                                                          ->default(0)
                                                                          ->afterStateUpdated(function (
                                                                              Get $get,
                                                                              Set $set
                                                                          ) {
                                                                              // Reset fields when toggling
                                                                              $set('sale_price', null);
                                                                              $set('service_price', null);
                                                                              $set('total_price', null);
                                                                              $set('quantity', null);
                                                                              $set('available_quantity', null);
                                                                              $set('product_type', null);
                                                                              $set('unit_type', null);
                                                                              $set('volume_per_unit', null);
                                                                              $set('remaining_volume', null);
                                                                          }),

                                            Forms\Components\TextInput::make('item_code')
                                                                      ->label(fn(Get $get
                                                                      ) => $get('is_service') ? 'Service Code' : 'Item Code')
                                                                      ->required()
                                                                      ->maxLength(255),

                                            Forms\Components\TextInput::make('product_name')
                                                                      ->label(fn(Get $get
                                                                      ) => $get('is_service') ? 'Service Name' : 'Product Name')
                                                                      ->required()
                                                                      ->maxLength(255),
                                            Forms\Components\Select::make('vendor_id')
                                                                   ->relationship('vendor') // Relationship name
                                                                   ->searchable() // Make the dropdown searchable
                                                                   ->label('Vendor') // Label for the field
                                                                   ->options(Vendor::get()->pluck('name',
                                                    'id')) // Use get() to ensure a collection
                                                                   ->visible(fn(Get $get) => ! $get('is_service')),
                                            // Visible if 'is_service' is 0

                                        ])
                                        ->columns(4),

                Forms\Components\Section::make('Product Details')
                                        ->schema([
                                            ToggleButtons::make('product_type')
                                                         ->label('Product Type')
                                                         ->inline()
                                                         ->options([
                                                             'discrete' => 'Discrete (Countable)',
                                                             'liquid'   => 'Liquid (Measurable)',
                                                         ])
                                                         ->colors([
                                                             'discrete' => 'success',
                                                             'liquid'   => 'info',
                                                         ])
                                                         ->icons([
                                                             'discrete' => 'heroicon-m-cube',
                                                             'liquid'   => 'heroicon-m-beaker',
                                                         ])
                                                         ->required()
                                                         ->live()
                                                         ->default('discrete')
                                                         ->visible(fn(Get $get) => ! $get('is_service'))
                                                         ->afterStateUpdated(function (Get $get, Set $set) {
                                                             $set('unit_type', null);
                                                             $set('volume_per_unit', null);
                                                             $set('remaining_volume', null);
                                                         }),
                                            Forms\Components\Grid::make()
                                                                 ->schema([
                                                                     Select::make('unit_type')
                                                                           ->label('Unit Type')
                                                                           ->options(function (Get $get) {
                                                                               if ($get('product_type') === 'discrete') {
                                                                                   return [
                                                                                       'piece' => 'Piece',
                                                                                   ];
                                                                               } else {
                                                                                   return [
                                                                                       'ml' => 'Milliliter (ML)',
                                                                                       'l'  => 'Liter (L)',
                                                                                   ];
                                                                               }
                                                                           })
                                                                           ->required()
                                                                           ->live(),

                                                                     Forms\Components\TextInput::make('volume_per_unit')
                                                                                               ->label('Volume per Unit')
                                                                                               ->numeric()
                                                                                               ->suffix(fn(Get $get
                                                                                               ) => strtoupper($get('unit_type') ?? ''))
                                                                                               ->required()
                                                                                               ->visible(fn(Get $get
                                                                                               ) => $get('product_type') === 'liquid'),
                                                                 ])
                                                                 ->columns(2)
                                                                 ->visible(fn(Get $get) => ! $get('is_service')),
                                        ])
                                        ->visible(fn(Get $get) => ! $get('is_service')),

                Forms\Components\Section::make('Pricing & Stock')
                                        ->schema([
                                            Forms\Components\Grid::make()
                                                                 ->schema([
                                                                     Forms\Components\TextInput::make('sale_price')
                                                                                               ->label(fn(Get $get
                                                                                               ) => $get('is_service') ? 'Service Price' : 'Sale Price')
                                                                                               ->numeric()
                                                                                               ->prefix('MVR')
                                                                                               ->required()
                                                                                               ->live(onBlur: true)
                                                                                               ->afterStateUpdated(fn(
                                                                                                   Get $get,
                                                                                                   Set $set
                                                                                               ) => self::calculateTotal($get,
                                                                                                   $set)),

                                                                     Forms\Components\TextInput::make('gst')
                                                                                               ->label('GST')
                                                                                               ->numeric()
                                                                                               ->prefix('MVR')
                                                                                               ->required()
                                                                                               ->live(onBlur: true)
                                                                                               ->afterStateUpdated(fn(
                                                                                                   Get $get,
                                                                                                   Set $set
                                                                                               ) => self::calculateTotal($get,
                                                                                                   $set)),

                                                                     Forms\Components\TextInput::make('total')
                                                                                               ->label('Total Price')
                                                                                               ->numeric()
                                                                                               ->prefix('MVR')
                                                                                               ->disabled()
                                                                                               ->dehydrated()
                                                                                               ->afterStateUpdated(fn(
                                                                                                   Get $get,
                                                                                                   Set $set
                                                                                               ) => self::calculateTotal($get,
                                                                                                   $set)),

                                                                     Forms\Components\TextInput::make('inventory_value')
                                                                                               ->label('Inventory Value')
                                                                                               ->numeric()
                                                                                               ->prefix('MVR')
                                                                                               ->disabled()
                                                                                               ->visible(fn(Get $get
                                                                                               ) => ! $get('is_service'))
                                                                                               ->dehydrated()
                                                                                               ->afterStateUpdated(fn(
                                                                                                   Get $get,
                                                                                                   Set $set
                                                                                               ) => self::calculateTotal($get,
                                                                                                   $set)),

                                                                     Forms\Components\TextInput::make('quantity')
                                                                                               ->label('Stock Quantity')
                                                                                               ->helperText(fn(Get $get
                                                                                               ) => $get('product_type') === 'liquid'
                                                                                                   ? 'Number of containers'
                                                                                                   : 'Number of '.($get('unit_type') ?? 'units'))
                                                                                               ->numeric()
                                                                                               ->visible(fn(Get $get
                                                                                               ) => ! $get('is_service'))
                                                                                               ->required()
                                                                                               ->live()
                                                                                               ->afterStateUpdated(function (
                                                                                                   Get $get,
                                                                                                   Set $set
                                                                                               ) {
                                                                                                   if ($get('product_type') === 'liquid') {
                                                                                                       $total_volume = ($get('quantity') ?? 0) * ($get('volume_per_unit') ?? 0);
                                                                                                       $set('remaining_volume',
                                                                                                           $total_volume);
                                                                                                   }
                                                                                               }),

                                                                     Forms\Components\TextInput::make('remaining_volume')
                                                                                               ->label('Remaining Volume')
                                                                                               ->suffix(fn(Get $get
                                                                                               ) => strtoupper($get('unit_type') ?? ''))
                                                                                               ->disabled()
                                                                                               ->visible(fn(Get $get
                                                                                               ) => $get('product_type') === 'liquid' && ! $get('is_service'))
                                                                                               ->columns(2),
                                                                 ]),
                                        ]),
            ]);
    }

    protected static function calculateTotal(Get $get, Set $set): void
    {
        // Retrieve and sanitize input values
        $salePrice = max(0, floatval($get('sale_price') ?? 0));
        $gst = max(0, floatval($get('gst') ?? 0));
        $qty = max(1, floatval($get('quantity') ?? 1));
        $isService = boolval($get('is_service'));

        // Calculate total
        $total = $salePrice + $gst;

        // Calculate inventory value
        $inventoryValue = $isService ? $salePrice : $total * $qty;

        // Set calculated values
        $set('total', $total);
        $set('inventory_value', $inventoryValue);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item_code')
                                         ->label('Code')
                                         ->searchable(),

                Tables\Columns\TextColumn::make('product_name')
                                         ->label('Name')
                                         ->searchable(),

                Tables\Columns\IconColumn::make('is_service')
                                         ->label('Service / Product')
                                         ->boolean()
                                         ->icons([
                                             'heroicon-o-shopping-bag'       => false,
                                             'heroicon-o-wrench-screwdriver' => true,
                                         ])
                                         ->colors([
                                             'primary' => false,
                                             'warning' => true,
                                         ]),


                Tables\Columns\TextColumn::make('sale_price')
                                         ->label('Price')
                                         ->money('MVR')
                                         ->sortable(),

                Tables\Columns\TextColumn::make('total')
                                         ->label('Total Inc. GST')
                                         ->money('MVR')
                                         ->sortable(),


                Tables\Columns\TextColumn::make('quantity')
                                         ->numeric()
                                         ->sortable()
                                         ->hidden(fn(Builder $query): bool => $query->where('is_service',
                                             true)->exists()),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                                           ->label('Item Type')
                                           ->options([
                                               '0' => 'Products',
                                               '1' => 'Services',
                                           ])
                                           ->attribute('is_service'),
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
            'index'  => Pages\ListStockItems::route('/'),
            'create' => Pages\CreateStockItem::route('/create'),
            'edit'   => Pages\EditStockItem::route('/{record}/edit'),
        ];
    }

    protected static function updateRemainingQuantity(Get $get, Set $set): void
    {
        if ($get('product_type') === 'discrete') {
            $availableQuantity = intval($get('available_quantity') ?? 0);
            $quantity = intval($get('quantity') ?? 0);
            $remainingQuantity = max(0, $availableQuantity - $quantity);
            $set('available_quantity', $remainingQuantity);
        }
    }

    protected static function updateRemainingVolume(Get $get, Set $set): void
    {
        if ($get('product_type') === 'liquid') {
            $remainingVolume = floatval($get('remaining_volume') ?? 0);
            $volume = floatval($get('volume') ?? 0);
            $newRemainingVolume = max(0, $remainingVolume - $volume);
            $set('remaining_volume', $newRemainingVolume);
        }
    }
}
