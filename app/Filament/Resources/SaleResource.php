<?php

namespace App\Filament\Resources;

use Str;
use Exception;
use Filament\Forms;
use App\Models\Sale;
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
use Illuminate\Database\Query\Builder;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Collection;
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
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->getStateUsing(function ($record) {
                        // If vehicle exists, use vehicle's customer
                        if ($record->vehicle?->customer) {
                            return $record->vehicle->customer->name;
                        }

                        // Otherwise use direct customer
                        return $record->customer?->name ?? 'No Customer';
                    })
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->where(function ($q) use ($search) {
                            $q->whereHas('customer', fn ($sub) => $sub->where('name', 'like', "%{$search}%"))
                                ->orWhereHas('vehicle.customer',
                                    fn ($sub) => $sub->where('name', 'like', "%{$search}%"));
                        });
                    })
                    ->sortable(query: function (Builder $query, string $direction) {
                        $query->orderBy(
                            Customer::select('name')
                                ->whereColumn('customers.id', 'sales.customer_id')
                                ->orWhereColumn('customers.id', 'vehicles.customer_id'),
                            $direction
                        );
                    }),
                Tables\Columns\TextColumn::make('vehicle.vehicle_number')
                    ->formatStateUsing(fn (
                        $state,
                        $record
                    ): string => $record->vehicle?->vehicle_number ?? 'No Vehicle'
                    )
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
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    bulkaction::make('print')
                        ->label('Generate Invoices')
                        ->icon('heroicon-o-document-text')
                        ->action(function (Collection $records) {
                            $pdf = PDF::loadView('pdf.invoice', [
                                'sales' => $records,
                            ]);

                            // Get first sale's customer info for filename
                            $firstSale = $records->first();
                            $filename = str_replace(' ', '_', strtolower($firstSale->customer->name))
                                .'_'
                                .$firstSale->customer->phone
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
                        Forms\Components\Section::make('Sale')
                            ->schema([
                                Repeater::make('items')
                                    ->relationship('items')
                                    ->label('Products and Services')
                                    ->schema([
                                        Select::make('stock_item_id')
                                            ->label('Product / Service')
                                            ->options(StockItem::query()->pluck('product_name',
                                                'id'))
                                            ->searchable()
                                            ->required()
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
                                                $items = $get('../../items') ?? [];
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

                                        // good version
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
                                            ->required()
                                            ->dehydrated()
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
                                                $items = $get('../../items') ?? [];
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
                                            ->readOnly()
                                            ->dehydrated(true),

                                        TextInput::make('total_price')
                                            ->label('Total Price')
                                            ->prefix('MVR')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated(true),
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

                        Forms\Components\Section::make('Sale Details')
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
                                    ->relationship('vehicle', 'vehicle_number')
                                    ->searchable() // Allows searching through all vehicles
                                    ->preload() // Loads some options upfront for better performance
                                    ->createOptionForm([
                                        Forms\Components\Select::make('vehicle_type')
                                            ->options([
                                                'motocycle' => 'Motocycle',
                                                'scooter' => 'Scooter',
                                                'bicycle' => 'Bicycle',
                                                'car' => 'Car',
                                                'tricycle' => 'Tricycle',
                                                'island_pickup' => 'Island Pickup',
                                                'pickup' => 'Pickup',
                                                'buggy' => 'Buggy',
                                                'wheel_barrow' => 'Wheel Barrow',
                                            ])->label('Vehicle Type')->required(),

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

                                        Forms\Components\TextInput::make('year_of_manufacture')->label('Year of Manufacture')->placeholder('2019')->required(),
                                        Forms\Components\TextInput::make('engine_number')->placeholder('Example: PJ12345U123456P'),
                                        Forms\Components\TextInput::make('chassis_number')->placeholder('Example: 1HGCM82633A123456'),
                                        Forms\Components\TextInput::make('vehicle_number')->placeholder('Example: P9930')->required(),

                                        Forms\Components\Select::make('customer_id')->label('Customer / Owner')
                                            ->searchable()->required()
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
                                            ): ?string => Customer::find($value)?->name),
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
                            ])->columnSpan(3),
                    ]),
            ]);
    }

    protected static function calculateTotals(Set $set, Get $get): void
    {
        $subtotal = collect($get('items'))->sum(function ($item) {
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
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
            'view' => Pages\ViewSale::route('/{record}'),
        ];
    }
}
