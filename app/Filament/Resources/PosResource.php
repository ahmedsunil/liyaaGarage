<?php

namespace App\Filament\Resources;

use Str;
use Exception;
use App\Models\Pos;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Customer;
use Filament\Forms\Form;
use App\Models\StockItem;
use Filament\Tables\Table;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use App\Support\Enums\TransactionType;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Resources\PosResource\Pages;

class PosResource extends Resource
{
    protected static ?string $model = Pos::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $label = 'POS';

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->getStateUsing(function ($record) {
                        if ($record->vehicle?->customer) {
                            return $record->vehicle->customer->name;
                        }

                        return $record->customer?->name ?? 'No Customer';
                    })
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->where(function ($q) use ($search) {
                            // Search direct customers (even if vehicle is null)
                            $q->whereHas('customer', fn ($sub) => $sub->where('name', 'like', "%{$search}%"))
                                ->orWhereHas('vehicle.customer',
                                    fn ($sub) => $sub->where('name', 'like', "%{$search}%"))
                                // Include cases where customer exists but vehicle is null
                                ->orWhere(function ($sub) use ($search) {
                                    $sub->whereNull('vehicle_id')
                                        ->whereHas('customer',
                                            fn ($subQ) => $subQ->where('name', 'like', "%{$search}%"));
                                });
                        });
                    })
                    ->sortable(query: function (Builder $query, string $direction) {
                        $query->orderBy(
                            Customer::select('name')
                                ->whereColumn('customers.id', 'pos.customer_id'),
                            $direction
                        );
                    }),

                TextColumn::make('vehicle.vehicle_number')
                    ->label('Vehicle Number')
                    ->default('No Vehicle')
                    ->sortable()
                    ->searchable(),

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
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('print')
                        ->label('Generate Invoices')
                        ->icon('heroicon-o-document-text')
                        ->action(function (Collection $records) {
                            // Transform Pos records to have 'items' property like Sale records
                            $transformedRecords = $records->map(function ($pos) {
                                // Convert the JSON sale_items to a collection similar to the Sale->items relationship
                                $saleItemsCollection = collect($pos->sale_items)->map(function ($item) {
                                    // Create an object with properties similar to SaleItem
                                    $itemObject = (object) $item;

                                    // Ensure quantity is explicitly set as a property on the object
                                    if (isset($item['quantity'])) {
                                        $itemObject->quantity = $item['quantity'];
                                    } else {
                                        // Set a default quantity of 1 if not specified
                                        $itemObject->quantity = 1;
                                    }

                                    // Ensure unit_price is explicitly set as a property on the object
                                    if (isset($item['unit_price'])) {
                                        $itemObject->unit_price = $item['unit_price'];
                                    } else {
                                        // Set a default unit_price of 0 if not specified
                                        $itemObject->unit_price = 0;
                                    }

                                    // Ensure total_price is explicitly set as a property on the object
                                    if (isset($item['total_price'])) {
                                        $itemObject->total_price = $item['total_price'];
                                    } else {
                                        // Calculate total_price from quantity and unit_price if not specified
                                        $itemObject->total_price = $itemObject->quantity * $itemObject->unit_price;
                                    }

                                    // Add a stockItem property that mimics the SaleItem->stockItem relationship
                                    $itemObject->stockItem = StockItem::find($item['stock_item_id']);

                                    return $itemObject;
                                });

                                // Create a modified pos object with an 'items' property for the view
                                $posWithItems = clone $pos;
                                $posWithItems->items = $saleItemsCollection;

                                return $posWithItems;
                            });

                            $pdf = PDF::loadView('pdf.invoice', [
                                'sales' => $transformedRecords,
                            ]);

                            // Get first pos's customer info for filename
                            $firstPos = $records->first();
                            $filename = str_replace(' ', '_', strtolower($firstPos->customer->name))
                                .'_'
                                .$firstPos->customer->phone
                                .'.pdf';

                            return response()->streamDownload(function () use ($pdf) {
                                echo $pdf->output();
                            }, $filename);
                        }),
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
                        Forms\Components\Section::make('POS Sale')
                            ->schema([
                                Repeater::make('sale_items')
                                    ->label('Products and Services')
                                    ->schema([
                                        Select::make('stock_item_id')
                                            ->label('Product / Service')
                                            ->options(StockItem::query()->pluck('product_name',
                                                'id'))
                                            ->searchable()
                                            ->required()
                                            ->getSearchResultsUsing(function (
                                                string $search
                                            ): array {
                                                return StockItem::query()
                                                    ->where('product_name',
                                                        'like',
                                                        "%{$search}%")
                                                    ->orWhere('item_code',
                                                        'like',
                                                        "%{$search}%")
                                                    ->limit(50) // Optional: Limit results to avoid performance issues
                                                    ->pluck('product_name',
                                                        'id')
                                                    ->toArray();
                                            })
                                            ->getOptionLabelUsing(function (
                                                $value
                                            ): ?string {
                                                return StockItem::find($value)?->product_name;
                                            })
                                            ->live()
                                            ->afterStateUpdated(function (
                                                $state,
                                                Set $set,
                                                Get $get
                                            ) {
                                                if (! $state) {
                                                    return;
                                                }

                                                $stockItem = StockItem::find($state);
                                                if (! $stockItem) {
                                                    return;
                                                }

                                                $unitPrice = $stockItem->is_service->value
                                                    ? $stockItem->total_cost_price_with_gst
                                                    : $stockItem->selling_price_per_quantity;

                                                $set('unit_price',
                                                    $unitPrice);

                                                if ($stockItem->is_service->value === 1) {
                                                    $set('quantity', 1);
                                                }

                                                // Calculate total price for this item
                                                $quantity = $stockItem->is_service->value ? 1 : floatval($get('quantity') ?? 1);
                                                $total = round($quantity * $unitPrice,
                                                    2);
                                                $set('total_price', $total);

                                                // Update overall totals using array instead of collection
                                                $items = $get('../../sale_items') ?? [];
                                                foreach ($items as &$item) {
                                                    if ($item['stock_item_id'] === $state) {
                                                        $item['total_price'] = $total;
                                                        break;
                                                    }
                                                }

                                                $subtotal = array_sum(array_column($items,
                                                    'total_price'));
                                                $discountPercentage = floatval($get('../../discount_percentage') ?? 0);
                                                $discountAmount = round(($subtotal * $discountPercentage) / 100,
                                                    2);

                                                $set('../../subtotal_amount',
                                                    $subtotal);
                                                $set('../../discount_amount',
                                                    $discountAmount);
                                                $set('../../total_amount',
                                                    $subtotal - $discountAmount);
                                            }),

                                        TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->helperText(function (
                                                Get $get
                                            ): string {
                                                $stockItemId = $get('stock_item_id');
                                                $stockItem = StockItem::find($stockItemId);

                                                if ($stockItem && ! $stockItem->is_service->value == 1) {
                                                    return 'Available: '.$stockItem->quantity;
                                                }

                                                return ' ';
                                            })
                                            ->minValue(1)
                                            ->default(1)
                                            ->live()
                                            ->disabled(fn (Get $get
                                            ): bool => StockItem::find($get('stock_item_id'))?->is_service->value == '1'
                                            )
                                            ->afterStateUpdated(function (
                                                $state,
                                                Set $set,
                                                Get $get
                                            ) {
                                                // Calculate total price for this item
                                                $unitPrice = floatval($get('unit_price') ?? 0);
                                                $quantity = floatval($state ?? 1);
                                                $total = round($quantity * $unitPrice,
                                                    2);
                                                $set('total_price',
                                                    $total);

                                                // Update overall totals
                                                $items = $get('../../sale_items') ?? [];
                                                $currentState = $get('stock_item_id');

                                                foreach ($items as &$item) {
                                                    if ($item['stock_item_id'] === $currentState) {
                                                        $item['total_price'] = $total;
                                                        break;
                                                    }
                                                }

                                                $subtotal = array_sum(array_column($items,
                                                    'total_price'));
                                                $discountPercentage = floatval($get('../../discount_percentage') ?? 0);
                                                $discountAmount = round(($subtotal * $discountPercentage) / 100,
                                                    2);

                                                $set('../../subtotal_amount',
                                                    $subtotal);
                                                $set('../../discount_amount',
                                                    $discountAmount);
                                                $set('../../total_amount',
                                                    $subtotal - $discountAmount);
                                            }),

                                        TextInput::make('unit_price')
                                            ->label('Unit Price')
                                            ->prefix('MVR')
                                            ->numeric()
                                            ->readOnly(),

                                        TextInput::make('total_price')
                                            ->label('Total Price')
                                            ->prefix('MVR')
                                            ->numeric()
                                            ->readOnly(),
                                    ])
                                    ->columns(4)
                                    ->columnSpan(9)
                                    ->live(onBlur: true)
                                    ->required()
                                    ->afterStateUpdated(function (
                                        Set $set,
                                        Get $get
                                    ) {
                                        self::calculateTotals($set, $get);
                                    }),
                            ])->columnSpan(9),

                        Forms\Components\Section::make('POS Details')
                            ->schema([
                                Forms\Components\DatePicker::make('date')
                                    ->required()
                                    ->default(now()),

                                Forms\Components\Select::make('customer_id')->label('Customer / Owner')
                                    ->searchable()
                                    ->relationship('customer')
                                    ->getSearchResultsUsing(fn (
                                        string $search
                                    ): array => Customer::where('name',
                                        'like',
                                        "%{$search}%")->orWhere('phone',
                                            'like',
                                            "%{$search}%")->limit(50)->pluck('name',
                                                'id')->toArray())
                                    ->getOptionLabelUsing(fn (
                                        $value
                                    ): ?string => Customer::find($value)?->name)
                                    ->required()
                                    ->live()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->required(),
                                        Forms\Components\TextInput::make('phone')->required(),
                                        Forms\Components\TextInput::make('email')->required(),
                                    ]),


                                Select::make('vehicle_id')
                                    ->label('Vehicle')
                                    ->relationship('vehicle', 'vehicle_number',
                                        function ($query, callable $get) {
                                            $customerId = $get('customer_id');

                                            if ($customerId) {
                                                $query->where('customer_id',
                                                    $customerId);
                                            }

                                            return $query;
                                        })
