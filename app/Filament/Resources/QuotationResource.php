<?php

namespace App\Filament\Resources;

use Str;
use Filament\Tables;
use App\Models\Vehicle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Customer;
use Filament\Forms\Form;
use App\Models\Quotation;
use App\Models\StockItem;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use App\Support\Enums\TransactionType;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use App\Filament\Resources\QoutationResource\Pages;

class QuotationResource extends Resource
{
    protected static ?string $model = Quotation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document';

    protected static ?string $navigationGroup = 'Core Business Operations';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(12)
                    ->schema([
                        Section::make('Sale')
                            ->schema([
                                Repeater::make('quotationItems')
                                    ->relationship('quotationItems')
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

                        Section::make('Sale Details')
                            ->schema([
                                DatePicker::make('date')
                                    ->required()
                                    ->default(now()),

                                Select::make('customer_id')->label('Customer / Owner')
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
                                        TextInput::make('name'),
                                        TextInput::make('phone'),
                                        TextInput::make('email'),
                                    ])
                                    ->afterStateUpdated(fn (
                                        Set $set
                                    ) => $set('vehicle_id',
                                        null)),


                                Select::make('vehicle_id')
                                    ->label('Vehicle')
                                    ->relationship('vehicle')
                                    ->options(function (Get $get) {
                                        $customerId = $get('customer_id');
                                        if ($customerId) {
                                            return Vehicle::where('customer_id',
                                                $customerId)->pluck('vehicle_number',
                                                    'id');
                                        }

                                        return [];
                                    })
                                    ->required()
                                    ->createOptionForm([
                                        Select::make('vehicle_type')
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
                                            ])->label('Vehicle Type'),
                                        Select::make('brand_id')
                                            ->relationship('brand',
                                                'name')
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

                                        TextInput::make('year_of_manufacture')->label('Year of Manufacture')->placeholder('2019'),
                                        TextInput::make('engine_number')->placeholder('Example: PJ12345U123456P'),
                                        TextInput::make('chassis_number')->placeholder('Example: 1HGCM82633A123456'),
                                        TextInput::make('vehicle_number')->placeholder('Example: P9930'),
                                        Select::make('customer_id')->label('Customer / Owner')
                                            ->searchable()
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

                                    ])
                                    ->disabled(fn (Get $get
                                    ): bool => $get('customer_id') === null),


                                Select::make('transaction_type')->label('Transaction Type')
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
            'index' => Pages\ListQoutations::route('/'),
            'create' => Pages\CreateQoutation::route('/create'),
            'edit' => Pages\EditQoutation::route('/{record}/edit'),
        ];
    }
}