//                                    ->searchable() // Now searches only within customer's vehicles
                                    ->live()
                                    ->createOptionForm([
                                        Forms\Components\Select::make('vehicle_type')
                                            ->options([
                                                'motorcycle' => 'Motorcycle',
                                                'scooter' => 'Scooter',
                                                'bicycle_16' => 'Bicycle 16 Inch',
                                                'bicycle_20' => 'Bicycle 20 Inch',
                                                'bicycle_24' => 'Bicycle 24 Inch',
                                                'car' => 'Car',
                                                'tricycle' => 'Tricycle',
                                                'island_pickup' => 'Island Pickup',
                                                'pickup' => 'Pickup',
                                                'buggy' => 'Buggy',
                                                'wheel_barrow' => 'Wheel Barrow',
                                            ])->label('Vehicle Type')->required()->live(),

                                        Forms\Components\Select::make('brand_id')
                                            ->relationship('brand',
                                                'name')->required()
                                            ->createOptionForm([
                                                TextInput::make('name')->label('Name')->live(onBlur: true)
                                                    ->afterStateUpdated(fn (
                                                        Set $set,
                                                        ?string $state
                                                    ) => $set('slug',
                                                        Str::slug($state))),
                                                TextInput::make('slug')->label('Slug')->unique('brands',
                                                    'slug',
                                                    ignoreRecord: true)->required()->maxLength(255)->readOnly(),
                                            ]),

                                        Forms\Components\TextInput::make('year_of_manufacture')
                                            ->label('Year of Manufacture')
                                            ->placeholder('2019')
                                            ->hidden(fn (
                                                callable $get
                                            ): bool => in_array($get('vehicle_type'),
                                                [
                                                    'bicycle_16',
                                                    'bicycle_20',
                                                    'bicycle_24',
                                                    'tricycle',
                                                    'wheel_barrow',
                                                ])),
                                        Forms\Components\TextInput::make('engine_number')
                                            ->placeholder('Example: PJ12345U123456P')
                                            ->hidden(fn (
                                                callable $get
                                            ): bool => in_array($get('vehicle_type'),
                                                [
                                                    'bicycle_16',
                                                    'bicycle_20',
                                                    'bicycle_24',
                                                    'tricycle',
                                                    'wheel_barrow',
                                                ])),
                                        Forms\Components\TextInput::make('chassis_number')
                                            ->placeholder('Example: 1HGCM82633A123456')
                                            ->hidden(fn (
                                                callable $get
                                            ): bool => in_array($get('vehicle_type'),
                                                [
                                                    'bicycle_16',
                                                    'bicycle_20',
                                                    'bicycle_24',
                                                    'tricycle',
                                                    'wheel_barrow',
                                                ])),
                                        Forms\Components\TextInput::make('vehicle_number')->placeholder('Example: P9930')->required()->label('Plate Number / Vehicle Tag'),

                                        Forms\Components\Hidden::make('customer_id')
                                            ->default(function (
                                                Get $get
                                            ) {
                                                return $get('../../customer_id');
                                            }),
                                    ]),


                                // end of a vehicle_id component
                                Forms\Components\Select::make('transaction_type')->label('Transaction Type')
                                    ->options(TransactionType::class)
                                    ->default(TransactionType::PENDING)
                                    ->required()
                                    ->reactive(),

                                TextInput::make('subtotal_amount')
                                    ->label('Subtotal')
                                    ->prefix('MVR')
                                    ->disabled()
                                    ->dehydrated()
                                    ->live(),

                                TextInput::make('discount_percentage')
                                    ->label('Discount %')
                                    ->suffix('%')
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(function (
                                        $state,
                                        Set $set,
                                        Get $get
                                    ) {
                                        self::calculateTotals($set,
                                            $get);
                                    }),

                                TextInput::make('discount_amount')
                                    ->label('Discount Amount')
                                    ->prefix('MVR')
                                    ->disabled()
                                    ->dehydrated(),

                                TextInput::make('total_amount')
                                    ->label('Total Amount')
                                    ->prefix('MVR')
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\Textarea::make('remarks')
                                    ->label('Remarks'),

                            ])->columnSpan(3),
                    ]),
            ]);
    }

    protected static function calculateTotals(Set $set, Get $get): void
    {
        $subtotal = collect($get('sale_items'))->sum(function ($item) {
            if (empty($item['stock_item_id'])) {
                return 0;
            }

            return floatval($item['total_price'] ?? 0);
        });

        $discountPercentage = floatval($get('discount_percentage') ?? 0);
        $discountAmount = round(($subtotal * $discountPercentage) / 100, 2);

        $set('subtotal_amount', round($subtotal, 2));
        $set('discount_amount', $discountAmount);
        $set('total_amount', round($subtotal - $discountAmount, 2));
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
            'index' => Pages\ListPos::route('/'),
            'create' => Pages\CreatePos::route('/create'),
            'edit' => Pages\EditPos::route('/{record}/edit'),
            'view' => Pages\ViewPos::route('/{record}'),
        ];
    }
}
